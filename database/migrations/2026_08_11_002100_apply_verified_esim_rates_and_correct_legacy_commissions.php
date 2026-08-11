<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ESIM_RATE_BPS = [
        'KX9WTTWW' => 1000,
        'FINDYOURESIM' => 4000,
        'QFJCHZ01' => 1000,
        'Y3THQKSL' => 1500,
        'ESIMCN' => 2000,
        'MYBESTESIM' => 2000,
        'ZUJNMA1J' => 1000,
        'ESIM' => 1000,
        'DATADOPT' => 1000,
        'W6VJAEI6' => 2000,
    ];

    public function up(): void
    {
        $requiredTables = [
            'affiliates',
            'affiliate_campaigns',
            'affiliate_commission_rules',
            'affiliate_commissions',
            'affiliate_commission_correction_logs',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is missing.");
            }
        }

        foreach (['product', 'commission_rate'] as $column) {
            if (! Schema::hasColumn('affiliate_campaigns', $column)) {
                throw new RuntimeException("affiliate_campaigns.{$column} is missing. Run the preceding migrations first.");
            }
        }

        $maxCommissionId = (int) (DB::table('affiliate_commissions')->max('id') ?? 0);
        $now = now();

        DB::transaction(function () use ($maxCommissionId, $now): void {
            $affiliates = DB::table('affiliates')
                ->get(['id', 'public_code'])
                ->keyBy(static fn ($affiliate): string => strtoupper(trim((string) $affiliate->public_code)));

            foreach (self::ESIM_RATE_BPS as $publicCode => $rateBps) {
                $affiliate = $affiliates->get($publicCode);
                if (! $affiliate) {
                    continue;
                }

                $affiliateId = (int) $affiliate->id;
                $rate = $rateBps / 10000;

                $this->upsertEsimRules($affiliateId, $rate, $now);

                DB::table('affiliate_campaigns')
                    ->where('affiliate_id', $affiliateId)
                    ->update([
                        'product' => 'esim',
                        'commission_rate' => $rate,
                        'updated_at' => $now,
                    ]);

                if ($maxCommissionId > 0) {
                    $this->correctEligibleCommissions($affiliateId, $rateBps, $maxCommissionId, $now);
                }
            }
        });
    }

    public function down(): void
    {
        // Financial corrections are intentionally not reversed automatically.
        // The correction log preserves every before/after value for audit purposes.
    }

    private function upsertEsimRules(int $affiliateId, float $rate, $now): void
    {
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

                continue;
            }

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

    private function correctEligibleCommissions(int $affiliateId, int $newRateBps, int $maxCommissionId, $now): void
    {
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
            ->chunkById(200, function ($commissions) use ($affiliateId, $newRateBps, $now): void {
                foreach ($commissions as $commission) {
                    $oldAmountCents = $this->moneyToCents($commission->amount);
                    $orderAmountCents = $commission->order_amount !== null
                        ? $this->moneyToCents($commission->order_amount)
                        : null;

                    if ($orderAmountCents === null) {
                        $oldRateBps = (int) round(((float) $commission->rate) * 10000);
                        if ($oldRateBps <= 0) {
                            continue;
                        }

                        $orderAmountCents = (int) round(($oldAmountCents * 10000) / $oldRateBps);
                    }

                    $newAmountCents = (int) round(($orderAmountCents * $newRateBps) / 10000);
                    $newRate = $newRateBps / 10000;
                    $newOrderAmount = $this->centsToMoney($orderAmountCents);
                    $newAmount = $this->centsToMoney($newAmountCents);

                    $oldProduct = $commission->product !== null ? (string) $commission->product : null;
                    $oldRate = $commission->rate !== null ? (float) $commission->rate : null;
                    $oldOrderAmount = $commission->order_amount !== null ? (float) $commission->order_amount : null;
                    $oldAmount = $commission->amount !== null ? (float) $commission->amount : null;

                    $changed = strtolower(trim((string) $oldProduct)) !== 'esim'
                        || $oldRate === null
                        || abs($oldRate - $newRate) > 0.00001
                        || $oldOrderAmount === null
                        || abs($oldOrderAmount - $newOrderAmount) > 0.004
                        || $oldAmount === null
                        || abs($oldAmount - $newAmount) > 0.004;

                    if (! $changed) {
                        continue;
                    }

                    DB::table('affiliate_commission_correction_logs')->insert([
                        'affiliate_commission_id' => (int) $commission->id,
                        'affiliate_id' => $affiliateId,
                        'reason' => 'verified_esim_rate_correction_2026_08_11',
                        'old_product' => $oldProduct,
                        'new_product' => 'esim',
                        'old_rate' => $oldRate,
                        'new_rate' => $newRate,
                        'old_order_amount' => $oldOrderAmount,
                        'new_order_amount' => $newOrderAmount,
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'created_at' => $now,
                    ]);

                    DB::table('affiliate_commissions')
                        ->where('id', (int) $commission->id)
                        ->update([
                            'product' => 'esim',
                            'order_amount' => $newOrderAmount,
                            'rate' => $newRate,
                            'rate_source' => 'legacy_esim_corrected',
                            'amount' => $newAmount,
                            'updated_at' => $now,
                        ]);
                }
            }, 'id');
    }

    private function moneyToCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function centsToMoney(int $cents): float
    {
        return round($cents / 100, 2);
    }
};
