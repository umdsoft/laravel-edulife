<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishLesson;
use App\Models\English\UserLessonProgress;
use App\Models\English\UserEnglishProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Base XP rewards by lesson type
     */
    private array $baseXpRewards = [
        'vocabulary' => 2,
        'grammar' => 3,
        'practice' => 2,
        'conversation' => 3,
        'standard' => 2,
        'review' => 2,
        'test' => 5,
    ];

    /**
     * Base coin rewards by lesson type
     */
    private array $baseCoinRewards = [
        'vocabulary' => 1,
        'grammar' => 1,
        'practice' => 1,
        'conversation' => 1,
        'standard' => 1,
        'review' => 1,
        'test' => 2,
    ];

    /**
     * Time limits for time bonus (in minutes)
     */
    private array $timeLimits = [
        'vocabulary' => 3,
        'grammar' => 5,
        'practice' => 3,
        'conversation' => 5,
        'standard' => 5,
        'review' => 3,
        'test' => 10,
    ];

    /**
     * Show lesson page
     */
    public function show(string $lessonId)
    {
        $user = Auth::user();
        $lesson = EnglishLesson::with(['unit.level'])->findOrFail($lessonId);

        // Check if lesson is unlocked
        if (!$this->isLessonUnlocked($lesson, $user)) {
            return redirect()->route('student.english.levels')
                ->with('error', 'Complete previous lessons first!');
        }

        // Get next lesson in the same unit
        $nextLesson = EnglishLesson::where('unit_id', $lesson->unit_id)
            ->where('lesson_number', '>', $lesson->lesson_number)
            ->where('is_active', true)
            ->orderBy('lesson_number')
            ->first();

        // If no next lesson in unit, check next unit
        if (!$nextLesson) {
            $nextUnit = $lesson->unit->level->units()
                ->where('unit_number', '>', $lesson->unit->unit_number)
                ->where('is_active', true)
                ->orderBy('unit_number')
                ->first();

            if ($nextUnit) {
                $nextLesson = $nextUnit->lessons()
                    ->where('is_active', true)
                    ->orderBy('lesson_number')
                    ->first();
            }
        }

        // Get user progress for this lesson
        $userProgress = $user ? UserLessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first() : null;

        // Generate content if not set
        $content = $lesson->content ?? $this->generateDefaultContent($lesson);

        return Inertia::render('English/Learn/Lesson/Show', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'title_uz' => $lesson->title_uz,
                'type' => $lesson->lesson_type,
                'description' => $lesson->description,
                'xp_reward' => $this->baseXpRewards[$lesson->lesson_type] ?? 2,
                'coin_reward' => $this->baseCoinRewards[$lesson->lesson_type] ?? 1,
                'pass_percentage' => $lesson->pass_percentage,
                'estimated_minutes' => $lesson->estimated_minutes,
            ],
            'content' => $content,
            'module' => [
                'id' => $lesson->unit->id,
                'title' => $lesson->unit->title,
                'level' => $lesson->unit->level->code,
                'level_color' => $lesson->unit->level->color,
            ],
            'userProgress' => $userProgress ? [
                'status' => $userProgress->status,
                'best_score' => $userProgress->best_score,
                'attempts' => $userProgress->attempts,
            ] : null,
            'nextLesson' => $nextLesson ? [
                'id' => $nextLesson->id,
                'title' => $nextLesson->title,
            ] : null,
        ]);
    }

    /**
     * Complete lesson and award rewards
     */
    public function complete(Request $request, string $lessonId)
    {
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'time_spent' => 'required|integer|min:0',
            'correct_answers' => 'required|integer|min:0',
            'total_questions' => 'required|integer|min:1',
            'max_streak' => 'nullable|integer|min:0',
        ]);

        $user = Auth::user();
        $lesson = EnglishLesson::findOrFail($lessonId);

        // Check pass threshold (80% required)
        if ($validated['score'] < 80) {
            return back()->with('error', 'You need at least 80% to pass this lesson.');
        }

        // Check if this is first completion
        $existingProgress = UserLessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $isFirstCompletion = !$existingProgress || $existingProgress->status !== 'completed';

        // Calculate rewards
        $rewards = $this->calculateRewards(
            $lesson,
            $validated['score'],
            $validated['time_spent'],
            $validated['max_streak'] ?? 0,
            $isFirstCompletion
        );

        DB::transaction(function () use ($user, $lesson, $validated, $existingProgress, $isFirstCompletion, $rewards) {
            // Update or create progress
            $progress = UserLessonProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'status' => 'completed',
                    'last_score' => $validated['score'],
                    'best_score' => max($validated['score'], $existingProgress?->best_score ?? 0),
                    'attempts' => ($existingProgress?->attempts ?? 0) + 1,
                    'stars_earned' => $rewards['stars'],
                    'time_spent_seconds' => ($existingProgress?->time_spent_seconds ?? 0) + $validated['time_spent'],
                    'completed_at' => now(),
                    'last_accessed_at' => now(),
                ]
            );

            // Award XP and Coins only on first completion
            if ($isFirstCompletion) {
                $progress->update([
                    'xp_earned' => $rewards['xp'],
                    'coins_earned' => $rewards['coins'],
                ]);

                // Update user XP and coins
                $user->increment('xp_total', $rewards['xp']);
                
                // Award coins to StudentProfile (for proper tracking in dashboard/admin)
                if ($user->studentProfile) {
                    $user->studentProfile->addCoins($rewards['coins']);
                    $user->studentProfile->addXp($rewards['xp']);
                } else {
                    // Fallback to user model if no student profile
                    $user->increment('coin_balance', $rewards['coins']);
                }

                // Update English profile if exists
                $profile = UserEnglishProfile::where('user_id', $user->id)->first();
                if ($profile) {
                    $profile->increment('total_xp', $rewards['xp']);
                    $profile->increment('lessons_completed');

                    // Check for level up
                    $this->checkLevelUp($profile);
                }
            }
        });

        return back()->with([
            'success' => true,
            'rewards' => $rewards,
            'is_first_completion' => $isFirstCompletion,
        ]);
    }

    /**
     * Calculate rewards based on performance
     */
    private function calculateRewards(
        EnglishLesson $lesson,
        int $score,
        int $timeSpent,
        int $maxStreak,
        bool $isFirstCompletion
    ): array {
        $lessonType = $lesson->lesson_type;

        // Base rewards
        $xp = $this->baseXpRewards[$lessonType] ?? 2;
        $coins = $this->baseCoinRewards[$lessonType] ?? 1;

        // Only apply bonuses on first completion
        if ($isFirstCompletion) {
            // Accuracy bonus (+1 XP if 90%+)
            if ($score >= 90) {
                $xp += 1;
            }

            // Time bonus (+1 XP if under time limit)
            $timeLimitMinutes = $this->timeLimits[$lessonType] ?? 5;
            if ($timeSpent < ($timeLimitMinutes * 60)) {
                $xp += 1;
            }

            // Streak bonus
            if ($maxStreak >= 10) {
                $xp += 2;
            } elseif ($maxStreak >= 5) {
                $xp += 1;
            } elseif ($maxStreak >= 3) {
                $xp += 1; // Round 0.5 up
            }

            // Perfect lesson bonus (100% first try)
            if ($score === 100) {
                $xp += 1;
                $coins += 1;
            }
        } else {
            // No rewards for retries
            $xp = 0;
            $coins = 0;
        }

        // Calculate stars
        $stars = $score >= 90 ? 3 : ($score >= 70 ? 2 : ($score >= 50 ? 1 : 0));

        // Apply max limits
        $maxXp = $lessonType === 'test' ? 10 : 6;
        $maxCoins = $lessonType === 'test' ? 4 : 3;

        return [
            'xp' => min($xp, $maxXp),
            'coins' => min($coins, $maxCoins),
            'stars' => $stars,
            'accuracy' => $score,
        ];
    }

    /**
     * Check if lesson is unlocked for user
     */
    private function isLessonUnlocked(EnglishLesson $lesson, $user): bool
    {
        // First lesson of first unit is always unlocked
        if ($lesson->lesson_number === 1) {
            $unit = $lesson->unit;
            if ($unit->unit_number === 1) {
                return true;
            }
        }

        if (!$user) {
            return $lesson->is_free;
        }

        // Check if previous lesson is completed
        $previousLesson = EnglishLesson::where('unit_id', $lesson->unit_id)
            ->where('lesson_number', '<', $lesson->lesson_number)
            ->orderByDesc('lesson_number')
            ->first();

        if ($previousLesson) {
            $progress = UserLessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $previousLesson->id)
                ->where('status', 'completed')
                ->exists();

            return $progress;
        }

        // If first lesson in unit, check if previous unit is complete
        $unit = $lesson->unit;
        $previousUnit = $unit->level->units()
            ->where('unit_number', '<', $unit->unit_number)
            ->orderByDesc('unit_number')
            ->first();

        if ($previousUnit) {
            $lastLesson = $previousUnit->lessons()
                ->orderByDesc('lesson_number')
                ->first();

            if ($lastLesson) {
                return UserLessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lastLesson->id)
                    ->where('status', 'completed')
                    ->exists();
            }
        }

        return true;
    }

    /**
     * Check and handle level up
     */
    private function checkLevelUp(UserEnglishProfile $profile): void
    {
        // Level progression formula: XP = 50 + (level-2) * 20 + (level-2)^2 * 10
        $currentLevel = $profile->current_level ?? 1;
        $xp = $profile->total_xp;

        $levelThresholds = [
            1 => 0,
            2 => 50,
            3 => 120,
            4 => 200,
            5 => 300,
            6 => 420,
            7 => 560,
            8 => 720,
            9 => 900,
            10 => 1100,
        ];

        $newLevel = 1;
        foreach ($levelThresholds as $level => $threshold) {
             if ($xp >= $threshold) {
                 $newLevel = $level;
             }
        }

        // Find the level model matches this order number
        if ($profile->relationLoaded('currentLevel') && $profile->currentLevel?->order_number === $newLevel) {
            return;
        }

        $levelModel = \App\Models\English\EnglishLevel::where('order_number', $newLevel)->first();
        
        if ($levelModel && $profile->current_level_id !== $levelModel->id) {
            $profile->current_level_id = $levelModel->id;
            $profile->save();
        }
    }

    /**
     * Generate default content for lessons without content
     */
    private function generateDefaultContent(EnglishLesson $lesson): array
    {
        // Default content structure based on lesson type
        $defaultContent = [
            'totalSteps' => 10,
            'words' => [],
            'exercises' => [],
            'quiz' => [],
        ];

        // Add sample content based on lesson type
        if ($lesson->lesson_type === 'vocabulary') {
            $defaultContent['words'] = [
                [
                    'english' => 'Hello',
                    'uzbek' => 'Salom',
                    'pronunciation' => '/həˈloʊ/',
                    'emoji' => '👋',
                    'example' => 'Hello! How are you?',
                    'exampleTranslation' => 'Salom! Qalaysiz?',
                ],
            ];
            $defaultContent['quiz'] = [
                [
                    'type' => 'multiple_choice',
                    'question' => 'What does "Hello" mean?',
                    'options' => ['Salom', 'Xayr', 'Rahmat', 'Kechirasiz'],
                    'correctAnswer' => 0,
                    'explanation' => '"Hello" means "Salom" - a common greeting.',
                ],
            ];
        }

        return $defaultContent;
    }
}
