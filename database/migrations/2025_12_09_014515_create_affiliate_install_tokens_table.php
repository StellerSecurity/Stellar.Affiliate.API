<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_install_tokens', function (Blueprint $table) {
            $table->id();

            $table->string('install_token', 80)->unique();

            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('campaign_id')->nullable();

            $table->string('source', 100)->nullable();
            $table->string('sub_id1', 255)->nullable();
            $table->string('sub_id2', 255)->nullable();
            $table->string('product', 50)->nullable(); // e.g. 'app', 'ios', 'android'

            // Optional attribution info once the app sends it back
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('platform', 50)->nullable();    // 'ios' / 'android'
            $table->string('device_id', 255)->nullable();

            $table->timestamp('claimed_at')->nullable();    // when app attributed install
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('affiliate_id');
            $table->index('campaign_id');
            $table->index('user_id');
            $table->index('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_install_tokens');
    }
};
