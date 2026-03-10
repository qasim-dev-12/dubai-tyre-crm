<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;


class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
   
         public function run()
    {
        Department::create([
            'name' => 'Technical',
            'slug' => 'technical',
        ]);

        Department::create([
            'name' => 'Sales',
            'slug' => 'sales',
        ]);

        Department::create([
            'name' => 'Operations',
            'slug' => 'operations',
        ]);
    }

}
