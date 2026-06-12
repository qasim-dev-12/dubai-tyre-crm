<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianBatteryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'product_id',
        'movement_type',
        'quantity',
        'job_id',
        'adjustment_id',
        'notes',
    ];

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function adjustment()
    {
        return $this->belongsTo(InventoryAdjustment::class);
    }
}
