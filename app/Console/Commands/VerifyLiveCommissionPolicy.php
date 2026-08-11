<?php

namespace App\Console\Commands;

use App\Models\AffiliateCommission;
use App\Services\AffiliateCommissionPolicy;
use App\Support\CommissionMath;
use Illuminate\Console\Command;

class VerifyLiveCommissionPolicy extends Command
{
    protected $signature = 'affiliate:verify-live-commission-policy {--latest=100 : Number of newest commissions to inspect}';

    protected $description = 'Verify that recent commissions use the current product policy, order base, and exact amount.';

    public function handle(AffiliateCommissionPolicy $policy): int
    {
        $limit = max(1, min(5000, (int) $this->option('latest')));

        $rows = AffiliateCommission::query()
            ->latest('id')
            ->limit($limit)
            ->get();

        $violations = [];

        foreach ($rows as $commission) {
            $product = $policy->normalizeProduct((string) $commission->product);
            $type = $commission->type === 'recurring' ? 'recurring' : 'initial';
            $resolved = $policy->effectiveRate((int) $commission->affiliate_id, $product, $type);
            $expectedRate = number_format((float) $resolved['rate'], 4, '.', '');

            $issues = [];

            if ((string) $commission->rate !== $expectedRate) {
                $issues[] = 'rate';
            }

            if ($commission->order_amount === null) {
                $issues[] = 'order_amount';
            } else {
                $expectedAmount = CommissionMath::calculate((string) $commission->order_amount, $expectedRate);
                if (CommissionMath::commission((string) $commission->amount) !== $expectedAmount) {
                    $issues[] = 'amount';
                }
            }

            if ($issues !== []) {
                $violations[] = [
                    $commission->id,
                    $commission->affiliate_id,
                    $product,
                    $type,
                    (string) $commission->rate,
                    $expectedRate,
                    $commission->order_amount === null ? 'NULL' : (string) $commission->order_amount,
                    (string) $commission->amount,
                    implode(', ', $issues),
                ];
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Recent commissions checked', $rows->count()],
                ['Policy violations', count($violations)],
            ]
        );

        if ($violations !== []) {
            $this->newLine();
            $this->error('Recent commission policy violations found.');
            $this->table(
                ['ID', 'Affiliate', 'Product', 'Type', 'Stored rate', 'Expected rate', 'Order amount', 'Commission', 'Issue'],
                array_slice($violations, 0, 50)
            );

            return self::FAILURE;
        }

        $this->info('Recent commissions match the current commission policy.');

        return self::SUCCESS;
    }
}
