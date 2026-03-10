<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
      DB::table('jobs')->update(['technician_id' => null]);

    // Add new foreign key pointing to employees
    Schema::table('jobs', function (Blueprint $table) {
        $table->foreign('technician_id')
              ->references('id')
              ->on('employees')
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
    Schema::table('jobs', function (Blueprint $table) {
        $table->dropForeign('jobs_technician_id_foreign');
    });
}
};
