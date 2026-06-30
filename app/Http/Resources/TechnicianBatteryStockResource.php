<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianBatteryStockResource extends JsonResource
{
    public function toArray($request)
    {
        $product  = $this->product;
        $subCat   = $product?->proSubCategory;

        return [
            'id'                 => $this->id,
            'technician_id'      => $this->technician_id,
            'product_id'         => $this->product_id,
            'brand_name'         => $subCat?->name ?? null,
            'product_name'       => $product?->name ?? null,
            'product_code'       => $product?->code ?? null,
            'product_type'       => $product?->product_type ?? null,
            'battery_type'       => $product?->battery_type ?? null,
            'voltage'            => $product?->voltage ?? null,
            'capacity'           => $product?->capacity ?? null,
            'warranty'           => $product?->warranty ?? null,
            'quantity'           => $this->quantity,
            'used_quantity'      => $this->used_quantity,
            'available_quantity' => $this->available_quantity,
        ];
    }
}
