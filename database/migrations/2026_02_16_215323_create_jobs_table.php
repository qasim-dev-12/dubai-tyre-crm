<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('jobs', function (Blueprint $table) {
        $table->id();

        // Customer Information
        $table->string('name');
        $table->string('mobile');

        // Service
        $table->unsignedBigInteger('service_type_id');

        // Area & Vehicle
        $table->string('area')->nullable();
        $table->string('vehicle_number');

        // Pricing
        $table->decimal('price', 10, 2)->nullable();

        // Technician Assignment
        $table->unsignedBigInteger('technician_id')->nullable();

        // Location (Google Map)
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();

        // Job Workflow Status
        $table->string('status')->default('Pending');

        $table->timestamps();
        $table->foreign('service_type_id')
      ->references('id')
      ->on('service_types')
      ->onDelete('cascade');

$table->foreign('technician_id')
      ->references('id')
      ->on('users')
      ->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
