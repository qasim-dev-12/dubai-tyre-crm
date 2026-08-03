<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Department;

class AddTechnicianAndCustomerServiceUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $technicianSample = Employee::where('name', 'like', '%smith%')->first();
        $technicianDeptId = $technicianSample ? $technicianSample->department_id : (Department::first() ? Department::first()->id : 1);

        $csSample = Employee::where('designation', 'like', '%customer service%')->first();
        $csDeptId = $csSample ? $csSample->department_id : $technicianDeptId;

        $technicians = [
            ['name' => 'Zubair', 'email' => 'zubairbt10@800speedy.com', 'password' => 'zubair123'],
            ['name' => 'Tahir', 'email' => 'tahirbt80@800speedy.com', 'password' => 'tahir123'],
        ];

        $customerService = [
            ['name' => 'Raja', 'email' => 'rajabt100@800speedy.com', 'password' => 'raja123'],
            ['name' => 'Hamza', 'email' => 'hamzabt110@800speedy.com', 'password' => 'hamza123'],
        ];

        $technicianRole = Role::where('slug', 'technician')->first();
        $customerServiceRole = Role::where('slug', 'customer-service')->first();

        if ($customerServiceRole) {
            $allPermissionIds = \DB::table('permissions')->pluck('id')->toArray();
            $customerServiceRole->permissions()->sync($allPermissionIds);
        }

        $nextEmpId = function () {
            $last = Employee::latest()->first();
            return $last ? $last->emp_id + 1 : 1;
        };

        if ($technicianRole) {
            foreach ($technicians as $d) {
                if (User::where('email', $d['email'])->exists()) {
                    continue;
                }

                $user = User::create([
                    'name' => $d['name'],
                    'email' => $d['email'],
                    'password' => Hash::make($d['password']),
                    'account_role' => 0,
                ]);
                $user->roles()->attach($technicianRole->id);
                $user->permissions()->attach($technicianRole->permissions);

                Employee::create([
                    'name' => $d['name'],
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

        if ($customerServiceRole) {
            foreach ($customerService as $d) {
                if (User::where('email', $d['email'])->exists()) {
                    continue;
                }

                $user = User::create([
                    'name' => $d['name'],
                    'email' => $d['email'],
                    'password' => Hash::make($d['password']),
                    'account_role' => 1,
                ]);
                $user->roles()->attach($customerServiceRole->id);
                $user->permissions()->attach($customerServiceRole->permissions);

                Employee::create([
                    'name' => $d['name'],
                    'emp_id' => $nextEmpId(),
                    'department_id' => $csDeptId,
                    'designation' => 'Customer Service',
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $emails = [
            'zubairbt10@800speedy.com',
            'tahirbt80@800speedy.com',
            'rajabt100@800speedy.com',
            'hamzabt110@800speedy.com',
        ];

        foreach (User::whereIn('email', $emails)->get() as $user) {
            if ($user->employee) {
                $user->employee->delete();
            }
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->delete();
        }
    }
}
