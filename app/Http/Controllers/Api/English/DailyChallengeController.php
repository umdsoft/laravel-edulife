<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishDailyChallenge;
use App\Models\English\UserDailyChallenge;
use App\Services\English\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DailyChallengeController extends Controller
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get today's daily challenge
     */
    public function today(Request $request): JsonResponse
    {
        $profile = $this->levelService->getOrCreateProfile($request->user());

        $challenge = EnglishDailyChallenge::with('tasks')
            ->whereDate('challenge_date', now()->toDateString())
            ->where('is_active', true)
            ->first();

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'No challenge available today',
            ], 404);
        }

        $userChallenge = UserDailyChallenge::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'daily_challenge_id' => $challenge->id,
            ],
            [
                'id' => Str::uuid(),
                'status' => 'available',
                'tasks_completed' => [],
                'started_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'challenge' => $challenge,
                'user_progress' => $userChallenge,
            ],
        ]);
    }

    /**
     * Complete a task
     */
    public function completeTask(Request $request, string $challengeId, string $taskId): JsonResponse
    {
        $validated = $request->validate([
            'score' => 'sometimes|integer|min:0',
        ]);

        $userChallenge = UserDailyChallenge::where('user_id', $request->user()->id)
            ->where('daily_challenge_id', $challengeId)
            ->firstOrFail();

        $tasksCompleted = $userChallenge->tasks_completed ?? [];

        if (!in_array($taskId, $tasksCompleted)) {
            $tasksCompleted[] = $taskId;
            $userChallenge->tasks_completed = $tasksCompleted;
        }

        $challenge = $userChallenge->dailyChallenge;
        $totalTasks = $challenge->tasks()->count();

        $userChallenge->completion_percentage = round((count($tasksCompleted) / $totalTasks) * 100, 2);

        if ($userChallenge->completion_percentage >= 100) {
            $userChallenge->status = 'completed';
            $userChallenge->completed_at = now();

            $profile = $this->levelService->getOrCreateProfile($request->user());
            $profile->addXp($challenge->xp_reward ?? 50);
            $profile->addCoins($challenge->coin_reward ?? 20);
        } else {
            $userChallenge->status = 'in_progress';
        }

        $userChallenge->save();

        return response()->json([
            'success' => true,
            'data' => $userChallenge,
        ]);
    }

    /**
     * Get challenge history
     */
    public function history(Request $request): JsonResponse
    {
        $challenges = UserDailyChallenge::with('dailyChallenge')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $challenges,
        ]);
    }
}
