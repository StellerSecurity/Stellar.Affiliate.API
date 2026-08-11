<?php

namespace App\Support;

final class CommissionMath
{
    public const COMMISSION_SCALE = 6;

    /**
     * Calculate an exact commission from a 2-decimal order amount and a 4-decimal rate.
     * The result is returned with exactly six decimal places and is never reduced to cents.
     */
    public static function calculate(string|int|float $orderAmount, string|int|float $rate): string
    {
        $amountMinor = self::toScaledInteger($orderAmount, 2);
        $rateUnits = self::toScaledInteger($rate, 4);

        return self::fromScaledInteger($amountMinor * $rateUnits, self::COMMISSION_SCALE);
    }

    public static function display(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $normalized = number_format((float) $value, self::COMMISSION_SCALE, '.', ',');
        $normalized = rtrim($normalized, '0');
        $normalized = rtrim($normalized, '.');

        return $normalized === '-0' ? '0' : $normalized;
    }

    private static function toScaledInteger(string|int|float $value, int $scale): int
    {
        $raw = is_float($value)
            ? number_format($value, $scale, '.', '')
            : trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        $negative = str_starts_with($raw, '-');
        if ($negative || str_starts_with($raw, '+')) {
            $raw = substr($raw, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '');
        $whole = preg_replace('/\D/', '', $whole) ?: '0';
        $fraction = preg_replace('/\D/', '', $fraction) ?: '';

        // Monetary order values and commission rates are schema-bounded to 2 and 4 decimals.
        // Extra digits are not used to avoid silently changing the persisted source precision.
        $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);

        $integer = ((int) $whole * (10 ** $scale)) + (int) $fraction;

        return $negative ? -$integer : $integer;
    }

    private static function fromScaledInteger(int $value, int $scale): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $divisor = 10 ** $scale;
        $whole = intdiv($absolute, $divisor);
        $fraction = $absolute % $divisor;

        return ($negative ? '-' : '')
            . $whole
            . '.'
            . str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT);
    }
}
