<?php

namespace App\Services\Exam\Grading;

/**
 * Turns what a student typed into a number.
 *
 * Accepted: "12", "-3", "0,5", "0.5", ".5", "1/2", "-3/4", "12 500" (spaces
 * as thousand separators), "2e-3", "2,5·10^-3", "2.5*10^(-3)", "2,5×10⁻³",
 * "10^6". A comma is the decimal mark (Uzbek usage); a string with both a
 * comma and a dot, or several commas, is ambiguous and rejected (NULL).
 */
class NumberParser
{
    private const SUPERSCRIPTS = [
        '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4', '⁵' => '5',
        '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9', '⁻' => '-', '⁺' => '+',
    ];

    private const NUM = '[+-]?(?:\d+(?:\.\d+)?|\.\d+)';

    public static function parse(?string $input): ?float
    {
        if ($input === null) {
            return null;
        }

        $s = trim($input);
        if ($s === '' || mb_strlen($s) > 64) {
            return null;
        }

        // unicode minus / dashes, thin and non-breaking spaces
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = preg_replace('/[\s\x{00A0}\x{2009}\x{202F}]+/u', '', $s) ?? '';

        if (str_contains($s, ',') && str_contains($s, '.')) {
            return null;
        }
        if (substr_count($s, ',') > 1) {
            return null;
        }
        $s = str_replace(',', '.', $s);

        // 10⁻³ -> 10^-3
        $s = preg_replace_callback('/[⁰¹²³⁴⁵⁶⁷⁸⁹⁻⁺]+/u', fn ($m) => '^'.strtr($m[0], self::SUPERSCRIPTS), $s) ?? '';
        $s = str_replace(['^^'], '^', $s);

        $num = self::NUM;

        if (preg_match('/^'.$num.'$/', $s)) {
            return (float) $s;
        }

        if (preg_match('/^('.$num.')[eE]([+-]?\d+)$/', $s, $m)) {
            return self::finite((float) $m[1] * 10 ** (int) $m[2]);
        }

        // mantissa · 10^exp   (· * × x ⋅)
        if (preg_match('/^('.$num.')(?:\*|·|×|x|⋅|\x{2219})10\^?\(?([+-]?\d+)\)?$/u', $s, $m)) {
            return self::finite((float) $m[1] * 10 ** (int) $m[2]);
        }

        if (preg_match('/^([+-]?)10\^\(?([+-]?\d+)\)?$/', $s, $m)) {
            return self::finite(($m[1] === '-' ? -1 : 1) * 10 ** (int) $m[2]);
        }

        if (preg_match('/^('.$num.')\/('.$num.')$/', $s, $m)) {
            $den = (float) $m[2];

            return $den == 0.0 ? null : self::finite((float) $m[1] / $den);
        }

        return null;
    }

    private static function finite(float|int $value): ?float
    {
        $value = (float) $value;

        return is_finite($value) ? $value : null;
    }
}
