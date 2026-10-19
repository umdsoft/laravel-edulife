<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

/**
 * The reward and limit values an admin edits on /admin/settings (XP, Coin and
 * Battle tabs), read in one place.
 *
 * Every default is the value the code used before these settings were wired,
 * so a missing setting row keeps today's behaviour. Setting::get() caches each
 * key; the model forgets the key whenever a row is saved, so an edit applies
 * on the next reward.
 */
class RewardSettings
{
    public const DEFAULTS = [
        // XP
        'xp_per_lesson' => 8,          // a text lesson; other lesson types scale from it
        'xp_per_test_pass' => 40,      // a test passed with 90%+; lower bands scale from it
        'xp_per_battle_win' => 100,
        'xp_multiplier_premium' => 1.0,
        'xp_multiplier_vip' => 1.0,
        // Coin
        'coin_per_lesson' => 0,
        'coin_per_test_pass' => 0,
        'coin_per_battle_win' => 10,
        'coin_monthly_limit_trial' => 0,     // 0 = no limit
        'coin_monthly_limit_standart' => 0,
        'coin_monthly_limit_premium' => 0,
        'coin_monthly_limit_vip' => 0,
        // Battle
        'battle_questions_count' => 5,
        'battle_time_per_question' => 20,
        'battle_daily_limit_trial' => 0,     // 0 = no limit
        'battle_daily_limit_standart' => 0,
        'battle_daily_limit_premium' => 0,
        'battle_daily_limit_vip' => 0,
        'elo_k_factor' => 20,                // an established player; new players ×2, 2400+ ×½
    ];

    /** Lesson XP per lesson type, relative to a text lesson (5 / 8 / 15 / 7 at the default 8). */
    private const LESSON_TYPE_RATIO = [
        'video' => 5 / 8,
        'text' => 1.0,
        'practical' => 15 / 8,
        'other' => 7 / 8,
    ];

    /** Test XP per score band, relative to the 90%+ band (40 / 25 / 10 at the default 40). */
    private const TEST_BAND_RATIO = [90 => 1.0, 70 => 25 / 40, 50 => 10 / 40];

    public function int(string $key): int
    {
        return max(0, (int) round((float) Setting::get($key, self::DEFAULTS[$key])));
    }

    public function float(string $key): float
    {
        $value = Setting::get($key, self::DEFAULTS[$key]);

        return is_numeric($value) ? max(0.0, (float) $value) : (float) self::DEFAULTS[$key];
    }

    // ---------------------------------------------------------------- XP

    public function lessonXp(?string $lessonType): int
    {
        $kind = match (strtolower($lessonType ?? 'video')) {
            'video' => 'video',
            'text', 'article', 'reading' => 'text',
            'practical', 'exercise', 'assignment', 'quiz' => 'practical',
            default => 'other',
        };

        return (int) round($this->int('xp_per_lesson') * self::LESSON_TYPE_RATIO[$kind]);
    }

    public function testXp(float $scorePercentage): int
    {
        foreach (self::TEST_BAND_RATIO as $from => $ratio) {
            if ($scorePercentage >= $from) {
                return (int) round($this->int('xp_per_test_pass') * $ratio);
            }
        }

        return 0;
    }

    public function battleWinXp(): int
    {
        return $this->int('xp_per_battle_win');
    }

    /** Premium / VIP subscribers earn more activity XP; everyone else ×1. */
    public function xpMultiplier(User $user): float
    {
        return match ($this->tier($user)) {
            'premium' => $this->float('xp_multiplier_premium') ?: 1.0,
            'vip' => $this->float('xp_multiplier_vip') ?: 1.0,
            default => 1.0,
        };
    }

    // -------------------------------------------------------------- Coin

    public function lessonCoins(): int
    {
        return $this->int('coin_per_lesson');
    }

    /** Coins for a passed test; the same bands as the XP. */
    public function testCoins(float $scorePercentage): int
    {
        foreach (self::TEST_BAND_RATIO as $from => $ratio) {
            if ($scorePercentage >= $from) {
                return (int) round($this->int('coin_per_test_pass') * $ratio);
            }
        }

        return 0;
    }

    public function battleWinCoins(): int
    {
        return $this->int('coin_per_battle_win');
    }

    /** Coins a student may earn from rewards in one calendar month; 0 = no limit. */
    public function monthlyCoinLimit(User $user): int
    {
        return $this->int('coin_monthly_limit_'.$this->tier($user));
    }

    // ------------------------------------------------------------ Battle

    public function battleRounds(): int
    {
        return max(1, $this->int('battle_questions_count'));
    }

    public function battleSecondsPerQuestion(): int
    {
        return max(5, $this->int('battle_time_per_question'));
    }

    /** Battles a student may start in one day; 0 = no limit. */
    public function battleDailyLimit(User $user): int
    {
        return $this->int('battle_daily_limit_'.$this->tier($user));
    }

    public function eloKFactor(): int
    {
        return max(1, $this->int('elo_k_factor'));
    }

    // -------------------------------------------------------------- Tier

    /**
     * trial | standart | premium | vip — from the student's active subscription
     * plan (slugs "standart", "premium-yearly", "vip", ...); no plan = trial.
     */
    public function tier(User $user): string
    {
        $slug = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.plan_id')
            ->orderByDesc('subscriptions.started_at')
            ->value('subscription_plans.slug');

        foreach (['vip', 'premium', 'standart'] as $tier) {
            if ($slug && str_starts_with($slug, $tier)) {
                return $tier;
            }
        }

        return 'trial';
    }
}
