<?php

namespace App\Support;

class MoneyNormalizer
{
    private const int SCALE = 2;

    /**
     * Normalizes any monetary input to a canonical decimal string "12345.67"
     */
    public static function normalize(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $raw = preg_replace('/\s+/u', '', (string) $value);

        if (str_contains($raw, '.') && str_contains($raw, ',')) {
            $posDot = strpos($raw, '.');
            $posComma = strpos($raw, ',');
            $raw = ($posDot < $posComma)
                ? str_replace('.', '', $raw)
                : str_replace(',', '', $raw);
        }

        $normalized = str_replace(',', '.', $raw);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        // bcadd with 0 normalizes, then format to exactly 2 decimals
        return bcadd($normalized, '0', self::SCALE);
    }

    /**
     * Sums monetary values without floating-point precision loss
     */
    public static function sum(mixed ...$values): string
    {
        $total = '0';
        foreach ($values as $value) {
            $total = bcadd($total, self::normalize($value), self::SCALE);
        }
        return $total;
    }
}
