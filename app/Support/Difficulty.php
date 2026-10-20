<?php

namespace App\Support;

/**
 * The one difficulty scale of the platform (docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §2.3).
 *
 *  - exam bank (question_bank.difficulty_level): levels 1..5;
 *  - course tests and battles (questions.difficulty): bands easy | medium | hard;
 *  - mapping: 1–2 = easy, 3 = medium, 4–5 = hard (both directions).
 *
 * The JS twin is resources/js/Utils/difficulty.js; keep the two in step.
 */
final class Difficulty
{
    public const MIN = 1;

    public const MAX = 5;

    /** @var list<int> */
    public const LEVELS = [1, 2, 3, 4, 5];

    public const EASY = 'easy';

    public const MEDIUM = 'medium';

    public const HARD = 'hard';

    /** @var list<string> */
    public const BANDS = [self::EASY, self::MEDIUM, self::HARD];

    /** Labels of the 1..5 scale, shown everywhere the same way. */
    public const LEVEL_LABELS = [
        1 => '1 — juda oson',
        2 => '2 — oson',
        3 => "3 — o'rta",
        4 => '4 — qiyin',
        5 => '5 — juda qiyin',
    ];

    /** Short names of the levels ("juda oson" …). */
    public const LEVEL_NAMES = [
        1 => 'juda oson',
        2 => 'oson',
        3 => "o'rta",
        4 => 'qiyin',
        5 => 'juda qiyin',
    ];

    public const BAND_LABELS = [
        self::EASY => 'Oson',
        self::MEDIUM => "O'rta",
        self::HARD => 'Qiyin',
    ];

    /** Bank levels of each band. */
    public const BAND_LEVELS = [
        self::EASY => [1, 2],
        self::MEDIUM => [3],
        self::HARD => [4, 5],
    ];

    /** The bank level a band question gets when it has to be put on the 1..5 scale. */
    public const BAND_DEFAULT_LEVEL = [
        self::EASY => 2,
        self::MEDIUM => 3,
        self::HARD => 4,
    ];

    public static function isLevel(mixed $value): bool
    {
        if (is_string($value) && ctype_digit(trim($value))) {
            $value = (int) trim($value);
        }

        return is_int($value) && $value >= self::MIN && $value <= self::MAX;
    }

    public static function isBand(mixed $value): bool
    {
        return is_string($value) && in_array($value, self::BANDS, true);
    }

    /** 1..5 from user input ("3", 3, " 4 "); null when it is not a level. */
    public static function parseLevel(mixed $value): ?int
    {
        return self::isLevel($value) ? (int) (is_string($value) ? trim($value) : $value) : null;
    }

    /** easy | medium | hard of a bank level. */
    public static function bandOf(?int $level): ?string
    {
        return match (true) {
            $level === null => null,
            $level <= 2 && $level >= 1 => self::EASY,
            $level === 3 => self::MEDIUM,
            $level >= 4 && $level <= 5 => self::HARD,
            default => null,
        };
    }

    /** @return list<int> bank levels of a band */
    public static function levelsOf(string $band): array
    {
        return self::BAND_LEVELS[$band] ?? [];
    }

    public static function defaultLevelOf(string $band): ?int
    {
        return self::BAND_DEFAULT_LEVEL[$band] ?? null;
    }

    public static function label(?int $level): ?string
    {
        return $level === null ? null : (self::LEVEL_LABELS[$level] ?? null);
    }

    public static function bandLabel(?string $band): ?string
    {
        return $band === null ? null : (self::BAND_LABELS[$band] ?? null);
    }

    /**
     * The olympiad-era string column question_bank.difficulty (easy | medium | hard | expert).
     * Kept for the olympiad module; the exam reads difficulty_level.
     */
    public static function legacyBankValue(?int $level): string
    {
        return match ($level) {
            1, 2 => 'easy',
            4 => 'hard',
            5 => 'expert',
            default => 'medium',
        };
    }

    /** [{value, label}] for selects. */
    public static function levelOptions(): array
    {
        return array_map(fn (int $l) => ['value' => $l, 'label' => self::LEVEL_LABELS[$l]], self::LEVELS);
    }

    /** [{value, label}] for selects. */
    public static function bandOptions(): array
    {
        return array_map(fn (string $b) => ['value' => $b, 'label' => self::BAND_LABELS[$b]], self::BANDS);
    }

    /**
     * The band a solve rate points at, with the battle's thresholds
     * (docs/battle/SCORING.md: p ≥ easy_min_p → easy, p < hard_max_p → hard).
     */
    public static function bandForSolveRate(float $p, float $easyMinP = 0.70, float $hardMaxP = 0.40): string
    {
        if ($p >= $easyMinP) {
            return self::EASY;
        }

        return $p >= $hardMaxP ? self::MEDIUM : self::HARD;
    }

    /** A 1..5 suggestion from a solve rate (inside the band: the outer level beyond 85% / below 20%). */
    public static function levelForSolveRate(float $p, float $easyMinP = 0.70, float $hardMaxP = 0.40): int
    {
        return match (self::bandForSolveRate($p, $easyMinP, $hardMaxP)) {
            self::EASY => $p >= 0.85 ? 1 : 2,
            self::MEDIUM => 3,
            default => $p < 0.20 ? 5 : 4,
        };
    }
}
