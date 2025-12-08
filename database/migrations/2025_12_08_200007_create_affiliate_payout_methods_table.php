<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_payout_methods', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');

            $table->string('type', 50); // bank, crypto, paypal, etc.
            $table->json('data')->nullable(); // encrypted / obfuscated info

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['affiliate_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payout_methods');
    }
};
