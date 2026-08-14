<?php

namespace App\Support;

class MoneyNormalizer
{
    /**
     * Converts any incoming string representing an amount of money into cents (int)
     */
    public static function toCents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $raw = preg_replace('/\s+/u', '', (string) $value);

        if (str_contains($raw, '.') && str_contains($raw, ',')) {
            $posDot = strpos($raw, '.');
            $posComma = strpos($raw, ',');

            if ($posDot < $posComma) {
                $raw = str_replace('.', '', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        }

        $normalized = str_replace(',', '.', $raw);
        $normalized = preg_replace('/[^0-9.-]/', '', $normalized);

        return (int) round(((float) $normalized) * 100);
    }

    /**
     * Converts cents (int) to the formatted string "12345.67"
     */
    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Normalizes and formats any monetary value
     */
    public static function format(mixed $value): string
    {
        return self::fromCents(self::toCents($value));
    }

    /**
     * Sums any number of monetary values without losing any cents
     */
    public static function sum(mixed ...$values): string
    {
        $totalCents = 0;
        foreach ($values as $value) {
            $totalCents += self::toCents($value);
        }

        return self::fromCents($totalCents);
    }
}
