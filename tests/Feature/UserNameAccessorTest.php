<?php

namespace Tests\Feature;

use App\Models\OlympiadLiveLeaderboard;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesOlympiads;
use Tests\TestCase;

/**
 * Olympiad, lab and leaderboard code read $user->name and eager-loaded
 * user:id,name — users has no name column (SQL error / empty names).
 */
class UserNameAccessorTest extends TestCase
{
    use RefreshDatabase, CreatesOlympiads;

    public function test_name_is_the_full_name(): void
    {
        $user = User::factory()->make(['first_name' => 'Ali', 'last_name' => 'Valiyev']);

        $this->assertSame('Ali Valiyev', $user->name);
    }

    public function test_live_leaderboard_loads_user_names(): void
    {
        $user = User::factory()->create(['role' => 'student', 'first_name' => 'Sara', 'last_name' => 'Karimova']);
        $olympiad = $this->olympiad();
        $attempt = $this->olympiadAttempt($olympiad, $user);
        OlympiadLiveLeaderboard::forceCreate([
            'olympiad_id' => $olympiad->id, 'user_id' => $user->id, 'attempt_id' => $attempt->id,
            'rank' => 1, 'score' => 90, 'max_score' => 100, 'score_percent' => 90, 'time_spent_seconds' => 600,
        ]);
        $olympiadId = $olympiad->id;

        $board = app(LeaderboardService::class)->getLiveLeaderboard($olympiadId);

        $this->assertSame('Sara Karimova', $board->first()->user->name);
    }
}
