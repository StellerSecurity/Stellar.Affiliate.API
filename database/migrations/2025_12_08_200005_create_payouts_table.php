<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('affiliate_id');

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');

            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])
                ->default('pending');

            $table->string('method_type', 50); // bank, crypto, etc.
            $table->json('method_details_snapshot')->nullable();

            $table->string('external_reference')->nullable(); // bank tx id, tx hash, etc.

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
