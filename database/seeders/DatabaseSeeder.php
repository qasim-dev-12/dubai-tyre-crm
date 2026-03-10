<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // for demo
        if(config('app.is_demo_mode')){
            $this->call([
                DemoDatabaseSeeder::class
            ]);
        }
        else{
            $this->call([
                RoleSeeder::class,
                UserSeeder::class,
                AccountSeeder::class,
                GeneralSettingsSeeder::class,
                CurrencySeeder::class,
                ServiceTypeSeeder::class,
                UserRoleSeeder::class,
                PermissionSeeder::class,
                 LeadPermissionSeeder::class,   // 👈 ADD THIS
                UserPermissionSeeder::class,
                RolePermissionSeeder::class,
                ClientSeeder::class,
                VatRateSeeder::class,
                MenuSeeder::class,
                 DepartmentSeeder::class,
            ]);
        }

    }
}
