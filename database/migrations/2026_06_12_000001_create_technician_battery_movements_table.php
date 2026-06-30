<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('technician_battery_movements') && !Schema::hasColumn('technician_battery_movements', 'adjustment_id')) {
            Schema::table('technician_battery_movements', function (Blueprint $table) {
                $table->foreignId('adjustment_id')
                    ->nullable()
                    ->after('job_id')
                    ->constrained('inventory_adjustments')
                    ->nullOnDelete();
            });
        }
    }

    public function down()
    {
        Schema::table('technician_battery_movements', function (Blueprint $table) {
            $table->dropForeign(['adjustment_id']);
            $table->dropColumn('adjustment_id');
        });
    }
};
