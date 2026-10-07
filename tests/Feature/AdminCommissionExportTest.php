<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommissionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_exports_all_commissions_matching_the_active_filters(): void
    {
        $admin = User::create([
            'name' => 'Program Analyst',
            'email' => 'analyst@example.com',
            'password' => Hash::make('password'),
            'affiliate_admin_role' => 'analyst',
        ]);

        $matchingAffiliate = Affiliate::create([
            'name' => 'Find Your eSIM',
            'email' => 'find@example.com',
            'public_code' => 'FINDYOURESIM',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);
        $otherAffiliate = Affiliate::create([
            'name' => 'Other Affiliate',
            'email' => 'other@example.com',
            'public_code' => 'OTHER',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        AffiliateCommission::create([
            'affiliate_id' => $matchingAffiliate->id,
            'order_id' => '=MATCHING-ORDER',
            'product' => 'esim',
            'order_amount' => '120.00',
            'type' => 'initial',
            'rate' => '0.6000',
            'rate_source' => 'affiliate_override',
            'amount' => '72.000000',
            'currency' => 'EUR',
            'status' => 'pending',
        ]);
        AffiliateCommission::create([
            'affiliate_id' => $otherAffiliate->id,
            'order_id' => 'OTHER-ORDER',
            'product' => 'esim',
            'order_amount' => '40.00',
            'type' => 'initial',
            'rate' => '0.6000',
            'rate_source' => 'affiliate_override',
            'amount' => '24.000000',
            'currency' => 'EUR',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('affiliate.admin.commissions.export', [
            'affiliate' => 'FINDYOURESIM',
            'status' => 'pending',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Affiliate Code', $csv);
        $this->assertStringContainsString('FINDYOURESIM', $csv);
        $this->assertStringContainsString("'=MATCHING-ORDER", $csv);
        $this->assertStringNotContainsString('OTHER-ORDER', $csv);
    }
}
