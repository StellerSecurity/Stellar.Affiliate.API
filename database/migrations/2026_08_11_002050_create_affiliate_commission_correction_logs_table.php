<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('affiliate_commission_correction_logs')) {
            return;
        }

        Schema::create('affiliate_commission_correction_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('affiliate_commission_id');
            $table->unsignedBigInteger('affiliate_id');
            $table->string('reason', 100);
            $table->string('old_product', 50)->nullable();
            $table->string('new_product', 50)->nullable();
            $table->decimal('old_rate', 6, 4)->nullable();
            $table->decimal('new_rate', 6, 4)->nullable();
            $table->decimal('old_order_amount', 12, 2)->nullable();
            $table->decimal('new_order_amount', 12, 2)->nullable();
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('affiliate_commission_id', 'affiliate_commission_correction_commission_idx');
            $table->index(['affiliate_id', 'created_at'], 'affiliate_commission_correction_affiliate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commission_correction_logs');
    }
};
