<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;

class BackfillTechnicianEmployeeLinks extends Migration
{
    /**
     * Guarantees Zubair/Tahir have both a User and a linked, active
     * Technician Employee row, regardless of whether the earlier
     * "add_technician_and_customer_service_users" migration already
     * ran (and is therefore skipped by `php artisan migrate`) on a
     * given environment.
     */
    public function up()
    {
        $technicianRole = Role::where('slug', 'technician')->first();

        if (!$technicianRole) {
            return;
        }

        $technicianDeptId = Employee::whereNotNull('department_id')->value('department_id') ?? 1;

        $nextEmpId = function () {
            $last = Employee::latest('emp_id')->first();
            return $last ? $last->emp_id + 1 : 1;
        };

        $technicians = [
            'zubairbt10@800speedy.com' => 'Zubair',
            'tahirbt80@800speedy.com' => 'Tahir',
        ];

        foreach ($technicians as $email => $name) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                continue;
            }

            $user->roles()->syncWithoutDetaching([$technicianRole->id]);
            $user->permissions()->sync($technicianRole->permissions->pluck('id'));

            $employee = Employee::where('user_id', $user->id)->first();

            if ($employee) {
                $employee->update([
                    'designation' => 'Technician',
                    'status' => 1,
                ]);
                continue;
            }

            Employee::create([
                'name' => $name,
                'emp_id' => $nextEmpId(),
                'department_id' => $technicianDeptId,
                'designation' => 'Technician',
                'salary' => 0,
                'commission' => 0,
                'mobile_number' => '000000000',
                'birth_date' => '1995-01-01',
                'gender' => 'Male',
                'appointment_date' => now()->toDateString(),
                'joining_date' => now()->toDateString(),
                'status' => 1,
                'image_path' => '',
                'user_id' => $user->id,
            ]);
        }
    }

    public function down()
    {
        // Intentionally left blank: this is a data backfill, not a schema change.
    }
}
