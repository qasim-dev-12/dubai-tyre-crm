<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'job_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'receipt',
        'battery_details',
    ];

    protected $casts = [
        'battery_details' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function payments()
{
    return $this->hasMany(Payment::class);
}
}
