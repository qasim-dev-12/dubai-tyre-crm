<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianBatteryStockResource;
use App\Models\TechnicianBatteryStock;
use Illuminate\Http\Request;

class TechnicianBatteryStockController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TechnicianBatteryStock::with(['product', 'product.proSubCategory'])
            ->where('quantity', '>', 0);

        if ($user && ($user->account_role == 0 || $user->roles()->where('slug', 'technician')->exists())) {
            if ($user->employee) {
                $query->where('technician_id', $user->employee->id);
            } else {
                return response()->json([
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0],
                    'message' => 'No employee profile linked to your account. Please contact admin.',
                ]);
            }
        }

        return TechnicianBatteryStockResource::collection($query->latest()->paginate($request->perPage ?? 20));
    }

    public function show(Request $request, $id)
    {
        $stock = TechnicianBatteryStock::with(['product', 'product.proSubCategory'])->findOrFail($id);

        $user = $request->user();
        if ($user && ($user->account_role == 0 || $user->roles()->where('slug', 'technician')->exists())) {
            if (!$user->employee || $stock->technician_id !== $user->employee->id) {
                abort(403, 'You are not authorized to view this battery stock');
            }
        }

        return new TechnicianBatteryStockResource($stock);
    }
}
