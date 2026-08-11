<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_campaigns')) {
            return;
        }

        $needsProduct = ! Schema::hasColumn('affiliate_campaigns', 'product');
        $needsCommissionRate = ! Schema::hasColumn('affiliate_campaigns', 'commission_rate');

        if ($needsProduct || $needsCommissionRate) {
            Schema::table('affiliate_campaigns', function (Blueprint $table) use ($needsProduct, $needsCommissionRate): void {
                if ($needsProduct) {
                    $table->string('product', 50)->nullable()->after('country_focus');
                }

                if ($needsCommissionRate) {
                    $table->decimal('commission_rate', 6, 4)->nullable()->after('product');
                }
            });
        }

        // All campaigns that exist at this point are eSIM campaigns.
        DB::table('affiliate_campaigns')->update([
            'product' => 'esim',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliate_campaigns')) {
            return;
        }

        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('affiliate_campaigns', 'commission_rate')) {
                $table->dropColumn('commission_rate');
            }

            if (Schema::hasColumn('affiliate_campaigns', 'product')) {
                $table->dropColumn('product');
            }
        });
    }
};
