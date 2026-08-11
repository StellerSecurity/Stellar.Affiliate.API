<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairCommissionPrecision extends Command
{
    protected $signature = 'affiliate:repair-commission-precision {--force : Recalculate every commission using order_amount x rate}';

    protected $description = 'Recalculate affiliate commissions at exact six-decimal precision without cent rounding.';

    public function handle(): int
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            $this->error('affiliate_commissions does not exist.');
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Recalculate all commissions with an order amount and rate?')) {
            $this->warn('No changes made.');
            return self::SUCCESS;
        }

        $missingSource = DB::table('affiliate_commissions')
            ->where(function ($query) {
                $query->whereNull('order_amount')->orWhereNull('rate');
            })
            ->count();

        $before = DB::table('affiliate_commissions')
            ->whereNotNull('order_amount')
            ->whereNotNull('rate')
            ->whereRaw('ABS(amount - (order_amount * rate)) > 0.0000004')
            ->count();

        DB::transaction(function (): void {
            DB::statement('UPDATE affiliate_commissions SET amount = order_amount * rate WHERE order_amount IS NOT NULL AND rate IS NOT NULL');

            if (Schema::hasTable('payouts')) {
                // Pending/processing payouts are accounting projections and should track the exact linked commission total.
                DB::statement("\n                    UPDATE payouts p\n                    SET p.amount = COALESCE((\n                        SELECT SUM(ac.amount)\n                        FROM affiliate_commissions ac\n                        WHERE ac.payout_id = p.id\n                    ), p.amount)\n                    WHERE p.status <> 'paid'\n                ");
            }
        });

        $remaining = DB::table('affiliate_commissions')
            ->whereNotNull('order_amount')
            ->whereNotNull('rate')
            ->whereRaw('ABS(amount - (order_amount * rate)) > 0.0000004')
            ->count();

        $example = DB::table('affiliate_commissions')
            ->select(['id', 'order_amount', 'rate', 'amount', 'currency', 'status'])
            ->whereNotNull('order_amount')
            ->whereNotNull('rate')
            ->orderByDesc('id')
            ->first();

        $this->info("Corrected {$before} commission(s).");
        $this->line('Exact precision: 6 decimal places.');
        $this->line("Rows without order_amount/rate and therefore not recalculable: {$missingSource}.");
        $this->line("Remaining mismatches: {$remaining}.");

        if ($example) {
            $this->table(
                ['ID', 'Order amount', 'Rate', 'Commission', 'Currency', 'Status'],
                [[
                    $example->id,
                    $example->order_amount,
                    $example->rate,
                    $example->amount,
                    $example->currency,
                    $example->status,
                ]]
            );
        }

        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }
}
