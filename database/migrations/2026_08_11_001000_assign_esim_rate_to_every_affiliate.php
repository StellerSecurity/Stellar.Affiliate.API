<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('affiliates') || ! Schema::hasTable('affiliate_commission_rules')) {
            return;
        }

        $globalRate = DB::table('affiliate_commission_rules')
            ->whereNull('affiliate_id')
            ->where('product', 'esim')
            ->where('type', 'initial')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('rate');

        $globalRate = $globalRate !== null ? (float) $globalRate : 0.10;
        $hasCommissions = Schema::hasTable('affiliate_commissions');
        $now = now();

        DB::table('affiliates')
            ->select('id')
            ->orderBy('id')
            ->chunkById(250, function ($affiliates) use ($globalRate, $hasCommissions, $now): void {
                foreach ($affiliates as $affiliate) {
                    $affiliateId = (int) $affiliate->id;

                    // Preserve an existing negotiated eSIM rate. If only one billing type
                    // was configured previously, that latest value becomes the single
                    // canonical eSIM rate for both initial and recurring eSIM orders.
                    $existingRate = DB::table('affiliate_commission_rules')
                        ->where('affiliate_id', $affiliateId)
                        ->where('product', 'esim')
                        ->where('is_active', true)
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id')
                        ->value('rate');

                    // If the affiliate has no explicit rule but has a historical eSIM
                    // conversion, preserve the most recent actual eSIM commission rate.
                    $historicalRate = null;
                    if ($existingRate === null && $hasCommissions) {
                        $historicalRate = DB::table('affiliate_commissions')
                            ->where('affiliate_id', $affiliateId)
                            ->where('product', 'esim')
                            ->whereNotNull('rate')
                            ->orderByDesc('id')
                            ->value('rate');
                    }

                    $rate = $existingRate !== null
                        ? (float) $existingRate
                        : ($historicalRate !== null ? (float) $historicalRate : $globalRate);

                    $rate = round(max(0, min(1, $rate)), 4);

                    foreach (['initial', 'recurring'] as $type) {
                        $ruleId = DB::table('affiliate_commission_rules')
                            ->where('affiliate_id', $affiliateId)
                            ->where('product', 'esim')
                            ->where('type', $type)
                            ->orderByDesc('updated_at')
                            ->orderByDesc('id')
                            ->value('id');

                        if ($ruleId) {
                            DB::table('affiliate_commission_rules')
                                ->where('id', $ruleId)
                                ->update([
                                    'rate' => $rate,
                                    'is_active' => true,
                                    'updated_at' => $now,
                                ]);

                            DB::table('affiliate_commission_rules')
                                ->where('affiliate_id', $affiliateId)
                                ->where('product', 'esim')
                                ->where('type', $type)
                                ->where('id', '<>', $ruleId)
                                ->update([
                                    'is_active' => false,
                                    'updated_at' => $now,
                                ]);
                        } else {
                            DB::table('affiliate_commission_rules')->insert([
                                'affiliate_id' => $affiliateId,
                                'product' => 'esim',
                                'type' => $type,
                                'rate' => $rate,
                                'is_active' => true,
                                'updated_by_user_id' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // This is an intentional data-normalization migration. Reversing it would
        // discard negotiated affiliate rates, so rollback leaves the data intact.
    }
};
