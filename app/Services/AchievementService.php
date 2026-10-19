<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\CoinTransaction;
use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Unlock every active achievement whose requirements the student now meets.
     *
     * Requirements are stored as {"metric": threshold, ...} (e.g. {"lessons": 1},
     * {"battle_wins": 1}); every listed metric must reach its threshold. The older
     * {"type": "...", "value": n} shape is also understood. Metrics the platform
     * does not track yet never unlock. $triggerType is informational only: the
     * check is cheap and idempotent, so all achievements are evaluated.
     *
     * @return array<Achievement> newly unlocked achievements
     */
    public function checkAchievements(User $user, ?string $triggerType = null, array $data = []): array
    {
        $profile = $user->studentProfile()->first();
        if (!$profile) {
            return [];
        }

        $unlockedIds = UserAchievement::where('user_id', $user->id)->pluck('achievement_id')->all();
        $achievements = Achievement::where('is_active', true)
            ->whereNotIn('id', $unlockedIds)
            ->get();

        $metrics = new class($user, $profile, $data) {
            private array $cache = [];

            public function __construct(private User $user, private $profile, private array $data) {}

            public function get(string $metric): ?float
            {
                if (!array_key_exists($metric, $this->cache)) {
                    $this->cache[$metric] = $this->compute($metric);
                }

                return $this->cache[$metric];
            }

            private function compute(string $metric): ?float
            {
                $p = $this->profile;

                return match ($metric) {
                    'lessons', 'lessons_completed', 'first_lesson' => (float) $p->lessons_completed,
                    'courses', 'courses_completed', 'first_course' => (float) $p->courses_completed,
                    'tests', 'tests_passed' => (float) $p->tests_passed,
                    'perfect_tests' => (float) \App\Models\TestAttempt::where('user_id', $this->user->id)
                        ->where('is_passed', true)->where('score', '>=', 100)->count(),
                    'battle_wins', 'battles_won' => (float) $p->battles_won,
                    'battles', 'battles_played', 'first_battle' => (float) ($p->battles_won + $p->battles_lost + $p->battles_draw),
                    'win_streak' => isset($this->data['win_streak']) ? (float) $this->data['win_streak'] : null,
                    'elo' => (float) $p->elo_rating,
                    'streak', 'streak_days' => (float) max($p->streak_days, $p->longest_streak),
                    'xp', 'xp_earned' => (float) $p->xp,
                    'level', 'level_reached' => (float) $p->level,
                    'coins_earned' => (float) $p->total_coins_earned,
                    'watch_time' => (float) $p->total_watch_time,
                    'reviews' => (float) \App\Models\Review::where('user_id', $this->user->id)->count(),
                    'tournaments' => (float) \App\Models\TournamentParticipant::where('user_id', $this->user->id)->count(),
                    default => null, // not tracked (yet)
                };
            }
        };

        $unlocked = [];
        foreach ($achievements as $achievement) {
            if ($this->requirementsMet((array) $achievement->requirements, $metrics, $profile)) {
                try {
                    $this->unlock($user, $achievement);
                    $unlocked[] = $achievement;
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    // unlocked concurrently by another request
                }
            }
        }

        return $unlocked;
    }

    private function requirementsMet(array $requirements, object $metrics, $profile): bool
    {
        if ($requirements === []) {
            return false;
        }

        // legacy shape: {"type": "lessons_completed", "value": 5}
        if (isset($requirements['type']) && is_string($requirements['type'])) {
            if ($requirements['type'] === 'rank_reached') {
                return $this->isRankHigherOrEqual((string) $profile->rank, (string) ($requirements['value'] ?? ''));
            }
            $requirements = [$requirements['type'] => $requirements['value'] ?? 1];
        }

        foreach ($requirements as $metric => $threshold) {
            if (!is_numeric($threshold)) {
                return false; // e.g. {"beta": true}, {"subscription": "vip"}: not tracked
            }
            $value = $metrics->get((string) $metric);
            if ($value === null || $value < (float) $threshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Unlock achievement for user
     */
    public function unlock(User $user, Achievement $achievement): UserAchievement
    {
        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'earned_at' => now(),
        ]);
        
        // live toast; never let the socket server break the action that unlocked it
        try {
            broadcast(new AchievementUnlocked($user, $achievement))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
        
        return $userAchievement;
    }
    
    /**
     * Claim achievement rewards
     */
    public function claim(User $user, UserAchievement $userAchievement): array
    {
        if ($userAchievement->is_claimed) {
            return ['success' => false, 'message' => 'Already claimed'];
        }
        
        $achievement = $userAchievement->achievement;

        $claimed = DB::transaction(function () use ($user, $userAchievement, $achievement) {
            // Atomically flip the flag so concurrent requests can't claim twice
            $updated = UserAchievement::whereKey($userAchievement->id)
                ->where('is_claimed', false)
                ->update(['is_claimed' => true, 'claimed_at' => now()]);

            if ($updated === 0) {
                return false;
            }

            $profile = $user->studentProfile()->lockForUpdate()->first();

            // Award XP
            if ($achievement->xp_reward > 0) {
                $profile->addXp($achievement->xp_reward);
            }

            // Award coins
            if ($achievement->coin_reward > 0) {
                $profile->addCoins($achievement->coin_reward);

                // Log transaction
                CoinTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'earn',
                    'amount' => $achievement->coin_reward,
                    'balance_after' => $profile->coins,
                    'source' => 'achievement',
                    'description' => "Achievement: {$achievement->title}",
                    'reference_type' => $achievement->getMorphClass(),
                    'reference_id' => $achievement->id,
                ]);
            }

            return true;
        });

        if (!$claimed) {
            return ['success' => false, 'message' => 'Already claimed'];
        }

        return [
            'success' => true,
            'xp_reward' => $achievement->xp_reward,
            'coin_reward' => $achievement->coin_reward,
        ];
    }
    
    private function isRankHigherOrEqual(string $currentRank, string $requiredRank): bool
    {
        $ranks = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'platinum' => 4, 'diamond' => 5, 'master' => 6];
        return ($ranks[$currentRank] ?? 0) >= ($ranks[$requiredRank] ?? 0);
    }
}
