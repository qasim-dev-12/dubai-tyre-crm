<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianBatteryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'product_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
    ];

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
