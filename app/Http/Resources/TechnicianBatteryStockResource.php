<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianBatteryStockResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'technician_id' => $this->technician_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product ? $this->product->name : null,
            'battery_type' => $this->product ? $this->product->battery_type : null,
            'product_code' => $this->product ? $this->product->code : null,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'product_details' => $this->product ? [
                'battery_type' => $this->product->battery_type,
                'voltage' => $this->product->voltage,
                'capacity' => $this->product->capacity,
            ] : null,
        ];
    }
}
