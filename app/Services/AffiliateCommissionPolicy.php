<?php

namespace App\Services;

use App\Models\AffiliateCommissionRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AffiliateCommissionPolicy
{
    public function normalizeProduct(?string $product): string
    {
        $candidate = strtolower(trim((string) $product));
        $legacyDefault = strtolower(trim((string) config('affiliate.legacy_default_product', 'esim')));
        $legacyUnassigned = array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) config('affiliate.legacy_unassigned_products', ['', 'legacy', 'unknown', 'unassigned'])
        );

        if (in_array($candidate, $legacyUnassigned, true)) {
            return $legacyDefault !== '' ? $legacyDefault : 'esim';
        }

        foreach ((array) config('affiliate.products', []) as $key => $definition) {
            $aliases = array_map(
                static fn ($alias): string => strtolower(trim((string) $alias)),
                (array) ($definition['aliases'] ?? [])
            );

            if ($candidate === strtolower((string) $key) || in_array($candidate, $aliases, true)) {
                return (string) $key;
            }
        }

        foreach (['antivirus', 'esim', 'vpn'] as $knownProduct) {
            if (str_contains($candidate, $knownProduct)) {
                return $knownProduct;
            }
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '_', $candidate) ?: 'unknown';

        return trim($normalized, '_') ?: 'unknown';
    }

    public function productLabel(?string $product): string
    {
        $normalized = $this->normalizeProduct($product);

        return (string) config(
            "affiliate.products.{$normalized}.label",
            ucwords(str_replace('_', ' ', $normalized))
        );
    }

    public function effectiveRate(int $affiliateId, string $product, string $type): array
    {
        $product = $this->normalizeProduct($product);
        $type = $type === 'recurring' ? 'recurring' : 'initial';

        // Only eSIM is negotiated per affiliate. VPN and Antivirus always use
        // the shared program rate so every affiliate receives the same terms.
        if ($product === 'esim') {
            $affiliateRule = AffiliateCommissionRule::query()
                ->where('affiliate_id', $affiliateId)
                ->where('product', 'esim')
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN type = ? THEN 0 ELSE 1 END", [$type])
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if ($affiliateRule) {
                return [
                    'rate' => (float) $affiliateRule->rate,
                    'source' => 'affiliate_override',
                    'rule_id' => (int) $affiliateRule->id,
                ];
            }
        }

        return $this->globalRate($product, $type);
    }

    public function setAffiliateEsimRate(int $affiliateId, float $rate, ?int $updatedByUserId = null): float
    {
        $rate = round(max(0, min(1, $rate)), 4);

        if (! Schema::hasTable('affiliate_commission_rules')) {
            return $rate;
        }

        DB::transaction(function () use ($affiliateId, $rate, $updatedByUserId): void {
            foreach (['initial', 'recurring'] as $type) {
                $rules = AffiliateCommissionRule::query()
                    ->where('affiliate_id', $affiliateId)
                    ->where('product', 'esim')
                    ->where('type', $type)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                $rule = $rules->first() ?: new AffiliateCommissionRule([
                    'affiliate_id' => $affiliateId,
                    'product' => 'esim',
                    'type' => $type,
                ]);

                $rule->fill([
                    'rate' => $rate,
                    'is_active' => true,
                    'updated_by_user_id' => $updatedByUserId,
                ])->save();

                if ($rules->count() > 1) {
                    AffiliateCommissionRule::query()
                        ->where('affiliate_id', $affiliateId)
                        ->where('product', 'esim')
                        ->where('type', $type)
                        ->where('id', '<>', $rule->id)
                        ->update(['is_active' => false]);
                }
            }
        });

        if (Schema::hasTable('affiliate_campaigns')
            && Schema::hasColumn('affiliate_campaigns', 'product')
            && Schema::hasColumn('affiliate_campaigns', 'commission_rate')) {
            DB::table('affiliate_campaigns')
                ->where('affiliate_id', $affiliateId)
                ->where('product', 'esim')
                ->update([
                    'commission_rate' => $rate,
                    'updated_at' => now(),
                ]);
        }

        return $rate;
    }

    public function ensureAffiliateEsimRate(int $affiliateId, ?int $updatedByUserId = null): float
    {
        if (! Schema::hasTable('affiliate_commission_rules')) {
            return (float) config('affiliate.products.esim.rates.initial', 0.10);
        }

        $existing = AffiliateCommissionRule::query()
            ->where('affiliate_id', $affiliateId)
            ->where('product', 'esim')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $rate = $existing
            ? (float) $existing->rate
            : (float) $this->globalRate('esim', 'initial')['rate'];

        return $this->setAffiliateEsimRate($affiliateId, $rate, $updatedByUserId);
    }

    public function resetAffiliateEsimRate(int $affiliateId, ?int $updatedByUserId = null): float
    {
        $rate = (float) $this->globalRate('esim', 'initial')['rate'];

        return $this->setAffiliateEsimRate($affiliateId, $rate, $updatedByUserId);
    }

    public function matrix(?int $affiliateId = null): array
    {
        $matrix = [];

        foreach ((array) config('affiliate.products', []) as $product => $definition) {
            foreach (['initial', 'recurring'] as $type) {
                $resolved = $affiliateId
                    ? $this->effectiveRate($affiliateId, (string) $product, $type)
                    : $this->globalRate((string) $product, $type);

                $matrix[] = [
                    'product' => (string) $product,
                    'product_label' => (string) ($definition['label'] ?? ucfirst((string) $product)),
                    'type' => $type,
                    'rate' => (float) $resolved['rate'],
                    'source' => (string) $resolved['source'],
                    'rule_id' => $resolved['rule_id'] ?? null,
                ];
            }
        }

        return $matrix;
    }

    public function globalRate(string $product, string $type): array
    {
        $product = $this->normalizeProduct($product);
        $type = $type === 'recurring' ? 'recurring' : 'initial';
        $lookupProduct = in_array($product, ['vpn', 'antivirus'], true) ? 'vpn' : $product;

        if (Schema::hasTable('affiliate_commission_rules')) {
            $rule = AffiliateCommissionRule::query()
                ->whereNull('affiliate_id')
                ->where('product', $lookupProduct)
                ->where('type', $type)
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if ($rule) {
                return [
                    'rate' => (float) $rule->rate,
                    'source' => 'global',
                    'rule_id' => (int) $rule->id,
                ];
            }
        }

        return [
            'rate' => (float) config("affiliate.products.{$lookupProduct}.rates.{$type}", config("affiliate.fallback_rates.{$type}", 0)),
            'source' => 'program_default',
            'rule_id' => null,
        ];
    }
}
