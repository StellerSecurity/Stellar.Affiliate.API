<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();

            // Optional link to external user in another system
            $table->unsignedBigInteger('external_user_id')->nullable();

            $table->string('public_code')->unique(); // used in ?ref=CODE
            $table->enum('status', ['pending', 'active', 'banned'])->default('active');

            $table->string('country', 2)->nullable();
            $table->string('payout_currency', 3)->default('EUR');

            $table->timestamps();

            $table->index('external_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
