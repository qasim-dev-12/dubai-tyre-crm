<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ServiceType;
// use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Payment;
use App\Models\JobJourney;

class Job extends Model
{
    use HasFactory;
    public const STATUS_FLOW = [
        'Assigned',
        'DCC',
        'On The Way',
        'Reached',
        'Job Started',
        'Job Completed'
    ];

    protected $fillable = [
        'salutation',
        'name',
        'mobile',
        'service_type_id',
        'area',
        'vehicle_number',
        'price',
        'technician_id',
        'latitude',
        'longitude',
        'location_url',
        'status',
        'paid_amount',
        'due_amount',
        'payment_status',
       'eta_minutes',
'eta_started_at',

    ];
       protected $casts = [
        'on_the_way_at' => 'datetime',
        'reached_at' => 'datetime',
        'job_started_at' => 'datetime',
        'job_completed_at' => 'datetime',
         'eta_started_at' => 'datetime:c',
    ];
public function client()
{
    return $this->belongsTo(Client::class);
}
    // ✅ Relationship: Service Type
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    // ✅ Relationship: Technician (User)
    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }
      public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function journeys()
{
    return $this->hasMany(JobJourney::class)->latest();
}

}
