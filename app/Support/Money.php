<?php
namespace App\Support;
use InvalidArgumentException;
final class Money
{
    public static function toCents(int|string $amount): int
    {
        $normalized = str_replace([',', '$', ' '], '', (string) $amount);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) { throw new InvalidArgumentException('Money must have no more than two decimal places.'); }
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }
    public static function format(int $cents): string { return '$'.number_format($cents / 100, 2); }
}