<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobJourney extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'status',
        'message',
        'user_id'
    ];
}
