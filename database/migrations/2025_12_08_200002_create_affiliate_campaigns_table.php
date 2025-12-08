<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_campaigns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id'); // no FK, just ID
            $table->string('name');                     // yt_review_nov
            $table->string('source', 50);               // youtube, tiktok, blog, ads

            $table->string('sub_id1')->nullable();
            $table->string('sub_id2')->nullable();

            $table->string('country_focus', 2)->nullable(); // e.g. DE, DK, CH

            $table->timestamps();

            $table->index('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_campaigns');
    }
};
