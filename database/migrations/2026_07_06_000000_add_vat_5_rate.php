<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddVat5Rate extends Migration
{
    public function up()
    {
        if (!DB::table('vat_rates')->where('code', 'VAT@5')->exists()) {
            DB::table('vat_rates')->insert([
                'name' => 'VAT 5%',
                'slug' => 'vat-5',
                'code' => 'VAT@5',
                'rate' => '5.00',
                'status' => 1,
            ]);
        }
    }

    public function down()
    {
        DB::table('vat_rates')->where('code', 'VAT@5')->delete();
    }
}
