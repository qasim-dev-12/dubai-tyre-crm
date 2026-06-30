<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('technician_battery_movements')) {
            Schema::create('technician_battery_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('technician_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('movement_type'); // assign, reverse, consumed, allocated, returned
                $table->decimal('quantity', 10, 2)->default(0);
                $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('technician_battery_movements');
    }
};
