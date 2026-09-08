<?php

namespace App\Support;

final class BankDetails
{
    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', $value));
    }

    public static function validIban(string $iban): bool
    {
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/D', $iban)) {
            return false;
        }
        $reordered = substr($iban, 4).substr($iban, 0, 4);
        $remainder = 0;
        foreach (str_split($reordered) as $character) {
            $digits = ctype_digit($character) ? $character : (string) (ord($character) - 55);
            foreach (str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }
}
