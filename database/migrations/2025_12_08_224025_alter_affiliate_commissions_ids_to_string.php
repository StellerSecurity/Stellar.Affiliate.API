<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            // order_id som string (fx subscription id, ekstern order id, UUID, etc.)
            $table->string('order_id', 64)->change();

            // subscription_id også som string (for fleksibilitet)
            $table->string('subscription_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->change();
            $table->unsignedBigInteger('subscription_id')->nullable()->change();
        });
    }
};
