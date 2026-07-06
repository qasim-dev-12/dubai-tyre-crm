<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddVat0Rate extends Migration
{
    public function up()
    {
        if (!DB::table('vat_rates')->where('code', 'VAT@0')->exists()) {
            DB::table('vat_rates')->insert([
                'name' => 'VAT 0%',
                'slug' => 'vat-zero',
                'code' => 'VAT@0',
                'rate' => '0.00',
                'status' => 1,
            ]);
        }
    }

    public function down()
    {
        DB::table('vat_rates')->where('code', 'VAT@0')->delete();
    }
}
