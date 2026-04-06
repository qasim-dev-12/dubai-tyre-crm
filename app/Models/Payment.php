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
         'receipt' // ✅ ADD THIS
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
