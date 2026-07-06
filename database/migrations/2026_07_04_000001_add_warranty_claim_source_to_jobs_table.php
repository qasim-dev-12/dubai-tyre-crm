<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('jobs', 'warranty_claim_source_payment_id')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->foreignId('warranty_claim_source_payment_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('payments')
                    ->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('jobs', 'warranty_claim_source_payment_id')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropForeign(['warranty_claim_source_payment_id']);
                $table->dropColumn('warranty_claim_source_payment_id');
            });
        }
    }
};
