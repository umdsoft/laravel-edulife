<?php

namespace Database\Seeders\Exam\Support;

/**
 * Small numeric toolkit used by the demo question data to COMPUTE answers
 * independently of the hand-written expected value (roots by the quadratic
 * formula, derivatives by central differences, integrals by Simpson's rule,
 * probabilities by enumeration, ...). DemoQuestionVerifier compares both.
 */
final class DemoMath
{
    /** Real roots of ax^2 + bx + c = 0, ascending. */
    public static function quadRoots(float $a, float $b, float $c): array
    {
        $d = $b * $b - 4 * $a * $c;
        if ($d < 0) {
            return [];
        }
        $r = [(-$b - sqrt($d)) / (2 * $a), (-$b + sqrt($d)) / (2 * $a)];
        sort($r);

        return $d == 0 ? [$r[0]] : $r;
    }

    public static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a);
    }

    public static function lcm(int $a, int $b): int
    {
        return intdiv(abs($a * $b), self::gcd($a, $b));
    }

    public static function fact(int $n): int
    {
        return $n <= 1 ? 1 : $n * self::fact($n - 1);
    }

    public static function comb(int $n, int $k): int
    {
        return intdiv(self::fact($n), self::fact($k) * self::fact($n - $k));
    }

    /** Central difference derivative. */
    public static function derivative(callable $f, float $x, float $h = 1e-5): float
    {
        return ($f($x + $h) - $f($x - $h)) / (2 * $h);
    }

    /** Composite Simpson rule. */
    public static function integrate(callable $f, float $a, float $b, int $n = 1000): float
    {
        $h = ($b - $a) / $n;
        $sum = $f($a) + $f($b);
        for ($i = 1; $i < $n; $i++) {
            $sum += ($i % 2 ? 4 : 2) * $f($a + $i * $h);
        }

        return $sum * $h / 3;
    }

    /** Minimum of f on [a, b] by a fine grid. */
    public static function minOn(callable $f, float $a, float $b, int $steps = 20000): float
    {
        $min = INF;
        for ($i = 0; $i <= $steps; $i++) {
            $min = min($min, $f($a + ($b - $a) * $i / $steps));
        }

        return $min;
    }

    /** Count integers n in [from, to] with $test(n) true. */
    public static function countIntegers(callable $test, int $from, int $to): int
    {
        $count = 0;
        for ($n = $from; $n <= $to; $n++) {
            if ($test($n)) {
                $count++;
            }
        }

        return $count;
    }

    /** Solve a1 x + b1 y = c1, a2 x + b2 y = c2 by Cramer's rule. */
    public static function cramer(float $a1, float $b1, float $c1, float $a2, float $b2, float $c2): array
    {
        $det = $a1 * $b2 - $a2 * $b1;

        return [($c1 * $b2 - $c2 * $b1) / $det, ($a1 * $c2 - $a2 * $c1) / $det];
    }

    public static function sinDeg(float $deg): float
    {
        return sin(deg2rad($deg));
    }

    public static function cosDeg(float $deg): float
    {
        return cos(deg2rad($deg));
    }

    public static function tanDeg(float $deg): float
    {
        return tan(deg2rad($deg));
    }
}
