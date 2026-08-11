<?php

namespace Tests\Feature;

use App\Services\AffiliateCommissionPolicy;
use Tests\TestCase;

class AffiliateLegacyProductTest extends TestCase
{
    public function test_legacy_missing_products_normalize_to_esim(): void
    {
        $policy = app(AffiliateCommissionPolicy::class);

        foreach ([null, '', 'legacy', 'unknown', 'unassigned', 'none', 'n/a', 'stellar_esim'] as $product) {
            $this->assertSame('esim', $policy->normalizeProduct($product));
        }
    }

    public function test_explicit_products_remain_explicit(): void
    {
        $policy = app(AffiliateCommissionPolicy::class);

        $this->assertSame('esim', $policy->normalizeProduct('esim'));
        $this->assertSame('vpn', $policy->normalizeProduct('vpn'));
        $this->assertSame('antivirus', $policy->normalizeProduct('antivirus'));
    }
}
