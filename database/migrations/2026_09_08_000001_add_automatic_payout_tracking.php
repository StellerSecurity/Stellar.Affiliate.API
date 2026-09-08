<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->uuid('request_id')->nullable()->unique();
            $table->string('provider_environment', 16)->nullable();
            $table->timestamp('cycle_at')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->decimal('commission_total', 18, 6)->nullable();
            $table->string('commission_fingerprint', 64)->nullable();
            $table->string('provider_state', 40)->nullable();
            $table->string('attention_reason', 100)->nullable();
            $table->unique(['affiliate_id', 'cycle_at'], 'payout_affiliate_cycle_unique');
        });
        Schema::table('affiliate_payout_methods', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable();
            $table->text('encrypted_details')->nullable();
            $table->string('registration_error', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_payout_methods', fn (Blueprint $table) => $table->dropColumn(['verified_at', 'encrypted_details', 'registration_error']));
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropUnique('payout_affiliate_cycle_unique');
            $table->dropUnique(['request_id']);
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['request_id', 'provider_environment', 'cycle_at', 'scheduled_at', 'attempted_at', 'checked_at', 'commission_total', 'commission_fingerprint', 'provider_state', 'attention_reason']);
        });
    }
};
