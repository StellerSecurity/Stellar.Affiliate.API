<?php

namespace App\Console\Commands;

use App\Services\AffiliateOrderService;
use App\Support\CommissionMath;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RepairCommissionOrderTotals extends Command
{
    protected $signature = 'affiliate:repair-commerce-order-totals
        {--force : Apply corrections without confirmation}
        {--dry-run : Fetch and compare Commerce totals without writing changes}
        {--include-paid : Also correct commissions already marked paid_out}
        {--affiliate= : Restrict to an affiliate public code or numeric affiliate ID}
        {--limit=0 : Limit the number of unique Commerce orders processed}';

    protected $description = 'Replace affiliate commission bases with Commerce grand totals and recalculate exact commissions.';

    public function handle(AffiliateOrderService $orders): int
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            $this->error('affiliate_commissions does not exist.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $includePaid = (bool) $this->option('include-paid');
        $affiliateId = $this->resolveAffiliateId();

        if ($affiliateId === false) {
            return self::FAILURE;
        }

        $baseQuery = $this->commissionQuery($includePaid, $affiliateId);
        $missingOrderId = (clone $baseQuery)
            ->where(function (Builder $query): void {
                $query->whereNull('order_id')->orWhere('order_id', '');
            })
            ->count();

        $orderIdsQuery = (clone $baseQuery)
            ->whereNotNull('order_id')
            ->where('order_id', '<>', '')
            ->select('order_id')
            ->distinct()
            ->orderBy('order_id');

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $orderIdsQuery->limit($limit);
        }

        $orderIds = $orderIdsQuery->pluck('order_id')->map(static fn ($id): string => (string) $id)->all();

        if ($orderIds === []) {
            $this->info('No commission orders matched the selected scope.');
            if ($missingOrderId > 0) {
                $this->warn("{$missingOrderId} commission(s) have no Order ID and cannot be verified against Commerce.");
                return self::FAILURE;
            }
            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force')) {
            $scope = $includePaid ? 'including paid-out commissions' : 'excluding paid-out commissions';
            if (! $this->confirm('Fetch '.count($orderIds)." Commerce order(s) and correct commission totals ({$scope})?")) {
                $this->warn('No changes made.');
                return self::SUCCESS;
            }
        }

        $stats = [
            'orders_checked' => 0,
            'orders_failed' => 0,
            'commissions_checked' => 0,
            'commissions_corrected' => 0,
            'commissions_unchanged' => 0,
            'missing_rate' => 0,
        ];
        $failedOrders = [];

        $progress = $this->output->createProgressBar(count($orderIds));
        $progress->start();

        foreach ($orderIds as $orderId) {
            try {
                $commerce = $orders->getCommissionTotal($orderId);
                $stats['orders_checked']++;
            } catch (Throwable $exception) {
                $stats['orders_failed']++;
                if (count($failedOrders) < 25) {
                    $failedOrders[] = [
                        'order_id' => $orderId,
                        'error' => $exception->getMessage(),
                    ];
                }
                $progress->advance();
                continue;
            }

            $grandTotal = CommissionMath::fromCents((int) $commerce['grand_total_cents']);
            $currency = (string) $commerce['currency'];

            $commissionRows = $this->commissionQuery($includePaid, $affiliateId)
                ->where('order_id', $orderId)
                ->orderBy('id')
                ->get();

            foreach ($commissionRows as $commission) {
                $stats['commissions_checked']++;

                if ($commission->rate === null) {
                    $stats['missing_rate']++;
                    continue;
                }

                $expectedAmount = CommissionMath::calculate($grandTotal, (string) $commission->rate);
                $currentOrderAmount = $commission->order_amount === null
                    ? null
                    : CommissionMath::money((string) $commission->order_amount);
                $currentAmount = CommissionMath::commission((string) $commission->amount);
                $currentCurrency = strtoupper((string) $commission->currency);

                $needsCorrection = $currentOrderAmount !== $grandTotal
                    || $currentAmount !== $expectedAmount
                    || $currentCurrency !== $currency;

                if (! $needsCorrection) {
                    $stats['commissions_unchanged']++;
                    continue;
                }

                $stats['commissions_corrected']++;

                if ($dryRun) {
                    continue;
                }

                DB::transaction(function () use ($commission, $grandTotal, $expectedAmount, $currency): void {
                    if (Schema::hasTable('affiliate_commission_correction_logs')) {
                        DB::table('affiliate_commission_correction_logs')->insert([
                            'affiliate_commission_id' => $commission->id,
                            'affiliate_id' => $commission->affiliate_id,
                            'reason' => 'commerce_grand_total_repair_2026_08_11',
                            'old_product' => $commission->product,
                            'new_product' => $commission->product,
                            'old_rate' => $commission->rate,
                            'new_rate' => $commission->rate,
                            'old_order_amount' => $commission->order_amount,
                            'new_order_amount' => $grandTotal,
                            'old_amount' => $commission->amount,
                            'new_amount' => $expectedAmount,
                            'created_at' => now(),
                        ]);
                    }

                    DB::table('affiliate_commissions')
                        ->where('id', $commission->id)
                        ->update([
                            'order_amount' => $grandTotal,
                            'amount' => $expectedAmount,
                            'currency' => $currency,
                            'updated_at' => now(),
                        ]);
                });
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        if (! $dryRun && Schema::hasTable('payouts')) {
            DB::statement("\n                UPDATE payouts p\n                SET p.amount = COALESCE((\n                    SELECT SUM(ac.amount)\n                    FROM affiliate_commissions ac\n                    WHERE ac.payout_id = p.id\n                ), p.amount)\n                WHERE p.status <> 'paid'\n            ");
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Commerce orders checked', $stats['orders_checked']],
                ['Commerce orders failed', $stats['orders_failed']],
                ['Commissions checked', $stats['commissions_checked']],
                [$dryRun ? 'Commissions requiring correction' : 'Commissions corrected', $stats['commissions_corrected']],
                ['Commissions already correct', $stats['commissions_unchanged']],
                ['Commissions missing rate', $stats['missing_rate']],
                ['Commissions missing Order ID', $missingOrderId],
            ]
        );

        if (! $includePaid) {
            $paidCount = $this->commissionQuery(true, $affiliateId)->where('status', 'paid_out')->count();
            if ($paidCount > 0) {
                $this->warn("{$paidCount} paid-out commission(s) were intentionally left unchanged. Use --include-paid only if you intend to restate paid accounting records.");
            }
        }

        if ($failedOrders !== []) {
            $this->newLine();
            $this->warn('Commerce orders that could not be verified (showing up to 25):');
            $this->table(['Order ID', 'Error'], array_map(
                static fn (array $row): array => [$row['order_id'], $row['error']],
                $failedOrders
            ));
        }

        if ($dryRun) {
            $this->info('Dry run complete. No database rows were changed.');
        } else {
            $this->info('Commerce grand-total commission repair complete.');
        }

        return $stats['orders_failed'] === 0
            && $stats['missing_rate'] === 0
            && $missingOrderId === 0
                ? self::SUCCESS
                : self::FAILURE;
    }

    private function commissionQuery(bool $includePaid, int|null $affiliateId): Builder
    {
        $query = DB::table('affiliate_commissions');

        if (! $includePaid) {
            $query->where('status', '<>', 'paid_out');
        }

        if ($affiliateId !== null) {
            $query->where('affiliate_id', $affiliateId);
        }

        return $query;
    }

    private function resolveAffiliateId(): int|null|false
    {
        $affiliate = trim((string) $this->option('affiliate'));
        if ($affiliate === '') {
            return null;
        }

        $affiliateId = ctype_digit($affiliate)
            ? (int) $affiliate
            : (int) DB::table('affiliates')->whereRaw('UPPER(public_code) = ?', [strtoupper($affiliate)])->value('id');

        if ($affiliateId <= 0) {
            $this->error("Affiliate {$affiliate} was not found.");
            return false;
        }

        return $affiliateId;
    }
}
