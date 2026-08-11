<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Legacy affiliate links existed before products were tagged.
     * All of those historical unassigned records belong to Stellar eSIM.
     */
    public function up(): void
    {
        $legacyValues = [
            '',
            'legacy',
            'unknown',
            'unassigned',
            'null',
            'none',
            'n/a',
            'na',
            'e_sim',
            'stellar_esim',
            'stellar-esim',
            'stellar esim',
        ];

        if (Schema::hasTable('affiliate_commissions') && Schema::hasColumn('affiliate_commissions', 'product')) {
            DB::table('affiliate_commissions')
                ->where(function ($query) use ($legacyValues): void {
                    $query->whereNull('product')
                        ->orWhereIn(DB::raw('LOWER(TRIM(product))'), $legacyValues);
                })
                ->update([
                    'product' => 'esim',
                    'updated_at' => now(),
                ]);

            Schema::table('affiliate_commissions', function (Blueprint $table): void {
                $table->string('product', 50)->default('esim')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('affiliate_campaigns') && Schema::hasColumn('affiliate_campaigns', 'product')) {
            DB::table('affiliate_campaigns')
                ->where(function ($query) use ($legacyValues): void {
                    $query->whereNull('product')
                        ->orWhereIn(DB::raw('LOWER(TRIM(product))'), $legacyValues);
                })
                ->update([
                    'product' => 'esim',
                    'updated_at' => now(),
                ]);

            Schema::table('affiliate_campaigns', function (Blueprint $table): void {
                $table->string('product', 50)->default('esim')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliate_commissions') && Schema::hasColumn('affiliate_commissions', 'product')) {
            Schema::table('affiliate_commissions', function (Blueprint $table): void {
                $table->string('product', 50)->nullable()->default(null)->change();
            });
        }

        if (Schema::hasTable('affiliate_campaigns') && Schema::hasColumn('affiliate_campaigns', 'product')) {
            Schema::table('affiliate_campaigns', function (Blueprint $table): void {
                $table->string('product', 50)->nullable()->default(null)->change();
            });
        }
    }
};
