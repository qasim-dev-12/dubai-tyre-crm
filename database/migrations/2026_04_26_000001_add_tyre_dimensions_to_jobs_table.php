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
            $table->string('tyre_width')->nullable()->after('brand');
            $table->string('tyre_height')->nullable()->after('tyre_width');
            $table->string('tyre_rim')->nullable()->after('tyre_height');
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
            $table->dropColumn(['tyre_width', 'tyre_height', 'tyre_rim']);
        });
    }
};
