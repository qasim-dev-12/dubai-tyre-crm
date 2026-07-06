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
        'warranty_months',
        'warranty_expires_at',
        'is_warranty_claimed',
        'warranty_claimed_at',
        'replacement_job_id',
        'claim_of_payment_id',
    ];

    protected $casts = [
        'battery_details' => 'array',
        'warranty_expires_at' => 'datetime',
        'is_warranty_claimed' => 'boolean',
        'warranty_claimed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function replacementJob()
    {
        return $this->belongsTo(Job::class, 'replacement_job_id');
    }

    public function claimOfPayment()
    {
        return $this->belongsTo(Payment::class, 'claim_of_payment_id');
    }
    public function payments()
{
    return $this->hasMany(Payment::class);
}
}
