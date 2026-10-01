<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->string('campaign', 80);
            $table->string('status', 20);
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();
            $table->unique(['affiliate_id', 'campaign']);
            $table->index(['campaign', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_email_deliveries');
    }
};
