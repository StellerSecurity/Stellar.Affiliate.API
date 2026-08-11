<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('affiliate:verify-esim-commission-repair', function () {
    $expectedRates = [
        'KX9WTTWW' => 10.00,
        'FINDYOURESIM' => 40.00,
        'QFJCHZ01' => 10.00,
        'Y3THQKSL' => 15.00,
        'ESIMCN' => 20.00,
        'MYBESTESIM' => 20.00,
        'ZUJNMA1J' => 10.00,
        'ESIM' => 10.00,
        'DATADOPT' => 10.00,
        'W6VJAEI6' => 20.00,
    ];

    if (! Schema::hasColumn('affiliate_campaigns', 'product') || ! Schema::hasColumn('affiliate_campaigns', 'commission_rate')) {
        $this->error('Campaign commission schema is missing. Run php artisan migrate --force first.');
        return 1;
    }

    $rows = [];
    $hasErrors = false;

    foreach ($expectedRates as $code => $expectedPercent) {
        $affiliate = DB::table('affiliates')
            ->whereRaw('UPPER(public_code) = ?', [$code])
            ->first(['id', 'public_code']);

        if (! $affiliate) {
            $rows[] = [$code, 'MISSING', '—', '—', '—', '—'];
            $hasErrors = true;
            continue;
        }

        $affiliateId = (int) $affiliate->id;
        $initial = DB::table('affiliate_commission_rules')
            ->where('affiliate_id', $affiliateId)
            ->where('product', 'esim')
            ->where('type', 'initial')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('rate');
        $recurring = DB::table('affiliate_commission_rules')
            ->where('affiliate_id', $affiliateId)
            ->where('product', 'esim')
            ->where('type', 'recurring')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('rate');

        $campaignCount = DB::table('affiliate_campaigns')->where('affiliate_id', $affiliateId)->count();
        $badCampaigns = DB::table('affiliate_campaigns')
            ->where('affiliate_id', $affiliateId)
            ->where(function ($query) use ($expectedPercent) {
                $query->where('product', '<>', 'esim')
                    ->orWhereNull('product')
                    ->orWhereNull('commission_rate')
                    ->orWhereRaw('ABS((commission_rate * 100) - ?) > 0.001', [$expectedPercent]);
            })
            ->count();

        $badEligibleCommissions = DB::table('affiliate_commissions')
            ->where('affiliate_id', $affiliateId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) {
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
            ->where(function ($query) use ($expectedPercent) {
                $query->where('product', '<>', 'esim')
                    ->orWhereNull('product')
                    ->orWhereRaw('ABS((rate * 100) - ?) > 0.001', [$expectedPercent]);
            })
            ->count();

        $initialPercent = $initial !== null ? round(((float) $initial) * 100, 2) : null;
        $recurringPercent = $recurring !== null ? round(((float) $recurring) * 100, 2) : null;
        $rulesOk = $initialPercent !== null
            && $recurringPercent !== null
            && abs($initialPercent - $expectedPercent) < 0.001
            && abs($recurringPercent - $expectedPercent) < 0.001;
        $rowOk = $rulesOk && $badCampaigns === 0 && $badEligibleCommissions === 0;

        if (! $rowOk) {
            $hasErrors = true;
        }

        $rows[] = [
            (string) $affiliate->public_code,
            number_format($expectedPercent, 2).'%',
            $initialPercent !== null ? number_format($initialPercent, 2).'%' : 'MISSING',
            $recurringPercent !== null ? number_format($recurringPercent, 2).'%' : 'MISSING',
            $campaignCount.' / '.($campaignCount - $badCampaigns).' OK',
            $badEligibleCommissions === 0 ? 'OK' : $badEligibleCommissions.' WRONG',
        ];
    }

    $this->table(
        ['Affiliate', 'Expected', 'Initial rule', 'Recurring rule', 'Campaigns', 'Pending/approved'],
        $rows
    );

    if ($hasErrors) {
        $this->error('eSIM commission repair verification found mismatches.');
        return 1;
    }

    $corrected = Schema::hasTable('affiliate_commission_correction_logs')
        ? DB::table('affiliate_commission_correction_logs')
            ->where('reason', 'verified_esim_rate_correction_2026_08_11')
            ->count()
        : 0;

    $this->info("eSIM commission repair is consistent. {$corrected} historical commission(s) were corrected.");
    return 0;
})->purpose('Verify the verified eSIM affiliate rates, campaigns, and eligible historical commissions');
