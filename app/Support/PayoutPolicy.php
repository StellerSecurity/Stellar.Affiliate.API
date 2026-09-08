<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class PayoutPolicy
{
    public static function cutoff(string $start, DateTimeImmutable $now): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $start, new DateTimeZone('UTC'));
        if (! $date || $date->format('Y-m-d') !== $start) {
            throw new InvalidArgumentException('Set AFFILIATE_PAYOUT_STARTS_ON to a valid YYYY-MM-DD UTC date.');
        }
        $elapsed = $now->getTimestamp() - $date->getTimestamp();
        $periods = intdiv(max(0, $elapsed), 30 * 86400);

        return $periods < 1 ? null : $date->modify('+'.($periods * 30).' days');
    }

    public static function micros(string $amount): int
    {
        if (! preg_match('/^(-?)(\d{1,12})(?:\.(\d{1,6}))?$/D', $amount, $parts)) {
            throw new InvalidArgumentException('Invalid commission amount.');
        }
        $units = ((int) $parts[2] * 1000000) + (int) str_pad($parts[3] ?? '', 6, '0');

        return $parts[1] === '-' ? -$units : $units;
    }

    public static function decimal(int $micros): string
    {
        return ($micros < 0 ? '-' : '').intdiv(abs($micros), 1000000).'.'.str_pad((string) (abs($micros) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function transfer(int $micros): string
    {
        if ($micros < 0) {
            throw new InvalidArgumentException('A payout cannot be negative.');
        }

        return CommissionMath::fromCents(intdiv($micros, 10000));
    }
}
