<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('technician_battery_stocks', 'reserved_quantity')) {
            Schema::table('technician_battery_stocks', function (Blueprint $table) {
                $table->renameColumn('reserved_quantity', 'used_quantity');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('technician_battery_stocks', 'used_quantity')) {
            Schema::table('technician_battery_stocks', function (Blueprint $table) {
                $table->renameColumn('used_quantity', 'reserved_quantity');
            });
        }
    }
};
