<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('payments', 'battery_details')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->json('battery_details')->nullable()->after('receipt');
            });
        }
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('battery_details');
        });
    }
};
