<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishUnit;
use App\Models\English\UserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LevelController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $requestedLevelCode = $request->input('level');

        // Fetch all levels with units and lessons
        $levels = EnglishLevel::where('is_active', true)
            ->orderBy('order_number')
            ->with([
                'units' => function ($q) {
                    $q->where('is_active', true)->orderBy('order_number')->with([
                        'lessons' => function ($q) {
                            $q->where('is_active', true)->orderBy('order_number');
                        }
                    ]);
                }
            ])
            ->get();

        // Fetch user progress for all lessons
        $progressMap = [];
        if ($user) {
            $progressMap = UserLessonProgress::where('user_id', $user->id)
                ->get()
                ->keyBy('lesson_id');
        }

        // Process levels data
        $processedLevels = $levels->map(function ($level, $index) use ($progressMap, $levels) {
            $totalLessons = 0;
            $completedLessons = 0;

            foreach ($level->units as $unit) {
                foreach ($unit->lessons as $lesson) {
                    $totalLessons++;
                    $progress = $progressMap[$lesson->id] ?? null;
                    if ($progress && $progress->status === 'completed') {
                        $completedLessons++;
                    }
                }
            }

            // Unlock logic
            $isUnlocked = $index === 0;

            // Test mode - all levels unlocked
            if (\App\Models\Setting::get('english_test_mode', false)) {
                $isUnlocked = true;
            } elseif ($index > 0) {
                $prevLevel = $levels[$index - 1];
                // Calculate prev level progress
                $prevTotal = 0;
                $prevCompleted = 0;
                foreach ($prevLevel->units as $pUnit) {
                    foreach ($pUnit->lessons as $pLesson) {
                        $prevTotal++;
                        $pProg = $progressMap[$pLesson->id] ?? null;
                        if ($pProg && $pProg->status === 'completed') {
                            $prevCompleted++;
                        }
                    }
                }
                $prevPercent = $prevTotal > 0 ? ($prevCompleted / $prevTotal) * 100 : 0;
                // Unlock if previous level is at least 80% complete
                $isUnlocked = $prevPercent >= 80;
            }

            // Allow A1 to be open by default
            if ($level->code === 'A1')
                $isUnlocked = true;

            return [
                'id' => $level->id,
                'code' => $level->code,
                'name' => $level->name,
                'description' => $level->description,
                'color' => $level->color ?? 'green',
                'icon' => $level->icon ?? '🎯',
                'is_unlocked' => $isUnlocked,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
                'modules_count' => $level->units->count(),
            ];
        });

        // Determine current level
        $currentLevelData = null;
        if ($requestedLevelCode) {
            $currentLevelData = $processedLevels->firstWhere('code', $requestedLevelCode);
        }

        if (!$currentLevelData) {
            // Default to the first unlocked level that is not fully completed
            $currentLevelData = $processedLevels->filter(fn($l) => $l['is_unlocked'] && $l['progress_percent'] < 100)->first()
                ?? $processedLevels->first();
        }

        // Get modules for the current level
        $modules = [];
        if ($currentLevelData) {
            $currentLevelModel = $levels->firstWhere('id', $currentLevelData['id']);

            if ($currentLevelModel) {
                // Check test mode
                $isTestMode = \App\Models\Setting::get('english_test_mode', false);

                // Determine module unlock status
                $previousModuleCompleted = true; // First module is unlocked

                $modules = $currentLevelModel->units->map(function ($unit) use ($progressMap, &$previousModuleCompleted, $isTestMode) {
                    $totalUnitLessons = $unit->lessons->count();
                    $completedUnitLessons = 0;

                    // Calculate stats first
                    foreach ($unit->lessons as $lesson) {
                        $p = $progressMap[$lesson->id] ?? null;
                        if ($p && $p->status === 'completed')
                            $completedUnitLessons++;
                    }

                    // Test mode - all units unlocked
                    $isUnitUnlocked = $isTestMode ? true : $previousModuleCompleted;
                    $progressPercent = $totalUnitLessons > 0 ? round(($completedUnitLessons / $totalUnitLessons) * 100) : 0;

                    // Calculate Unlocked status for lessons
                    $prevLessonDone = true; // First lesson of unlocked module is always unlocked
                    $lessonIndex = 0;

                    $unitLessons = $unit->lessons->map(function ($lesson) use ($progressMap, &$prevLessonDone, $isUnitUnlocked, &$lessonIndex, $isTestMode) {
                        $p = $progressMap[$lesson->id] ?? null;
                        $status = $p ? $p->status : 'not_started';
                        $score = $p ? $p->best_score : 0;

                        // Test mode - all lessons unlocked
                        // Normal mode: Lesson is unlocked if module is unlocked AND (first lesson OR previous lesson is completed)
                        $isLessonUnlocked = $isTestMode ? true : ($isUnitUnlocked && ($lessonIndex === 0 || $prevLessonDone));

                        // Update prevLessonDone for next iteration
                        $prevLessonDone = ($status === 'completed');
                        $lessonIndex++;

                        return [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'type' => $lesson->lesson_type,
                            'status' => $status,
                            'score' => $score ?? 0,
                            'xp_reward' => $lesson->xp_reward,
                            'coin_reward' => $lesson->coin_reward,
                            'is_unlocked' => $isLessonUnlocked,
                        ];
                    });

                    // Update previous module completed status for next module
                    if ($progressPercent < 100 && $totalUnitLessons > 0) {
                        $previousModuleCompleted = false;
                    }

                    return [
                        'id' => $unit->id,
                        'title' => $unit->title,
                        'description' => $unit->description,
                        'order' => $unit->order_number,
                        'is_unlocked' => $isUnitUnlocked,
                        'status' => $progressPercent == 100 ? 'completed' : ($progressPercent > 0 ? 'in_progress' : 'not_started'),
                        'progress_percent' => $progressPercent,
                        'estimated_minutes' => $unit->estimated_minutes,
                        'lessons' => $unitLessons,
                    ];
                });
            }
        }

        return Inertia::render('English/Learn/Index', [
            'levels' => $processedLevels->values(),
            'currentLevel' => $currentLevelData,
            'modules' => $modules,
            'userProgress' => [
                'xp' => $user ? ($user->xp ?? 0) : 0,
            ]
        ]);
    }

    public function modules(Request $request, string $code)
    {
        // Redirect to index logic via an internal call or just return JSON if strictly API.
        // But since this route was used in the frontend code provided (Index.vue uses Inertia visit),
        // we likely won't hit this method for the page view.
        // However, if the user requested it for API:
        // Reuse logic?
        // Let's just return what index returns but in JSON if we want to be clean.
        // For now, I'll return empty as it's not the main path.
        // Actually, let's implement it for completeness in case I misunderstood 'router.get' usage (sometimes it's used for partial reloads).
        return $this->index($request->merge(['level' => $code]));
    }
}
