<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->string('product', 50)->nullable()->after('subscription_id')->index();
            $table->decimal('order_amount', 12, 2)->nullable()->after('product');
            $table->decimal('rate', 6, 4)->change();
            $table->string('rate_source', 32)->nullable()->after('rate');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('paid_out_at')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropIndex(['product']);
            $table->decimal('rate', 5, 2)->change();
            $table->dropColumn([
                'product',
                'order_amount',
                'rate_source',
                'approved_at',
                'rejected_at',
                'paid_out_at',
            ]);
        });
    }
};
