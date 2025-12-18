<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TrueFalseService
{
    protected TrueFalseDataService $dataService;
    protected GameScoringService $scoringService;
    protected array $sessions = [];

    public function __construct(TrueFalseDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
    }

    /**
     * Start a new game session
     */
    public function startSession(int $levelNumber, string $mode = 'classic'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $statements = $this->dataService->getStatementsForLevel($levelNumber, $mode);
        if (empty($statements)) {
            throw new \Exception('No statements available for this level');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $modeConfig = null;

        foreach ($gameModes as $gm) {
            if ($gm['id'] === $mode) {
                $modeConfig = $gm;
                break;
            }
        }

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id(),
            'level' => $levelNumber,
            'mode' => $mode,
            'mode_config' => $modeConfig,
            'statements' => $statements,
            'current_index' => 0,
            'score' => 0,
            'correct_count' => 0,
            'wrong_count' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'answers' => [],
            'used_powerups' => [],
            'double_points_active' => false,
            'extra_lives' => 0,
            'started_at' => now()->toIso8601String(),
            'category_stats' => [],
            'fast_answers' => 0,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $mode,
            'mode_config' => $modeConfig,
            'total_statements' => count($statements),
            'current_statement' => $this->formatStatement($statements[0]),
            'current_index' => 0,
        ];
    }

    /**
     * Format statement for frontend
     */
    protected function formatStatement(array $statement): array
    {
        return [
            'id' => $statement['id'],
            'statement' => $statement['statement'],
            'statement_uz' => $statement['statement_uz'] ?? null,
            'difficulty' => $statement['difficulty'] ?? 'beginner',
        ];
    }

    /**
     * Check an answer
     */
    public function checkAnswer(string $sessionId, string $statementId, bool $userAnswer, float $answerTime): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $currentStatement = $session['statements'][$session['current_index']] ?? null;
        if (!$currentStatement || $currentStatement['id'] !== $statementId) {
            throw new \Exception('Invalid statement');
        }

        $isCorrect = $currentStatement['answer'] === $userAnswer;
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        // Calculate points
        $points = 0;
        if ($isCorrect) {
            $points = $this->calculatePoints($session, $answerTime, $scoring);

            if ($session['double_points_active']) {
                $points *= 2;
                $session['double_points_active'] = false;
            }

            $session['correct_count']++;
            $session['streak']++;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }

            // Track fast answers
            $fastThreshold = $scoring['speed_thresholds']['fast'] ?? 3;
            if ($answerTime <= $fastThreshold) {
                $session['fast_answers']++;
            }

            // Track category stats
            $category = $this->getStatementCategory($currentStatement);
            if (!isset($session['category_stats'][$category])) {
                $session['category_stats'][$category] = ['correct' => 0, 'total' => 0];
            }
            $session['category_stats'][$category]['correct']++;
            $session['category_stats'][$category]['total']++;
        } else {
            $session['wrong_count']++;
            $session['streak'] = 0;

            // Track category stats
            $category = $this->getStatementCategory($currentStatement);
            if (!isset($session['category_stats'][$category])) {
                $session['category_stats'][$category] = ['correct' => 0, 'total' => 0];
            }
            $session['category_stats'][$category]['total']++;

            // Check survival mode
            $modeConfig = $session['mode_config'] ?? [];
            $maxMistakes = $modeConfig['max_mistakes'] ?? null;

            if ($maxMistakes !== null) {
                $totalMistakes = $session['wrong_count'];
                $allowedMistakes = $maxMistakes + $session['extra_lives'];

                if ($totalMistakes >= $allowedMistakes) {
                    $session['game_over'] = true;
                }
            }
        }

        $session['score'] += $points;
        $session['answers'][] = [
            'statement_id' => $statementId,
            'user_answer' => $userAnswer,
            'correct_answer' => $currentStatement['answer'],
            'is_correct' => $isCorrect,
            'time' => $answerTime,
            'points' => $points,
        ];

        $session['current_index']++;
        $this->saveSession($session);

        $response = [
            'is_correct' => $isCorrect,
            'correct_answer' => $currentStatement['answer'],
            'explanation' => $currentStatement['explanation'] ?? null,
            'explanation_uz' => $currentStatement['explanation_uz'] ?? null,
            'points_earned' => $points,
            'total_score' => $session['score'],
            'streak' => $session['streak'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
        ];

        // Check if game is over
        if ($session['game_over'] ?? false) {
            $response['game_over'] = true;
            $response['reason'] = 'max_mistakes';
        } elseif ($session['current_index'] >= count($session['statements'])) {
            $response['round_complete'] = true;
        } else {
            $response['next_statement'] = $this->formatStatement($session['statements'][$session['current_index']]);
            $response['current_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Get statement category
     */
    protected function getStatementCategory(array $statement): string
    {
        $id = $statement['id'] ?? '';

        if (str_starts_with($id, 'g')) return 'grammar';
        if (str_starts_with($id, 'v')) return 'vocabulary';
        if (str_starts_with($id, 'c')) return 'culture';
        if (str_starts_with($id, 'p')) return 'pronunciation';
        if (str_starts_with($id, 's')) return 'spelling';

        return 'mixed';
    }

    /**
     * Calculate points for correct answer
     */
    protected function calculatePoints(array $session, float $answerTime, array $scoring): int
    {
        $basePoints = $scoring['base_points'] ?? 100;
        $points = $basePoints;

        // Speed bonus
        $speedThresholds = $scoring['speed_thresholds'] ?? [];
        $speedBonuses = $scoring['speed_bonuses'] ?? [];

        if ($answerTime <= ($speedThresholds['fast'] ?? 3)) {
            $points += $speedBonuses['fast'] ?? 50;
        } elseif ($answerTime <= ($speedThresholds['medium'] ?? 6)) {
            $points += $speedBonuses['medium'] ?? 25;
        }

        // Time bonus (for timed mode)
        if (($session['mode_config']['time_per_statement'] ?? null) !== null) {
            $timeLimit = $session['mode_config']['time_per_statement'];
            $remainingRatio = max(0, ($timeLimit - $answerTime) / $timeLimit);
            $maxTimeBonus = $scoring['time_bonus_max'] ?? 50;
            $points += (int)($remainingRatio * $maxTimeBonus);
        }

        // Streak multiplier
        $streak = $session['streak'];
        $streakMultipliers = $scoring['streak_multipliers'] ?? [];

        $multiplier = 1.0;
        foreach ($streakMultipliers as $threshold => $mult) {
            if ($streak >= (int)$threshold) {
                $multiplier = $mult;
            }
        }
        $points = (int)($points * $multiplier);

        // Mode bonus multiplier
        $modeMultiplier = $session['mode_config']['bonus_multiplier'] ?? 1.0;
        $points = (int)($points * $modeMultiplier);

        return $points;
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $powerups = $this->dataService->getPowerups();
        $powerup = null;

        foreach ($powerups as $p) {
            if ($p['id'] === $powerupId) {
                $powerup = $p;
                break;
            }
        }

        if (!$powerup) {
            throw new \Exception('Powerup not found');
        }

        // Check usage limit
        $usedCount = count(array_filter($session['used_powerups'], fn($id) => $id === $powerupId));
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup limit reached');
        }

        $session['used_powerups'][] = $powerupId;
        $result = ['powerup_used' => $powerupId];

        switch ($powerupId) {
            case 'fifty_fifty':
                $currentStatement = $session['statements'][$session['current_index']] ?? null;
                if ($currentStatement) {
                    $hint = $currentStatement['answer']
                        ? 'This statement is more likely to be TRUE'
                        : 'This statement is more likely to be FALSE';
                    $result['hint'] = $hint;
                }
                break;

            case 'skip':
                $session['current_index']++;
                if ($session['current_index'] < count($session['statements'])) {
                    $result['next_statement'] = $this->formatStatement($session['statements'][$session['current_index']]);
                    $result['current_index'] = $session['current_index'];
                } else {
                    $result['round_complete'] = true;
                }
                break;

            case 'extra_time':
                $result['extra_time'] = 10;
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $result['double_points_active'] = true;
                break;

            case 'extra_life':
                $session['extra_lives']++;
                $result['extra_lives'] = $session['extra_lives'];
                break;
        }

        $this->saveSession($session);
        return $result;
    }

    /**
     * Complete a session
     */
    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $config = $this->dataService->getConfig();
        $level = $this->dataService->getLevel($session['level']);

        $totalAnswered = $session['correct_count'] + $session['wrong_count'];
        $accuracy = $totalAnswered > 0 ? round(($session['correct_count'] / $totalAnswered) * 100) : 0;
        $isPerfect = $session['wrong_count'] === 0 && $session['correct_count'] > 0;

        // Calculate stars
        $starThresholds = $level['star_thresholds'] ?? ['one' => 50, 'two' => 70, 'three' => 90];
        $stars = 0;
        if ($accuracy >= $starThresholds['three']) {
            $stars = 3;
        } elseif ($accuracy >= $starThresholds['two']) {
            $stars = 2;
        } elseif ($accuracy >= $starThresholds['one']) {
            $stars = 1;
        }

        // Calculate XP
        $xpRewards = $config['xp_rewards'] ?? [];
        $xpEarned = ($session['correct_count'] * ($xpRewards['per_correct'] ?? 10));
        $xpEarned += $xpRewards['completion_bonus'] ?? 50;

        if ($isPerfect) {
            $xpEarned += $xpRewards['perfect_bonus'] ?? 100;
        }
        if ($session['best_streak'] >= 5) {
            $xpEarned += $xpRewards['streak_5_bonus'] ?? 25;
        }
        if ($session['best_streak'] >= 10) {
            $xpEarned += $xpRewards['streak_10_bonus'] ?? 50;
        }

        // Calculate coins
        $coinRewards = $config['coin_rewards'] ?? [];
        $coinsEarned = ($session['correct_count'] * ($coinRewards['per_correct'] ?? 2));
        $coinsEarned += $coinRewards['completion_bonus'] ?? 10;

        if ($isPerfect) {
            $coinsEarned += $coinRewards['perfect_bonus'] ?? 25;
        }
        if ($session['best_streak'] >= 10) {
            $coinsEarned += $coinRewards['streak_10_bonus'] ?? 15;
        }

        // Add perfect round bonus to score
        if ($isPerfect) {
            $scoring = $config['scoring'] ?? [];
            $session['score'] += $scoring['perfect_round_bonus'] ?? 200;
        }

        // Update user progress
        $progress = $this->dataService->getUserProgress();

        // Update level progress
        if (!isset($progress['levels'][$session['level']])) {
            $progress['levels'][$session['level']] = [
                'completed' => false,
                'stars' => 0,
                'best_score' => 0,
                'best_accuracy' => 0,
                'attempts' => 0,
            ];
        }

        $levelProgress = &$progress['levels'][$session['level']];
        $levelProgress['completed'] = true;
        $levelProgress['attempts']++;

        if ($session['score'] > $levelProgress['best_score']) {
            $levelProgress['best_score'] = $session['score'];
        }
        if ($accuracy > $levelProgress['best_accuracy']) {
            $levelProgress['best_accuracy'] = $accuracy;
        }
        if ($stars > $levelProgress['stars']) {
            $levelProgress['stars'] = $stars;
        }

        // Update stats
        $progress['stats']['games_played'] = ($progress['stats']['games_played'] ?? 0) + 1;
        $progress['stats']['total_correct'] = ($progress['stats']['total_correct'] ?? 0) + $session['correct_count'];
        $progress['stats']['total_answered'] = ($progress['stats']['total_answered'] ?? 0) + $totalAnswered;
        $progress['stats']['fast_answers'] = ($progress['stats']['fast_answers'] ?? 0) + $session['fast_answers'];

        if ($isPerfect) {
            $progress['stats']['perfect_rounds'] = ($progress['stats']['perfect_rounds'] ?? 0) + 1;
        }

        if ($session['best_streak'] > ($progress['stats']['best_streak'] ?? 0)) {
            $progress['stats']['best_streak'] = $session['best_streak'];
        }

        // Update overall accuracy
        $totalCorrect = $progress['stats']['total_correct'] ?? 0;
        $totalAnsweredAll = $progress['stats']['total_answered'] ?? 0;
        $progress['stats']['accuracy'] = $totalAnsweredAll > 0
            ? round(($totalCorrect / $totalAnsweredAll) * 100)
            : 0;

        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + $xpEarned;
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + $coinsEarned;

        // Update category stats
        foreach ($session['category_stats'] as $category => $catStats) {
            if (!isset($progress['category_stats'][$category])) {
                $progress['category_stats'][$category] = ['correct' => 0, 'total' => 0];
            }
            $progress['category_stats'][$category]['correct'] += $catStats['correct'];
            $progress['category_stats'][$category]['total'] += $catStats['total'];
        }

        // Update mode stats
        $mode = $session['mode'];
        if (!isset($progress['mode_stats'][$mode])) {
            $progress['mode_stats'][$mode] = ['games' => 0, 'wins' => 0];
        }
        $progress['mode_stats'][$mode]['games']++;
        if ($stars > 0) {
            $progress['mode_stats'][$mode]['wins']++;
        }

        // Check achievements
        $newAchievements = $this->checkAchievements($progress, $session);
        foreach ($newAchievements as $achievement) {
            if (!in_array($achievement['id'], $progress['achievements'] ?? [])) {
                $progress['achievements'][] = $achievement['id'];
                $xpEarned += $achievement['xp_reward'] ?? 0;
                $coinsEarned += $achievement['coin_reward'] ?? 0;
            }
        }

        $this->dataService->saveUserProgress($progress);
        $this->deleteSession($sessionId);

        return [
            'total_score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'accuracy' => $accuracy,
            'stars' => $stars,
            'is_perfect' => $isPerfect,
            'best_streak' => $session['best_streak'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'new_achievements' => $newAchievements,
            'level_complete' => true,
        ];
    }

    /**
     * Check achievements
     */
    protected function checkAchievements(array $progress, array $session): array
    {
        $achievements = $this->dataService->getAchievements();
        $unlockedIds = $progress['achievements'] ?? [];
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) {
                continue;
            }

            if ($this->isAchievementUnlocked($achievement, $progress, $session)) {
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    /**
     * Check if achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $progress, array $session): bool
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $value = $condition['value'] ?? 1;

        switch ($type) {
            case 'correct_answers':
                return ($progress['stats']['total_correct'] ?? 0) >= $value;

            case 'perfect_rounds':
                return ($progress['stats']['perfect_rounds'] ?? 0) >= $value;

            case 'streak':
                return ($progress['stats']['best_streak'] ?? 0) >= $value;

            case 'total_rounds':
                return ($progress['stats']['games_played'] ?? 0) >= $value;

            case 'fast_answers':
                return ($progress['stats']['fast_answers'] ?? 0) >= $value;

            case 'category_correct':
                $category = $condition['category'] ?? '';
                return ($progress['category_stats'][$category]['correct'] ?? 0) >= $value;

            case 'mode_complete':
                $mode = $condition['mode'] ?? '';
                return ($progress['mode_stats'][$mode]['wins'] ?? 0) > 0;

            case 'mode_rounds':
                $mode = $condition['mode'] ?? '';
                return ($progress['mode_stats'][$mode]['games'] ?? 0) >= $value;

            case 'all_categories':
                $categories = ['grammar', 'vocabulary', 'culture', 'pronunciation', 'spelling'];
                foreach ($categories as $cat) {
                    if (($progress['category_stats'][$cat]['correct'] ?? 0) < 1) {
                        return false;
                    }
                }
                return true;

            default:
                return false;
        }
    }

    /**
     * Get session state
     */
    public function getSessionState(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return null;
        }

        return [
            'session_id' => $session['id'],
            'level' => $session['level'],
            'mode' => $session['mode'],
            'score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'streak' => $session['streak'],
            'current_index' => $session['current_index'],
            'total_statements' => count($session['statements']),
            'current_statement' => isset($session['statements'][$session['current_index']])
                ? $this->formatStatement($session['statements'][$session['current_index']])
                : null,
        ];
    }

    /**
     * Session management
     */
    protected function getSession(string $sessionId): ?array
    {
        $path = storage_path("app/game_sessions/true_false/{$sessionId}.json");
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return null;
    }

    protected function saveSession(array $session): void
    {
        $dir = storage_path('app/game_sessions/true_false');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$session['id']}.json";
        file_put_contents($path, json_encode($session));
    }

    protected function deleteSession(string $sessionId): void
    {
        $path = storage_path("app/game_sessions/true_false/{$sessionId}.json");
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
