<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTyreRepairWarrantyClaimServiceType extends Migration
{
    public function up()
    {
        if (!DB::table('service_types')->where('name', 'Tyre Repair Warranty Claim')->exists()) {
            DB::table('service_types')->insert([
                'name' => 'Tyre Repair Warranty Claim',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('service_types')->where('name', 'Tyre Repair Warranty Claim')->delete();
    }
}
