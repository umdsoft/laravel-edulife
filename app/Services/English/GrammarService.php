<?php

namespace App\Services\English;

use App\Models\English\EnglishGrammarRule;
use App\Models\English\EnglishGrammarExercise;
use App\Models\User;
use Illuminate\Support\Collection;

class GrammarService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get grammar rules for level
     */
    public function getGrammarRulesForLevel(string $levelId, User $user): Collection
    {
        return EnglishGrammarRule::with(['category', 'examples'])
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->map(function ($rule) use ($user) {
                $rule->user_progress = $this->getRuleProgress($rule, $user);
                return $rule;
            });
    }

    /**
     * Get single grammar rule with exercises
     */
    public function getGrammarRuleWithExercises(string $ruleId, User $user): ?EnglishGrammarRule
    {
        $rule = EnglishGrammarRule::with([
            'category',
            'examples',
            'exercises' => fn($q) => $q->where('is_active', true)->orderBy('order_number'),
            'level',
        ])->find($ruleId);

        if (!$rule)
            return null;

        $rule->user_progress = $this->getRuleProgress($rule, $user);

        return $rule;
    }

    /**
     * Get grammar exercises for practice
     */
    public function getExercisesForPractice(string $ruleId, User $user, int $limit = 10): Collection
    {
        return EnglishGrammarExercise::where('grammar_rule_id', $ruleId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Submit grammar exercise answer
     */
    public function submitExerciseAnswer(
        EnglishGrammarExercise $exercise,
        User $user,
        string $answer
    ): array {
        $isCorrect = $this->checkAnswer($exercise, $answer);

        $exercise->times_attempted = ($exercise->times_attempted ?? 0) + 1;
        if ($isCorrect) {
            $exercise->times_correct = ($exercise->times_correct ?? 0) + 1;
        }
        $exercise->accuracy_rate = round(($exercise->times_correct / $exercise->times_attempted) * 100, 2);
        $exercise->save();

        $xpEarned = $isCorrect ? 5 : 1;
        $profile = $this->levelService->getOrCreateProfile($user);
        $profile->addXp($xpEarned);

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $exercise->correct_answer,
            'explanation' => $exercise->explanation,
            'xp_earned' => $xpEarned,
        ];
    }

    /**
     * Check if answer is correct
     */
    private function checkAnswer(EnglishGrammarExercise $exercise, string $answer): bool
    {
        $correctAnswer = strtolower(trim($exercise->correct_answer));
        $userAnswer = strtolower(trim($answer));

        if ($correctAnswer === $userAnswer) {
            return true;
        }

        $alternatives = $exercise->alternative_answers ?? [];
        foreach ($alternatives as $alt) {
            if (strtolower(trim($alt)) === $userAnswer) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rule progress for user
     */
    private function getRuleProgress(EnglishGrammarRule $rule, User $user): array
    {
        $exercises = $rule->exercises;
        $totalExercises = $exercises->count();

        return [
            'total_exercises' => $totalExercises,
            'completed_exercises' => 0,
            'accuracy' => 0,
            'is_mastered' => false,
        ];
    }
}
