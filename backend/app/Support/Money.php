<?php

namespace App\Support;

class Money
{
    /** 35000 -> "35,000", 33333.33 -> "33,333.33" */
    public static function fmt(float|int|null $n): string
    {
        $n = round((float) $n, 2);

        return number_format($n, fmod($n, 1) === 0.0 ? 0 : 2);
    }

    /** 35000 -> "Rs 35,000" */
    public static function rs(float|int|null $n): string
    {
        return 'Rs '.self::fmt($n);
    }

    /** "0300-1234567" -> "923001234567" (digits only, country code added) */
    public static function phone(?string $raw, string $country = '92'): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $raw);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '00')) {
            return substr($d, 2);
        }
        if (str_starts_with($d, '0')) {
            return $country.substr($d, 1);
        }

        return $d;
    }
}
