<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_api_keys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');

            $table->string('key_hash', 191)->unique(); // only hash, never plaintext
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_api_keys');
    }
};
