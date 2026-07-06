<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'warranty_months')) {
                $table->unsignedInteger('warranty_months')->nullable()->after('battery_details');
            }
            if (!Schema::hasColumn('payments', 'warranty_expires_at')) {
                $table->timestamp('warranty_expires_at')->nullable()->after('warranty_months');
            }
            if (!Schema::hasColumn('payments', 'is_warranty_claimed')) {
                $table->boolean('is_warranty_claimed')->default(false)->after('warranty_expires_at');
            }
            if (!Schema::hasColumn('payments', 'warranty_claimed_at')) {
                $table->timestamp('warranty_claimed_at')->nullable()->after('is_warranty_claimed');
            }
        });

        if (!Schema::hasColumn('payments', 'replacement_job_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('replacement_job_id')
                    ->nullable()
                    ->after('warranty_claimed_at')
                    ->constrained('jobs')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('payments', 'claim_of_payment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('claim_of_payment_id')
                    ->nullable()
                    ->after('replacement_job_id')
                    ->constrained('payments')
                    ->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('payments', 'claim_of_payment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['claim_of_payment_id']);
                $table->dropColumn('claim_of_payment_id');
            });
        }
        if (Schema::hasColumn('payments', 'replacement_job_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['replacement_job_id']);
                $table->dropColumn('replacement_job_id');
            });
        }
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('payments', 'warranty_claimed_at') ? 'warranty_claimed_at' : null,
                Schema::hasColumn('payments', 'is_warranty_claimed') ? 'is_warranty_claimed' : null,
                Schema::hasColumn('payments', 'warranty_expires_at') ? 'warranty_expires_at' : null,
                Schema::hasColumn('payments', 'warranty_months') ? 'warranty_months' : null,
            ]));
        });
    }
};
