<?php

namespace App\Jobs\English;

use App\Models\English\EnglishDailyChallenge;
use App\Models\English\EnglishDailyChallengeTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateDailyChallengeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $existingChallenge = EnglishDailyChallenge::whereDate('challenge_date', now()->toDateString())->first();

        if ($existingChallenge) {
            return;
        }

        $themes = ['vocabulary', 'grammar', 'mixed', 'speaking', 'listening'];
        $difficulties = ['easy', 'medium', 'hard'];

        $challenge = EnglishDailyChallenge::create([
            'id' => Str::uuid(),
            'challenge_date' => now()->toDateString(),
            'theme' => $themes[array_rand($themes)],
            'difficulty' => $difficulties[now()->dayOfWeek % 3],
            'xp_reward' => rand(50, 100),
            'coin_reward' => rand(20, 50),
            'is_active' => true,
        ]);

        $tasks = [
            ['title' => 'Learn 5 new words', 'type' => 'vocabulary', 'target_count' => 5],
            ['title' => 'Complete 1 lesson', 'type' => 'lesson', 'target_count' => 1],
            ['title' => 'Win 1 battle', 'type' => 'battle', 'target_count' => 1],
            ['title' => 'Practice grammar', 'type' => 'grammar', 'target_count' => 5],
        ];

        $selectedTasks = array_slice($tasks, 0, rand(3, 4));

        foreach ($selectedTasks as $index => $task) {
            EnglishDailyChallengeTask::create([
                'id' => Str::uuid(),
                'daily_challenge_id' => $challenge->id,
                'title' => $task['title'],
                'task_type' => $task['type'],
                'target_count' => $task['target_count'],
                'xp_reward' => rand(10, 25),
                'order_number' => $index + 1,
            ]);
        }
    }
}
