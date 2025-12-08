<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('campaign_id')->nullable();

            $table->string('source', 50)->nullable();       // from ?src=
            $table->string('session_id', 64)->nullable();   // internal tracking id

            $table->string('ip_hash', 128)->nullable();     // hashed IP
            $table->text('user_agent')->nullable();

            $table->text('landing_url')->nullable();
            $table->text('referrer')->nullable();

            $table->timestamps();

            $table->index(['affiliate_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
