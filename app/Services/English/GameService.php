<?php

namespace App\Services\English;

use App\Models\English\EnglishGame;
use App\Models\English\EnglishGameCategory;
use App\Models\English\EnglishGameContent;
use App\Models\English\EnglishGameLevel;
use App\Models\English\UserGameAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GameService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get all game categories with games
     */
    public function getGameCategories(): Collection
    {
        return EnglishGameCategory::with(['games' => fn($q) => $q->where('is_active', true)->orderBy('order_number')])
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get();
    }

    /**
     * Get games by category
     */
    public function getGamesByCategory(string $categoryId, User $user): Collection
    {
        return EnglishGame::with('category')
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->map(function ($game) use ($user) {
                $game->user_stats = $this->getUserGameStats($game, $user);
                return $game;
            });
    }

    /**
     * Get game with levels
     */
    public function getGameWithLevels(string $gameId, User $user): ?EnglishGame
    {
        $game = EnglishGame::with([
            'category',
            'levels' => fn($q) => $q->where('is_active', true)->orderBy('difficulty_order'),
        ])->find($gameId);

        if (!$game)
            return null;

        $game->user_stats = $this->getUserGameStats($game, $user);
        $game->levels->each(function ($level) use ($user, $game) {
            $level->user_best = $this->getUserLevelBest($game, $level, $user);
            $level->is_unlocked = $this->isLevelUnlocked($level, $user, $game);
        });

        return $game;
    }

    /**
     * Get game content/questions
     */
    public function getGameContent(EnglishGame $game, EnglishGameLevel $level, User $user): Collection
    {
        $profile = $this->levelService->getOrCreateProfile($user);
        $userLevel = $profile->currentLevel;

        $questionsCount = $game->config['questions_per_round'] ?? 10;

        return EnglishGameContent::where('game_id', $game->id)
            ->where('difficulty', $level->difficulty)
            ->where(function ($q) use ($userLevel) {
                $q->whereNull('level_id')
                    ->orWhere('level_id', $userLevel?->id);
            })
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($questionsCount)
            ->get();
    }

    /**
     * Start game session
     */
    public function startGame(EnglishGame $game, EnglishGameLevel $level, User $user): UserGameAttempt
    {
        return UserGameAttempt::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'game_id' => $game->id,
            'game_level_id' => $level->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Submit game results
     */
    public function submitGameResults(
        UserGameAttempt $attempt,
        array $answers,
        int $score,
        int $correctCount,
        int $totalCount,
        int $timeSpentSeconds
    ): array {
        $game = $attempt->game;
        $level = $attempt->gameLevel;
        $user = $attempt->user;
        $profile = $this->levelService->getOrCreateProfile($user);

        $percentage = $totalCount > 0 ? round(($correctCount / $totalCount) * 100, 2) : 0;
        $stars = $this->calculateStars($percentage, $level->pass_percentage ?? 60);
        $passed = $percentage >= ($level->pass_percentage ?? 60);

        $previousBest = UserGameAttempt::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('game_level_id', $level->id)
            ->where('status', 'completed')
            ->max('score');
        $isHighScore = $score > ($previousBest ?? 0);

        $xpEarned = $this->calculateGameXp($game, $level, $stars, $isHighScore);
        $coinsEarned = $passed ? ($stars * 5) : 0;

        $attempt->update([
            'score' => $score,
            'percentage' => $percentage,
            'stars_earned' => $stars,
            'correct_count' => $correctCount,
            'total_count' => $totalCount,
            'time_spent_seconds' => $timeSpentSeconds,
            'answers' => $answers,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_high_score' => $isHighScore,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $profile->addXp($xpEarned);
        if ($coinsEarned > 0) {
            $profile->addCoins($coinsEarned);
        }

        $game->times_played = ($game->times_played ?? 0) + 1;
        $game->save();

        return [
            'score' => $score,
            'percentage' => $percentage,
            'stars' => $stars,
            'passed' => $passed,
            'is_high_score' => $isHighScore,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
        ];
    }

    private function calculateStars(float $percentage, int $passPercentage): int
    {
        return match (true) {
            $percentage >= 95 => 3,
            $percentage >= 80 => 2,
            $percentage >= $passPercentage => 1,
            default => 0,
        };
    }

    private function calculateGameXp(EnglishGame $game, EnglishGameLevel $level, int $stars, bool $isHighScore): int
    {
        $baseXp = $game->xp_reward ?? 20;

        $difficultyMultiplier = match ($level->difficulty ?? 'easy') {
            'hard' => 1.5,
            'medium' => 1.2,
            default => 1.0,
        };

        $starMultiplier = match ($stars) {
            3 => 1.5, 2 => 1.2, 1 => 1.0, default => 0.5,
        };

        $highScoreBonus = $isHighScore ? 1.25 : 1.0;

        return (int) round($baseXp * $difficultyMultiplier * $starMultiplier * $highScoreBonus);
    }

    private function getUserGameStats(EnglishGame $game, User $user): array
    {
        $attempts = UserGameAttempt::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('status', 'completed')
            ->get();

        return [
            'times_played' => $attempts->count(),
            'best_score' => $attempts->max('score') ?? 0,
            'best_stars' => $attempts->max('stars_earned') ?? 0,
            'average_score' => round($attempts->avg('score') ?? 0),
            'total_xp_earned' => $attempts->sum('xp_earned'),
        ];
    }

    private function getUserLevelBest(EnglishGame $game, EnglishGameLevel $level, User $user): ?UserGameAttempt
    {
        return UserGameAttempt::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('game_level_id', $level->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->first();
    }

    private function isLevelUnlocked(EnglishGameLevel $level, User $user, EnglishGame $game): bool
    {
        if (($level->difficulty_order ?? 1) === 1)
            return true;

        $previousLevel = EnglishGameLevel::where('game_id', $game->id)
            ->where('difficulty_order', ($level->difficulty_order ?? 1) - 1)
            ->first();

        if (!$previousLevel)
            return true;

        $best = $this->getUserLevelBest($game, $previousLevel, $user);
        return $best && ($best->stars_earned ?? 0) >= 1;
    }
}
