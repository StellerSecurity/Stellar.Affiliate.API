<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id')->nullable();
            $table->string('product', 50);
            $table->enum('type', ['initial', 'recurring']);
            $table->decimal('rate', 6, 4);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'product', 'type'], 'affiliate_commission_rules_lookup');
        });

        $now = now();
        DB::table('affiliate_commission_rules')->insert([
            ['affiliate_id' => null, 'product' => 'vpn', 'type' => 'initial', 'rate' => 1.0000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['affiliate_id' => null, 'product' => 'vpn', 'type' => 'recurring', 'rate' => 0.6000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['affiliate_id' => null, 'product' => 'antivirus', 'type' => 'initial', 'rate' => 1.0000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['affiliate_id' => null, 'product' => 'antivirus', 'type' => 'recurring', 'rate' => 0.6000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['affiliate_id' => null, 'product' => 'esim', 'type' => 'initial', 'rate' => 0.1000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['affiliate_id' => null, 'product' => 'esim', 'type' => 'recurring', 'rate' => 0.1000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commission_rules');
    }
};
