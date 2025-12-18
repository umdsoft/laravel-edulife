<?php

namespace App\Services\English;

use App\Models\User;
use App\Models\English\UserEnglishProfile;
use Illuminate\Support\Facades\Log;

/**
 * Unified Game Scoring Service
 *
 * Bu servis barcha o'yinlar uchun yagona XP va Coin hisoblash tizimini ta'minlaydi.
 * Maksimal coin: 3 (oddiy), 5 (mukammal sessiya)
 * XP: Qiyinlik va natijaga qarab
 */
class GameScoringService
{
    // Coin limiti
    const MAX_COINS_PER_SESSION = 3;
    const MAX_COINS_PERFECT_SESSION = 5;

    // XP bazaviy qiymatlari
    const BASE_XP_PER_CORRECT = 5;
    const BASE_XP_COMPLETION = 10;
    const XP_PERFECT_BONUS = 20;
    const XP_PER_STAR = 5;

    // Qiyinlik multiplikatorlari
    const DIFFICULTY_MULTIPLIERS = [
        'easy' => 1.0,
        'medium' => 1.2,
        'hard' => 1.5,
        'expert' => 2.0,
    ];

    // Star thresholds (accuracy %)
    const STAR_THRESHOLDS = [
        3 => 90,  // 3 star: 90%+
        2 => 70,  // 2 star: 70-89%
        1 => 50,  // 1 star: 50-69%
    ];

    /**
     * O'yin sessiyasini yakunlash va reward hisoblash
     *
     * @param array $sessionData Session ma'lumotlari
     * @param User|null $user Foydalanuvchi
     * @return array Hisoblangan rewards
     */
    public function calculateSessionRewards(array $sessionData, ?User $user = null): array
    {
        $correct = $sessionData['correct'] ?? $sessionData['correct_count'] ?? 0;
        $total = $sessionData['total'] ?? $sessionData['total_count'] ?? 1;
        $difficulty = $sessionData['difficulty'] ?? 'medium';
        $streak = $sessionData['streak'] ?? $sessionData['best_streak'] ?? 0;
        $timeTaken = $sessionData['time_taken'] ?? 0;
        $isPerfect = $sessionData['is_perfect'] ?? ($correct === $total && $total > 0);
        $isFirstCompletion = $sessionData['is_first_completion'] ?? true;

        // Accuracy hisoblash
        $accuracy = $total > 0 ? round(($correct / $total) * 100) : 0;

        // Yulduzlar hisoblash
        $stars = $this->calculateStars($accuracy);

        // XP hisoblash
        $xpEarned = $this->calculateXp($correct, $total, $difficulty, $stars, $streak, $isPerfect, $isFirstCompletion);

        // Coin hisoblash (maksimal 3, perfect uchun 5)
        $coinsEarned = $this->calculateCoins($accuracy, $stars, $isPerfect);

        // Foydalanuvchi profiliga qo'shish
        if ($user) {
            $this->addRewardsToUser($user, $xpEarned, $coinsEarned);
        }

        return [
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'stars' => $stars,
            'accuracy' => $accuracy,
            'correct' => $correct,
            'total' => $total,
            'is_perfect' => $isPerfect,
            'rewards_added' => $user !== null,
        ];
    }

    /**
     * Yulduzlar sonini hisoblash
     */
    public function calculateStars(int $accuracy): int
    {
        foreach (self::STAR_THRESHOLDS as $stars => $threshold) {
            if ($accuracy >= $threshold) {
                return $stars;
            }
        }
        return 0;
    }

    /**
     * XP hisoblash
     */
    public function calculateXp(
        int $correct,
        int $total,
        string $difficulty = 'medium',
        int $stars = 0,
        int $streak = 0,
        bool $isPerfect = false,
        bool $isFirstCompletion = true
    ): int {
        // Birinchi marta tugallanganda to'liq XP, aks holda kamroq
        $completionMultiplier = $isFirstCompletion ? 1.0 : 0.3;

        // Bazaviy XP (to'g'ri javoblar uchun)
        $baseXp = $correct * self::BASE_XP_PER_CORRECT;

        // Tugallash bonusi
        $completionBonus = self::BASE_XP_COMPLETION;

        // Yulduz bonusi
        $starBonus = $stars * self::XP_PER_STAR;

        // Mukammal sessiya bonusi
        $perfectBonus = $isPerfect ? self::XP_PERFECT_BONUS : 0;

        // Streak bonusi (har 5 streak uchun +5 XP, maksimal +25)
        $streakBonus = min(25, floor($streak / 5) * 5);

        // Jami XP
        $totalXp = $baseXp + $completionBonus + $starBonus + $perfectBonus + $streakBonus;

        // Qiyinlik multiplikatori
        $difficultyMultiplier = self::DIFFICULTY_MULTIPLIERS[$difficulty] ?? 1.0;
        $totalXp = (int) round($totalXp * $difficultyMultiplier);

        // Birinchi marta emas bo'lsa kamroq
        $totalXp = (int) round($totalXp * $completionMultiplier);

        return max(1, $totalXp); // Minimal 1 XP
    }

    /**
     * Coin hisoblash (maksimal 3, perfect uchun 5)
     */
    public function calculateCoins(int $accuracy, int $stars, bool $isPerfect = false): int
    {
        // Agar 0 yulduz bo'lsa, coin berilmaydi
        if ($stars === 0) {
            return 0;
        }

        // Bazaviy coin (1 coin har yulduz uchun, maksimal 3)
        $coins = min($stars, self::MAX_COINS_PER_SESSION);

        // Mukammal sessiya uchun qo'shimcha 2 coin (jami 5)
        if ($isPerfect && $accuracy === 100) {
            $coins = self::MAX_COINS_PERFECT_SESSION;
        }

        return $coins;
    }

    /**
     * Foydalanuvchi profiliga XP va Coin qo'shish
     */
    public function addRewardsToUser(User $user, int $xp, int $coins): bool
    {
        try {
            // English Profile
            $englishProfile = $user->englishProfile;
            if ($englishProfile) {
                if ($xp > 0) {
                    $englishProfile->addXp($xp);
                }
                if ($coins > 0) {
                    $englishProfile->addCoins($coins);
                }
            }

            // Agar English Profile yo'q bo'lsa, Student Profile ga qo'shish
            if (!$englishProfile && $user->studentProfile) {
                if ($xp > 0) {
                    $user->studentProfile->addXp($xp);
                }
                if ($coins > 0) {
                    $user->studentProfile->addCoins($coins);
                }
            }

            // Games played sonini oshirish
            if ($englishProfile) {
                $englishProfile->increment('games_played');
            }

            return true;
        } catch (\Exception $e) {
            Log::error('GameScoringService: Failed to add rewards to user', [
                'user_id' => $user->id,
                'xp' => $xp,
                'coins' => $coins,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Javob uchun XP hisoblash (real-time)
     */
    public function calculateAnswerXp(
        bool $isCorrect,
        string $difficulty = 'medium',
        int $currentStreak = 0,
        bool $isFirstTry = true,
        int $responseTimeMs = 0
    ): int {
        if (!$isCorrect) {
            return 0;
        }

        // Bazaviy XP
        $xp = self::BASE_XP_PER_CORRECT;

        // Birinchi urinishda bonus
        if ($isFirstTry) {
            $xp += 2;
        }

        // Tezlik bonusi (5 soniyadan kam bo'lsa)
        if ($responseTimeMs > 0 && $responseTimeMs < 5000) {
            $xp += 2;
        }

        // Streak bonusi (har 3 streak uchun +1)
        $xp += min(5, floor($currentStreak / 3));

        // Qiyinlik multiplikatori
        $multiplier = self::DIFFICULTY_MULTIPLIERS[$difficulty] ?? 1.0;

        return (int) round($xp * $multiplier);
    }

    /**
     * Session statistics uchun helper
     */
    public function getSessionStats(array $sessionData): array
    {
        $correct = $sessionData['correct'] ?? $sessionData['correct_count'] ?? 0;
        $total = $sessionData['total'] ?? $sessionData['total_count'] ?? 1;
        $accuracy = $total > 0 ? round(($correct / $total) * 100) : 0;

        return [
            'correct' => $correct,
            'incorrect' => $total - $correct,
            'total' => $total,
            'accuracy' => $accuracy,
            'stars' => $this->calculateStars($accuracy),
            'is_perfect' => $accuracy === 100 && $total > 0,
            'passed' => $accuracy >= 50,
        ];
    }

    /**
     * Level unlock check
     */
    public function canUnlockNextLevel(int $stars, int $accuracy): bool
    {
        return $stars >= 1 && $accuracy >= 50;
    }

    /**
     * Reward summary yaratish (frontend uchun)
     */
    public function createRewardSummary(array $rewards): array
    {
        $messages = [];

        if ($rewards['xp_earned'] > 0) {
            $messages[] = "+{$rewards['xp_earned']} XP";
        }

        if ($rewards['coins_earned'] > 0) {
            $messages[] = "+{$rewards['coins_earned']} Coin";
        }

        if ($rewards['is_perfect']) {
            $messages[] = "Mukammal natija!";
        }

        return [
            'xp' => $rewards['xp_earned'],
            'coins' => $rewards['coins_earned'],
            'stars' => $rewards['stars'],
            'accuracy' => $rewards['accuracy'],
            'messages' => $messages,
            'star_rating' => str_repeat('⭐', $rewards['stars']),
        ];
    }
}
