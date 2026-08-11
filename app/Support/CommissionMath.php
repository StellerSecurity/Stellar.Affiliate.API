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

    /**
     * Convert integer minor units such as cents into a fixed 2-decimal money string.
     */
    public static function fromCents(int $cents): string
    {
        return self::fromScaledInteger($cents, 2);
    }

    /**
     * Normalize a money value to the persisted 2-decimal order amount precision.
     */
    public static function money(string|int|float $value): string
    {
        return self::fromScaledInteger(self::toScaledInteger($value, 2), 2);
    }

    /**
     * Normalize a persisted commission value to six decimal places.
     */
    public static function commission(string|int|float $value): string
    {
        return self::fromScaledInteger(self::toScaledInteger($value, self::COMMISSION_SCALE), self::COMMISSION_SCALE);
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
