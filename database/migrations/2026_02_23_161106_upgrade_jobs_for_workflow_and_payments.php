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

            // 🔁 Update default status
            $table->string('status')->default('DCC')->change();

            // ⏱ Workflow timestamps
            $table->timestamp('on_the_way_at')->nullable();
            $table->timestamp('reached_at')->nullable();
            $table->timestamp('job_started_at')->nullable();
            $table->timestamp('job_completed_at')->nullable();

            // 💰 Payment tracking
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['Unpaid', 'Partial', 'Paid'])
                  ->default('Unpaid');
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

            $table->string('status')->default('Assigned')->change();

            $table->dropColumn([
                'on_the_way_at',
                'reached_at',
                'job_started_at',
                'job_completed_at',
                'paid_amount',
                'due_amount',
                'payment_status'
            ]);
        });
    }
};
