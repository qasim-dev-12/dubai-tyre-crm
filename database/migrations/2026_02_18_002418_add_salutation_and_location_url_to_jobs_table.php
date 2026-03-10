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
        $table->string('salutation')->nullable()->after('id');
        // $table->string('location_url')->nullable()->after('longitude');
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
        $table->dropColumn(['salutation']);
    });
    }
};
