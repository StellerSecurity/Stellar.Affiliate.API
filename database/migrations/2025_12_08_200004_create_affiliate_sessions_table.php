<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('campaign_id')->nullable();

            $table->string('source', 50)->nullable(); // youtube, tiktok, etc.

            $table->string('session_token', 128)->unique(); // cookie value
            $table->string('browser_fingerprint', 191)->nullable();

            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_sessions');
    }
};
