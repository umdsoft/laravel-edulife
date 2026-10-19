<?php

namespace App\Enums\Exam;

/**
 * How a numeric answer tolerance is interpreted.
 *  abs: |given - expected| <= tolerance
 *  rel: |given - expected| <= tolerance * |expected|
 */
enum ToleranceType: string
{
    case Absolute = 'abs';
    case Relative = 'rel';

    public function accepts(float $expected, float $given, float $tolerance): bool
    {
        $diff = abs($given - $expected);
        // Tiny epsilon absorbs binary floating point noise (0.1 + 0.2).
        $eps = 1e-9 * max(1.0, abs($expected));

        return match ($this) {
            self::Absolute => $diff <= $tolerance + $eps,
            self::Relative => $diff <= $tolerance * abs($expected) + $eps,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
