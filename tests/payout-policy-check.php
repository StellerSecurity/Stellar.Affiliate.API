<?php

// Dependency-free checks: php tests/payout-policy-check.php
require __DIR__.'/../app/Support/CommissionMath.php';
require __DIR__.'/../app/Support/PayoutPolicy.php';
require __DIR__.'/../app/Support/BankDetails.php';

use App\Support\BankDetails;
use App\Support\PayoutPolicy;

$checks = 0;
$check = function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$start = '2026-09-01';
$check(PayoutPolicy::cutoff($start, new DateTimeImmutable('2026-09-30T23:59:59Z')) === null, 'No round before 30 days.');
$check(PayoutPolicy::cutoff($start, new DateTimeImmutable('2026-10-01T00:00:00Z'))->format('Y-m-d') === '2026-10-01', 'First cutoff is day 30.');
$check(PayoutPolicy::cutoff($start, new DateTimeImmutable('2026-10-31T00:00:00Z'))->format('Y-m-d') === '2026-10-31', 'Rounds are every 30 days, not calendar months.');
$check(PayoutPolicy::cutoff($start, new DateTimeImmutable('2026-11-05T00:00:00Z'))->format('Y-m-d') === '2026-10-31', 'Scheduler recovery uses latest cutoff.');
$check(PayoutPolicy::cutoff($start, new DateTimeImmutable('2026-09-30T20:00:00-04:00'))->format('Y-m-d') === '2026-10-01', 'Cutoffs use UTC.');
foreach (['', '2026-02-30', '2026-9-1'] as $invalid) {
    try {
        PayoutPolicy::cutoff($invalid, new DateTimeImmutable);
        throw new RuntimeException('Invalid start accepted.');
    } catch (InvalidArgumentException) {
        $checks++;
    }
}
$check(PayoutPolicy::micros('99.999999') < 100000000, 'Below threshold must wait.');
$check(PayoutPolicy::micros('100.000000') === 100000000, 'Exact threshold is eligible.');
$carry = 0;
$earned = 0;
$paid = 0;
foreach (['100.005400', '100.009999', '123.999999', '100.000001'] as $amount) {
    $units = PayoutPolicy::micros($amount);
    $sent = PayoutPolicy::micros(PayoutPolicy::transfer($units + $carry));
    $carry += $units - $sent;
    $earned += $units;
    $paid += $sent;
    $check($carry >= 0 && $carry < 10000, 'Carry remains below one cent.');
    $check($earned === $paid + $carry, 'No commission value is lost across payouts.');
}
$check(PayoutPolicy::decimal(-5400) === '-0.005400', 'Negative adjustment retains sign.');
$check(BankDetails::validIban('DE89370400440532013000'), 'Accept known valid IBAN.');
$check(BankDetails::validIban('GB82WEST12345698765432'), 'Accept alphanumeric IBAN.');
$check(! BankDetails::validIban('DE89370400440532013001'), 'Reject checksum corruption.');
$check(! BankDetails::validIban('not-a-bank-account'), 'Reject malformed IBAN.');
$check(BankDetails::normalize('de89 3704 0044 0532 0130 00') === 'DE89370400440532013000', 'Normalize formatted bank input.');
echo "Passed {$checks} payout policy and bank validation checks.\n";
