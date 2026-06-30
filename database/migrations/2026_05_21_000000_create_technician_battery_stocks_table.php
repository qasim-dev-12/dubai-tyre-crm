<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('technician_battery_stocks')) {
            return;
        }
        Schema::create('technician_battery_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['technician_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('technician_battery_stocks');
    }
};
