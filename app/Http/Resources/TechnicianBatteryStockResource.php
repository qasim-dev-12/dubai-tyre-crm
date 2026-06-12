<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianBatteryStockResource extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->product;
        $brand   = $product?->productBrand;

        return [
            'id'                 => $this->id,
            'technician_id'      => $this->technician_id,
            'product_id'         => $this->product_id,
            'brand_name'         => $brand?->name ?? null,
            'product_name'       => $product?->name ?? null,
            'product_code'       => $product?->code ?? null,
            'battery_type'       => $product?->battery_type ?? null,
            'voltage'            => $product?->voltage ?? null,
            'capacity'           => $product?->capacity ?? null,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
        ];
    }
}
