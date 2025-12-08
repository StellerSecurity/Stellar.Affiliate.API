<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');

            // IDs from external billing system (no FK)
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('subscription_id')->nullable();

            $table->enum('type', ['initial', 'recurring']); // 100% vs 60%
            $table->decimal('rate', 5, 2);                  // e.g. 1.00 or 0.60
            $table->decimal('amount', 12, 2);               // commission amount
            $table->string('currency', 3)->default('EUR');

            $table->enum('status', ['pending', 'approved', 'rejected', 'paid_out'])
                ->default('pending');

            $table->unsignedBigInteger('payout_id')->nullable(); // link to payouts.id

            $table->timestamp('eligible_payout_at')->nullable(); // after refund window

            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index('order_id');
            $table->index('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
