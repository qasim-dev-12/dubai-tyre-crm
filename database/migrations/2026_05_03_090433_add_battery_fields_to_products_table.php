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
          Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->nullable()->after('image_path');
            $table->string('battery_type')->nullable()->after('product_type');
            $table->string('voltage')->nullable()->after('battery_type');
            $table->string('capacity')->nullable()->after('voltage');
            $table->string('warranty')->nullable()->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'battery_type',
                'voltage',
                'capacity',
                'warranty',
            ]);
        });
    }
};
