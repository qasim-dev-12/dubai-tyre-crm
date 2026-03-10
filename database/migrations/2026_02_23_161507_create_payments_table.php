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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        // 🔗 Connect payment to job
        $table->foreignId('job_id')
              ->constrained('jobs')
              ->onDelete('cascade');

        // 💰 Payment amount
        $table->decimal('amount', 10, 2);

        // 💳 Optional fields
        $table->string('payment_method')->nullable(); // cash/card
        $table->string('reference_number')->nullable();
        $table->text('notes')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
