<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::table('roles')->updateOrInsert(
        ['slug' => 'developer'],
        ['name' => 'Developer']
    );

    DB::table('roles')->updateOrInsert(
        ['slug' => 'super-admin'],
        ['name' => 'Super Admin']
    );

    DB::table('roles')->updateOrInsert(
        ['slug' => 'technician'],
        ['name' => 'Technician']
    );
    }
}
