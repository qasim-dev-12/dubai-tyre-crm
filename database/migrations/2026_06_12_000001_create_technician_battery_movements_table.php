<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create the table if it was never created by the earlier migration
        if (!Schema::hasTable('technician_battery_movements')) {
            Schema::create('technician_battery_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technician_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('movement_type');
                $table->decimal('quantity', 10, 2)->default(0);
                $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        // Add adjustment_id column if not already present
        if (!Schema::hasColumn('technician_battery_movements', 'adjustment_id')) {
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
        if (Schema::hasColumn('technician_battery_movements', 'adjustment_id')) {
            Schema::table('technician_battery_movements', function (Blueprint $table) {
                $table->dropForeign(['adjustment_id']);
                $table->dropColumn('adjustment_id');
            });
        }
    }
};
