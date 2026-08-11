<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use StellarSecurity\CommerceLaravel\Contracts\CommerceClientContract;
use Throwable;
use UnexpectedValueException;

class AffiliateOrderService
{
    public function __construct(
        private readonly CommerceClientContract $commerce,
    ) {
    }

    /**
     * Fetch an order from Stellar Commerce and expose only the fields an affiliate needs.
     * Buyer references, user IDs, shipping data, event payloads and arbitrary metadata are
     * deliberately excluded from the returned structure.
     */
    public function getAffiliateOrder(string $orderId): array
    {
        $order = $this->fetchOrderPayload($orderId);

        $items = $order['items'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        return [
            'id' => (string) $order['id'],
            'status' => $this->stringOrNull($order['status'] ?? null),
            'currency' => strtoupper($this->stringOrNull($order['currency'] ?? null) ?: 'EUR'),
            'created_at' => $this->formatDate($order['created_at'] ?? null),
            'updated_at' => $this->formatDate($order['updated_at'] ?? null),
            'subtotal_cents' => $this->intValue($order['subtotal_cents'] ?? 0),
            'discount_cents' => $this->intValue($order['discount_cents'] ?? 0),
            'tax_cents' => $this->intValue($order['tax_cents'] ?? 0),
            'shipping_cents' => $this->intValue($order['shipping_cents'] ?? 0),
            'grand_total_cents' => $this->intValue($order['grand_total_cents'] ?? 0),
            'items' => array_values(array_map(
                fn (array $item): array => $this->sanitizeItem($item),
                array_filter($items, 'is_array'),
            )),
        ];
    }

    /**
     * Return the authoritative monetary basis for affiliate commission calculation.
     * Commerce grand_total_cents is the source of truth, so discounts, tax and shipping
     * are reflected exactly as they are on the final order.
     */
    public function getCommissionTotal(string $orderId): array
    {
        $order = $this->fetchOrderPayload($orderId);

        if (! array_key_exists('grand_total_cents', $order) || ! is_numeric($order['grand_total_cents'])) {
            throw new UnexpectedValueException('Commerce order is missing grand_total_cents.');
        }

        $grandTotalCents = (int) $order['grand_total_cents'];
        if ($grandTotalCents < 0) {
            throw new UnexpectedValueException('Commerce order returned a negative grand total.');
        }

        $currency = strtoupper($this->stringOrNull($order['currency'] ?? null) ?: 'EUR');
        if (strlen($currency) !== 3) {
            throw new UnexpectedValueException('Commerce order returned an invalid currency.');
        }

        return [
            'id' => (string) $order['id'],
            'currency' => $currency,
            'grand_total_cents' => $grandTotalCents,
        ];
    }

    private function fetchOrderPayload(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new UnexpectedValueException('Order ID is required.');
        }

        $response = $this->commerce->getOrder(
            $orderId,
            (string) Str::uuid(),
        );

        $order = $response['data'] ?? $response;

        if (! is_array($order) || $order === []) {
            throw new UnexpectedValueException('Commerce returned an invalid order payload.');
        }

        $returnedOrderId = $this->stringOrNull($order['id'] ?? null);
        if ($returnedOrderId === null || ! hash_equals($orderId, $returnedOrderId)) {
            throw new UnexpectedValueException('Commerce returned an unexpected order identifier.');
        }

        $order['id'] = $returnedOrderId;

        return $order;
    }

    private function sanitizeItem(array $item): array
    {
        $quantity = max(0, $this->intValue($item['qty'] ?? 0));
        $unitPrice = $this->intValue($item['unit_price_cents'] ?? 0);
        $lineTotal = array_key_exists('line_total_cents', $item)
            ? $this->intValue($item['line_total_cents'])
            : $unitPrice * $quantity;

        return [
            'name' => $this->stringOrNull($item['name'] ?? null) ?: 'Product',
            'sku' => $this->stringOrNull($item['sku'] ?? null),
            'qty' => $quantity,
            'unit_price_cents' => $unitPrice,
            'line_total_cents' => $lineTotal,
        ];
    }

    private function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function formatDate(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('M j, Y · H:i');
        } catch (Throwable) {
            return null;
        }
    }
}
