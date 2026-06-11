<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->user_id ? $this->user : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'empID' => $this->emp_id,
            'slug' => $this->slug,
            'department' => $this->department,
            'designation' => $this->designation,
            'salary' => $this->salary,
            'totalSalary' => $this->totalSalary(),
            'commission' => $this->commission,
            'mobileNumber' => $this->mobile_number,
            'birthDate' => $this->birth_date,
            'gender' => $this->gender,
            'bloodGroup' => $this->blood_group,
            'religion' => $this->religion,
            'appointmentDate' => $this->appointment_date,
            'joiningDate' => $this->joining_date,
            'address' => $this->address,
            'user' => $user,
            'allowLogin' => (bool) ($this->user_id && $user),
            'email' => $user?->email,
            'role' => $user ? ($user->roles->first() ?? '') : '',
            'status' => (int) $this->status,
            'image' => $this->image_path ? asset('/images/employees/'.$this->image_path) : '',
        ];
    }
}
