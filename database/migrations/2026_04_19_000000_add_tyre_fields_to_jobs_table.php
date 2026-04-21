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
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('vehicle_number');
            $table->string('size')->nullable()->after('brand');
            $table->decimal('buying_price', 10, 2)->nullable()->after('size');
            $table->decimal('selling_price', 10, 2)->nullable()->after('buying_price');
            $table->decimal('service_charges', 10, 2)->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['brand', 'size', 'buying_price', 'selling_price', 'service_charges']);
        });
    }
};
