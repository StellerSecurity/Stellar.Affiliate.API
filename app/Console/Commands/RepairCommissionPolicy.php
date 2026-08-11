<?php

namespace App\Console\Commands;

use App\Models\AffiliateCommission;
use App\Services\AffiliateCommissionPolicy;
use App\Services\AffiliateOrderService;
use App\Support\CommissionMath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RepairCommissionPolicy extends Command
{
    protected $signature = 'affiliate:repair-commission-policy
        {--force : Apply corrections without confirmation}
        {--dry-run : Show what would change without writing}
        {--include-paid : Also inspect paid_out commissions}';

    protected $description = 'Apply the current product commission policy to existing commissions and repair missing order bases when possible.';

    public function handle(AffiliateCommissionPolicy $policy, AffiliateOrderService $orders): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $includePaid = (bool) $this->option('include-paid');

        $query = AffiliateCommission::query()
            ->whereIn('status', $includePaid ? ['pending', 'approved', 'paid_out'] : ['pending', 'approved'])
            ->orderBy('id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No commissions matched the selected scope.');
            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm("Inspect {$total} commission(s) and apply the current commission policy?")) {
                $this->warn('No changes made.');
                return self::SUCCESS;
            }
        }

        $rateCache = [];
        $orderCache = [];
        $stats = [
            'checked' => 0,
            'corrected' => 0,
            'already_correct' => 0,
            'order_bases_recovered' => 0,
            'order_bases_failed' => 0,
        ];
        $failures = [];

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $query->chunkById(500, function ($commissions) use (
            $policy,
            $orders,
            $dryRun,
            &$rateCache,
            &$orderCache,
            &$stats,
            &$failures,
            $progress
        ): void {
            foreach ($commissions as $commission) {
                $stats['checked']++;

                $product = $policy->normalizeProduct((string) $commission->product);
                if (! in_array($product, ['esim', 'vpn', 'antivirus'], true)) {
                    $product = 'esim';
                }

                $type = $commission->type === 'recurring' ? 'recurring' : 'initial';
                $rateKey = $commission->affiliate_id.'|'.$product.'|'.$type;

                if (! isset($rateCache[$rateKey])) {
                    $rateCache[$rateKey] = $policy->effectiveRate((int) $commission->affiliate_id, $product, $type);
                }

                $resolved = $rateCache[$rateKey];
                $expectedRate = number_format((float) $resolved['rate'], 4, '.', '');
                $orderAmount = $commission->order_amount === null
                    ? null
                    : CommissionMath::money((string) $commission->order_amount);
                $currency = strtoupper((string) $commission->currency);

                if ($orderAmount === null && trim((string) $commission->order_id) !== '') {
                    $orderId = trim((string) $commission->order_id);

                    if (! array_key_exists($orderId, $orderCache)) {
                        try {
                            $commerce = $orders->getCommissionTotal($orderId);
                            $orderCache[$orderId] = [
                                'amount' => CommissionMath::fromCents((int) $commerce['grand_total_cents']),
                                'currency' => (string) $commerce['currency'],
                            ];
                        } catch (Throwable $exception) {
                            $orderCache[$orderId] = false;
                            if (count($failures) < 25) {
                                $failures[] = [$commission->id, $orderId, $exception->getMessage()];
                            }
                        }
                    }

                    if (is_array($orderCache[$orderId])) {
                        $orderAmount = $orderCache[$orderId]['amount'];
                        $currency = $orderCache[$orderId]['currency'];
                        $stats['order_bases_recovered']++;
                    } else {
                        $stats['order_bases_failed']++;
                    }
                }

                $expectedAmount = $orderAmount === null
                    ? null
                    : CommissionMath::calculate($orderAmount, $expectedRate);

                $currentRate = number_format((float) $commission->rate, 4, '.', '');
                $currentAmount = CommissionMath::commission((string) $commission->amount);
                $needsCorrection = $product !== (string) $commission->product
                    || $currentRate !== $expectedRate
                    || ($orderAmount !== null && (
                        $commission->order_amount === null
                        || CommissionMath::money((string) $commission->order_amount) !== $orderAmount
                        || $currentAmount !== $expectedAmount
                    ));

                if (! $needsCorrection) {
                    $stats['already_correct']++;
                    $progress->advance();
                    continue;
                }

                $stats['corrected']++;

                if (! $dryRun) {
                    $update = [
                        'product' => $product,
                        'rate' => $expectedRate,
                        'rate_source' => (string) $resolved['source'],
                        'updated_at' => now(),
                    ];

                    if ($orderAmount !== null && $expectedAmount !== null) {
                        $update['order_amount'] = $orderAmount;
                        $update['amount'] = $expectedAmount;
                        $update['currency'] = $currency;
                    }

                    DB::transaction(function () use ($commission, $update, $expectedRate, $expectedAmount, $orderAmount, $product): void {
                        if (Schema::hasTable('affiliate_commission_correction_logs')) {
                            DB::table('affiliate_commission_correction_logs')->insert([
                                'affiliate_commission_id' => $commission->id,
                                'affiliate_id' => $commission->affiliate_id,
                                'reason' => 'commission_policy_repair_2026_08_11',
                                'old_product' => $commission->product,
                                'new_product' => $product,
                                'old_rate' => $commission->rate,
                                'new_rate' => $expectedRate,
                                'old_order_amount' => $commission->order_amount,
                                'new_order_amount' => $orderAmount,
                                'old_amount' => $commission->amount,
                                'new_amount' => $expectedAmount ?? $commission->amount,
                                'created_at' => now(),
                            ]);
                        }

                        DB::table('affiliate_commissions')->where('id', $commission->id)->update($update);
                    });
                }

                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine(2);

        if (! $dryRun && Schema::hasTable('payouts')) {
            DB::statement("\n                UPDATE payouts p\n                SET p.amount = COALESCE((\n                    SELECT SUM(ac.amount)\n                    FROM affiliate_commissions ac\n                    WHERE ac.payout_id = p.id\n                ), p.amount)\n                WHERE p.status <> 'paid'\n            ");
        }

        $this->table(['Metric', 'Count'], [
            ['Commissions checked', $stats['checked']],
            [$dryRun ? 'Commissions requiring correction' : 'Commissions corrected', $stats['corrected']],
            ['Commissions already correct', $stats['already_correct']],
            ['Missing order bases recovered from Commerce', $stats['order_bases_recovered']],
            ['Missing order bases not recovered', $stats['order_bases_failed']],
        ]);

        if ($failures !== []) {
            $this->newLine();
            $this->warn('Order bases that could not be recovered from Commerce (showing up to 25):');
            $this->table(['Commission', 'Order ID', 'Error'], $failures);
        }

        if ($dryRun) {
            $this->info('Dry run complete. No database rows were changed.');
        } else {
            $this->info('Commission policy repair complete.');
        }

        return self::SUCCESS;
    }
}
