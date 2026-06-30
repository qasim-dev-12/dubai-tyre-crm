<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\AdjustmentProduct;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryAdjustment;
use App\Models\TechnicianBatteryStock;
use App\Models\TechnicianBatteryMovement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AdjustmentResource;
use App\Http\Resources\AdjustmentListResource;

class InventoryAdjustmentController extends Controller
{
    // define middleware
    public function __construct()
    {
        $this->middleware('can:adjustment-list', ['only' => ['index', 'search']]);
        $this->middleware('can:adjustment-create', ['only' => ['create']]);
        $this->middleware('can:adjustment-view', ['only' => ['show']]);
        $this->middleware('can:adjustment-edit', ['only' => ['update']]);
        $this->middleware('can:adjustment-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = InventoryAdjustment::with('technician', 'user')->latest();
        $user = $request->user();

        if ($this->isTechnicianUser($user)) {
            if ($user->employee) {
                $query->where('technician_id', $user->employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return AdjustmentListResource::collection($query->paginate($request->perPage));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate request
        $this->validate($request, [
            'adjustmentReason' => 'required|string|max:255',
            'technician_id' => 'required|exists:employees,id',
            'selectedProducts' => 'required|array|min:1',
            'selectedProducts.*' => 'required|distinct',
            'adjustmentDate' => 'nullable|date_format:Y-m-d',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // generate code
            $code = 1;
            $prevCode = InventoryAdjustment::latest()->first();
            if ($prevCode) {
                $code = $prevCode->code + 1;
            }

            // get logged in user id
            $userId = auth()->user()->id;

            // create adjustment
            $adjustment = InventoryAdjustment::create([
                'reason' => $request->adjustmentReason,
                'code' => $code,
                'date' => $request->adjustmentDate,
                'note' => clean($request->note),
                'status' => $request->status,
                'created_by' => $userId,
                'technician_id' => $request->technician_id,
            ]);

            // store adjustment products
            foreach ($request->selectedProducts as $key => $selectedProduct) {
                // get the product
                $product = Product::where('slug', $selectedProduct['slug'])->first();

                // update inventory count
                if ($selectedProduct['adjustType'] == 'Increment') {
                    $stock = $product->inventory_count + $selectedProduct['adjustQty'];
                } else {
                    $stock = $product->inventory_count - $selectedProduct['adjustQty'];
                }
                $purchasePrice = ($product->purchase_price * $stock) / $stock;

                // update product purchase price
                $product->update([
                    'purchase_price' => $purchasePrice,
                    'inventory_count' => $stock,
                ]);

                // store product
                AdjustmentProduct::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $product->id,
                    'type' => $selectedProduct['adjustType'] == 'Increment' ? 1 : 0,
                    'purchase_price' => $selectedProduct['purchasePrice'],
                    'quantity' => $selectedProduct['adjustQty'],
                ]);

                // sync technician battery stock
                $batteryStock = TechnicianBatteryStock::firstOrNew([
                    'technician_id' => $request->technician_id,
                    'product_id' => $product->id,
                ]);

                // Decrement adjustment means product is taken from inventory and assigned to the technician.
                // Increment adjustment means product is returned from technician back to inventory.
                $delta = $selectedProduct['adjustType'] == 'Increment'
                    ? -$selectedProduct['adjustQty']
                    : $selectedProduct['adjustQty'];

                $batteryStock->quantity = max(0, ($batteryStock->quantity ?? 0) + $delta);
                $batteryStock->available_quantity = max(0, ($batteryStock->available_quantity ?? 0) + $delta);
                $batteryStock->used_quantity = $batteryStock->used_quantity ?? 0;
                $batteryStock->save();
            }

            // add activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($adjustment)
                ->withProperties([
                    'name' => "",
                    'code' => '[' . config('config.adjustmentPrefix') . '-' . $code . ']',
                    'event' => 'Create',
                    'slug' => $adjustment->slug,
                    'routeName' => 'adjustments.show'
                ])
                ->useLog('Adjustment Created')
                ->log('Adjustment Created');

            DB::commit();

            return $this->responseWithSuccess('Adjustment added successfully');
        } catch (Exception $e) {
            DB::commit();
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $slug)
    {
        try {
            $adjustment = InventoryAdjustment::with(
                'adjustmentProducts.product.productUnit',
                'user',
                'technician'
            )->where('slug', $slug)->firstOrFail();

            $user = $request->user();
            if ($this->isTechnicianUser($user)) {
                if (!$user->employee || $adjustment->technician_id !== $user->employee->id) {
                    return $this->responseWithError('You are not authorized to view this adjustment', [], 403);
                }
            }

            return new AdjustmentResource($adjustment);
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        // get the adjustment
        $adjustment = InventoryAdjustment::where('slug', $slug)->first();

        // validate request
        $this->validate($request, [
            'adjustmentReason' => 'required|string|max:255',
            'selectedProducts' => 'required|array|min:1',
            'selectedProducts.*' => 'required|distinct',
            'adjustmentDate' => 'nullable|date_format:Y-m-d',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // update adjustment
            $adjustment->update([
                'reason' => $request->adjustmentReason,
                'date' => $request->adjustmentDate,
                'note' => clean($request->note),
                'status' => $request->status,
            ]);

            // add activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($adjustment)
                ->withProperties([
                    'name' => "",
                    'code' => '[' . config('config.adjustmentPrefix') . '-' . $adjustment->code . ']',
                    'event' => 'Update',
                    'slug' => $adjustment->slug,
                    'routeName' => 'adjustments.show'
                ])
                ->useLog('Adjustment Updated')
                ->log('Adjustment Updated');

            // reverse old battery stock then delete old adjustment products
            $oldTechnicianId = $adjustment->technician_id;
            $oldProducts = $adjustment->adjustmentProducts()->with('product')->get();
            foreach ($oldProducts as $oldProduct) {
                if ($oldTechnicianId && $oldProduct->product_id) {
                    $batteryStock = TechnicianBatteryStock::where([
                        'technician_id' => $oldTechnicianId,
                        'product_id' => $oldProduct->product_id,
                    ])->first();
                    if ($batteryStock) {
                        // type=0: Decrement (assigned to technician) → reverse by subtracting
                        // type=1: Increment (returned from technician) → reverse by adding
                        $reverseDelta = $oldProduct->type == 0
                            ? -$oldProduct->quantity
                            : $oldProduct->quantity;
                        $batteryStock->quantity = max(0, $batteryStock->quantity + $reverseDelta);
                        $batteryStock->available_quantity = max(0, $batteryStock->available_quantity + $reverseDelta);
                        $batteryStock->save();
                        TechnicianBatteryMovement::create([
                            'technician_id' => $oldTechnicianId,
                            'product_id' => $oldProduct->product_id,
                            'adjustment_id' => $adjustment->id,
                            'movement_type' => 'returned',
                            'quantity' => $oldProduct->quantity,
                            'notes' => 'Reversed on adjustment update',
                        ]);
                    }
                }
                $oldProduct->delete();
            }

            // store purchase products
            foreach ($request->selectedProducts as $key => $selectedProduct) {
                // get the product
                $product = Product::where('slug', $selectedProduct['slug'])->first();

                $productStock = $product->inventory_count;
                if ($request->oriAdjustType == 'Increment') {
                    $productStock = $product->inventory_count - $request->oriAdjustQty;
                } else {
                    $productStock = $product->inventory_count + $request->oriAdjustQty;
                }

                // update inventory count
                if ($selectedProduct['adjustType'] == 'Increment') {
                    $productStock = $productStock + $selectedProduct['adjustQty'];
                } else {
                    $productStock = $productStock - $selectedProduct['adjustQty'];
                }
                $purchasePrice = ($product->purchase_price * $productStock) / $productStock;

                // update product purchase price
                $product->update([
                    'purchase_price' => $purchasePrice,
                    'inventory_count' => $productStock,
                ]);

                // store product
                AdjustmentProduct::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $product->id,
                    'type' => $selectedProduct['adjustType'] == 'Increment' ? 1 : 0,
                    'purchase_price' => $selectedProduct['purchasePrice'],
                    'quantity' => $selectedProduct['adjustQty'],
                ]);

                // sync technician battery stock with new quantities
                if ($adjustment->technician_id) {
                    $batteryStock = TechnicianBatteryStock::firstOrNew([
                        'technician_id' => $adjustment->technician_id,
                        'product_id' => $product->id,
                    ]);
                    $delta = $selectedProduct['adjustType'] == 'Increment'
                        ? -$selectedProduct['adjustQty']
                        : $selectedProduct['adjustQty'];
                    $batteryStock->quantity = max(0, ($batteryStock->quantity ?? 0) + $delta);
                    $batteryStock->available_quantity = max(0, ($batteryStock->available_quantity ?? 0) + $delta);
                    $batteryStock->used_quantity = $batteryStock->used_quantity ?? 0;
                    $batteryStock->save();
                    TechnicianBatteryMovement::create([
                        'technician_id' => $adjustment->technician_id,
                        'product_id' => $product->id,
                        'adjustment_id' => $adjustment->id,
                        'movement_type' => 'allocated',
                        'quantity' => $selectedProduct['adjustQty'],
                        'notes' => 'Applied on adjustment update',
                    ]);
                }
            }

            DB::commit();

            return $this->responseWithSuccess('Adjustment updated successfully');
        } catch (Exception $e) {
            DB::rollback();
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        try {
            DB::beginTransaction();

            $adjustment = InventoryAdjustment::with('adjustmentProducts')->where('slug', $slug)->first();

            // check if the adjustment can be delete
            $canDelete = true;
            foreach ($adjustment->adjustmentProducts as $key => $adjustmentProduct) {
                if ($adjustmentProduct->type == 1 && $adjustmentProduct->product->inventory_count <= $adjustmentProduct->quantity) {
                    $canDelete = false;
                    break;
                }
            }

            if ($canDelete) {
                foreach ($adjustment->adjustmentProducts as $key => $adjustmentProduct) {
                    if ($adjustmentProduct->type == 1) {
                        $adjustmentProduct->product->update([
                            'inventory_count' => $adjustmentProduct->product->inventory_count - $adjustmentProduct->quantity,
                        ]);
                    } else {
                        $adjustmentProduct->product->update([
                            'inventory_count' => $adjustmentProduct->product->inventory_count + $adjustmentProduct->quantity,
                        ]);
                    }
                }

                // add activity log
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($adjustment)
                    ->withProperties([
                        'name' => "",
                        'code' => '[' . config('config.adjustmentPrefix') . '-' . $adjustment->code . ']',
                        'event' => 'Delete'
                    ])
                    ->useLog('Adjustment Deleted')
                    ->log('Adjustment Deleted');

                // reverse battery stock before deleting adjustment
                if ($adjustment->technician_id) {
                    foreach ($adjustment->adjustmentProducts as $adjustmentProduct) {
                        $batteryStock = TechnicianBatteryStock::where([
                            'technician_id' => $adjustment->technician_id,
                            'product_id' => $adjustmentProduct->product_id,
                        ])->first();
                        if ($batteryStock) {
                            $reverseDelta = $adjustmentProduct->type == 0
                                ? -$adjustmentProduct->quantity
                                : $adjustmentProduct->quantity;
                            $batteryStock->quantity = max(0, $batteryStock->quantity + $reverseDelta);
                            $batteryStock->available_quantity = max(0, $batteryStock->available_quantity + $reverseDelta);
                            $batteryStock->save();
                            TechnicianBatteryMovement::create([
                                'technician_id' => $adjustment->technician_id,
                                'product_id' => $adjustmentProduct->product_id,
                                'adjustment_id' => $adjustment->id,
                                'movement_type' => 'returned',
                                'quantity' => $adjustmentProduct->quantity,
                                'notes' => 'Reversed on adjustment delete',
                            ]);
                        }
                    }
                }

                $adjustment->delete();
            } else {
                return $this->responseWithError('Sorry you can\'t delete this adjustment!');
            }

            DB::commit();

            return $this->responseWithSuccess('Adjustment deleted successfully');
        } catch (Exception $e) {
            DB::rollback();
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * search resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(Request $request)
    {
        $term = $request->term;
        $filterType = $request->filterType;
        $query = InventoryAdjustment::with('technician', 'user');
        $user = $request->user();

        if ($this->isTechnicianUser($user)) {
            if ($user->employee) {
                $query->where('technician_id', $user->employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Apply status filter first
        if ($filterType === 'active') {
            $query->where('status', 1);
        } elseif ($filterType === 'inactive') {
            $query->where('status', 0);
        }

        if ($request->startDate && $request->endDate) {
            $query = $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }

        $query->where(function ($query) use ($term) {
            $query->where('reason', 'LIKE', '%' . $term . '%')->orWhere('code', 'LIKE', '%' . $term . '%');
        });

        return AdjustmentListResource::collection($query->latest()->paginate($request->perPage));
    }

    protected function isTechnicianUser($user)
    {
        return $user && (
            $user->account_role == 0 ||
            $user->roles()->where('slug', 'technician')->exists()
        );
    }
}
