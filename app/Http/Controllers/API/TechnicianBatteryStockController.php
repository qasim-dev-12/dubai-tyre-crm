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
        $query = TechnicianBatteryStock::with(['product', 'product.proSubCategory']);

        if ($user && ($user->account_role == 0 || $user->roles()->where('slug', 'technician')->exists())) {
            if ($user->employee) {
                $query->where('technician_id', $user->employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return TechnicianBatteryStockResource::collection($query->paginate($request->perPage ?? 10));
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
