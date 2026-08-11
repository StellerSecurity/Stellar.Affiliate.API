<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeLegacyEsimProducts extends Command
{
    protected $signature = 'affiliate:normalize-legacy-esim-products
        {--force : Apply changes without confirmation}
        {--dry-run : Show what would be normalized without changing data}';

    protected $description = 'Assign legacy or missing affiliate products to Stellar eSIM without changing financial values.';

    private array $legacyValues = [
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

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $commissionCount = $this->matchingCount('affiliate_commissions');
        $campaignCount = $this->matchingCount('affiliate_campaigns');

        $this->table(
            ['Record type', 'Rows to normalize'],
            [
                ['Commissions', $commissionCount],
                ['Campaigns', $campaignCount],
            ]
        );

        if ($commissionCount === 0 && $campaignCount === 0) {
            $this->info('All legacy affiliate products are already normalized to eSIM.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry run complete. No database rows were changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Normalize these legacy product records to eSIM?')) {
            $this->warn('No changes made.');
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $this->normalizeTable('affiliate_commissions');
            $this->normalizeTable('affiliate_campaigns');
        });

        $remainingCommissions = $this->matchingCount('affiliate_commissions');
        $remainingCampaigns = $this->matchingCount('affiliate_campaigns');

        $this->info("Normalized {$commissionCount} commission(s) and {$campaignCount} campaign(s) to eSIM.");
        $this->line('Commission rates, amounts, statuses, Order IDs and payout state were not changed.');
        $this->line("Remaining legacy commission products: {$remainingCommissions}.");
        $this->line("Remaining legacy campaign products: {$remainingCampaigns}.");

        return $remainingCommissions === 0 && $remainingCampaigns === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function matchingCount(string $table): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product')) {
            return 0;
        }

        return $this->legacyQuery($table)->count();
    }

    private function normalizeTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product')) {
            return;
        }

        $this->legacyQuery($table)->update([
            'product' => 'esim',
            'updated_at' => now(),
        ]);
    }

    private function legacyQuery(string $table): Builder
    {
        return DB::table($table)
            ->where(function (Builder $query): void {
                $query->whereNull('product')
                    ->orWhereIn(DB::raw('LOWER(TRIM(product))'), $this->legacyValues);
            });
    }
}
