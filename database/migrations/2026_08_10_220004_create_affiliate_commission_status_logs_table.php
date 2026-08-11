<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_commission_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_commission_id');
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['affiliate_commission_id', 'created_at'], 'affiliate_commission_status_log_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commission_status_logs');
    }
};
