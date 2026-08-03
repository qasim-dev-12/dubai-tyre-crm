<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianCashEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id', 'job_id', 'entry_date', 'category', 'amount', 'note',
    ];

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
