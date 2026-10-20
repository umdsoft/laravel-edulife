<?php

namespace App\Services\Exam\Grading;

/**
 * Canonical spelling of a physical unit so "m/s^2", "m / s²" and "m/s**2"
 * compare equal. Case is kept (mA is not MA, mS is not Ms).
 */
class UnitNormalizer
{
    private const POWERS = [
        '^-1' => '⁻¹', '^-2' => '⁻²', '^-3' => '⁻³',
        '^1' => '', '^2' => '²', '^3' => '³',
        '**2' => '²', '**3' => '³',
    ];

    public static function normalize(?string $unit): string
    {
        if ($unit === null) {
            return '';
        }

        $u = trim($unit);
        $u = preg_replace('/\s+/u', '', $u) ?? '';
        $u = str_replace(['^(-1)', '^(-2)', '^(-3)'], ['^-1', '^-2', '^-3'], $u);
        $u = strtr($u, self::POWERS);
        // multiplication dot variants: N*m, N⋅m, N•m, N.m -> N·m
        $u = preg_replace('/(?<=\pL)[*⋅•.](?=\pL)/u', '·', $u) ?? '';
        // micro sign -> greek mu, ohm spellings
        $u = str_replace(['µ', 'Ohm', 'ohm', 'Om'], ['μ', 'Ω', 'Ω', 'Ω'], $u);
        $u = str_replace(['°С'], ['°C'], $u); // cyrillic Es

        return $u;
    }

    public static function same(?string $a, ?string $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }
}
