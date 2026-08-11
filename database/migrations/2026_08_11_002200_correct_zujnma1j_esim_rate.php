<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PUBLIC_CODE = 'ZUJNMA1J';
    private const RATE_BPS = 1000;

    public function up(): void
    {
        foreach ([
            'affiliates',
            'affiliate_campaigns',
            'affiliate_commission_rules',
            'affiliate_commissions',
            'affiliate_commission_correction_logs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        if (! Schema::hasColumn('affiliate_campaigns', 'product')
            || ! Schema::hasColumn('affiliate_campaigns', 'commission_rate')) {
            return;
        }

        $affiliate = DB::table('affiliates')
            ->whereRaw('UPPER(public_code) = ?', [self::PUBLIC_CODE])
            ->first(['id']);

        if (! $affiliate) {
            return;
        }

        $affiliateId = (int) $affiliate->id;
        $rate = self::RATE_BPS / 10000;
        $now = now();
        $maxCommissionId = (int) (DB::table('affiliate_commissions')->max('id') ?? 0);

        DB::transaction(function () use ($affiliateId, $rate, $now, $maxCommissionId): void {
            foreach (['initial', 'recurring'] as $type) {
                $ruleIds = DB::table('affiliate_commission_rules')
                    ->where('affiliate_id', $affiliateId)
                    ->where('product', 'esim')
                    ->where('type', $type)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                $primaryRuleId = $ruleIds->first();

                if ($primaryRuleId) {
                    DB::table('affiliate_commission_rules')
                        ->where('id', $primaryRuleId)
                        ->update([
                            'rate' => $rate,
                            'is_active' => true,
                            'updated_by_user_id' => null,
                            'updated_at' => $now,
                        ]);

                    if ($ruleIds->count() > 1) {
                        DB::table('affiliate_commission_rules')
                            ->whereIn('id', $ruleIds->slice(1)->values()->all())
                            ->update([
                                'is_active' => false,
                                'updated_at' => $now,
                            ]);
                    }
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

            DB::table('affiliate_campaigns')
                ->where('affiliate_id', $affiliateId)
                ->update([
                    'product' => 'esim',
                    'commission_rate' => $rate,
                    'updated_at' => $now,
                ]);

            if ($maxCommissionId <= 0) {
                return;
            }

            DB::table('affiliate_commissions')
                ->where('affiliate_id', $affiliateId)
                ->where('id', '<=', $maxCommissionId)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query): void {
                    $query->whereNull('product')
                        ->orWhere('product', '')
                        ->orWhereIn(DB::raw('LOWER(product)'), [
                            'legacy',
                            'unknown',
                            'esim',
                            'stellar_esim',
                            'stellar-esim',
                            'stellar esim',
                        ]);
                })
                ->orderBy('id')
                ->chunkById(500, function ($commissions) use ($affiliateId, $rate, $now): void {
                    foreach ($commissions as $commission) {
                        $oldRate = $commission->rate !== null ? (float) $commission->rate : null;
                        $oldAmount = $commission->amount !== null ? (float) $commission->amount : null;
                        $oldOrderAmount = $commission->order_amount !== null ? (float) $commission->order_amount : null;
                        $oldProduct = $commission->product !== null ? (string) $commission->product : null;

                        $orderAmount = $oldOrderAmount;
                        if ($orderAmount === null) {
                            if ($oldRate === null || $oldRate <= 0 || $oldAmount === null) {
                                continue;
                            }
                            $orderAmount = round($oldAmount / $oldRate, 2);
                        }

                        $newAmount = round($orderAmount * $rate, 2);
                        $changed = strtolower(trim((string) $oldProduct)) !== 'esim'
                            || $oldRate === null
                            || abs($oldRate - $rate) > 0.00001
                            || $oldOrderAmount === null
                            || abs($oldOrderAmount - $orderAmount) > 0.004
                            || $oldAmount === null
                            || abs($oldAmount - $newAmount) > 0.004;

                        if (! $changed) {
                            continue;
                        }

                        DB::table('affiliate_commission_correction_logs')->insert([
                            'affiliate_commission_id' => (int) $commission->id,
                            'affiliate_id' => $affiliateId,
                            'reason' => 'zujnma1j_esim_rate_correction_2026_08_11',
                            'old_product' => $oldProduct,
                            'new_product' => 'esim',
                            'old_rate' => $oldRate,
                            'new_rate' => $rate,
                            'old_order_amount' => $oldOrderAmount,
                            'new_order_amount' => $orderAmount,
                            'old_amount' => $oldAmount,
                            'new_amount' => $newAmount,
                            'created_at' => $now,
                        ]);

                        DB::table('affiliate_commissions')
                            ->where('id', (int) $commission->id)
                            ->update([
                                'product' => 'esim',
                                'order_amount' => $orderAmount,
                                'rate' => $rate,
                                'rate_source' => 'legacy_esim_corrected',
                                'amount' => $newAmount,
                                'updated_at' => $now,
                            ]);
                    }
                }, 'id');
        });
    }

    public function down(): void
    {
        // Historical financial corrections are not reversed automatically.
    }
};
