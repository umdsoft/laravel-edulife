<?php

namespace Tests\Unit;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\EloRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * QA2-S-18: a 0:0 draw left ELO at 1000 but turned "Bronza" into "Kumush":
 * new profiles defaulted to bronze while the scale put 1000 in silver.
 */
class BattleRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_starting_rating_is_bronze_on_the_one_scale(): void
    {
        $this->assertSame('bronze', StudentProfile::calculateRank(1000));
        $this->assertSame('bronze', StudentProfile::calculateRank(1099));
        $this->assertSame('silver', StudentProfile::calculateRank(1100));
        $this->assertSame('gold', StudentProfile::calculateRank(1300));
        $this->assertSame('master', StudentProfile::calculateRank(2400));
    }

    public function test_an_unchanged_rating_keeps_the_title(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $profile = $student->studentProfile()->first();
        $this->assertSame(1000, (int) $profile->elo_rating);
        $before = $profile->rank;

        app(EloRatingService::class)->updateRank($student->fresh());

        $this->assertSame($before, $student->studentProfile()->first()->rank);
        $this->assertSame('bronze', $before);
    }

    public function test_existing_titles_are_recomputed_from_elo(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DB::table('student_profiles')->where('user_id', $student->id)->update(['elo_rating' => 1000, 'rank' => 'silver']);
        $pro = User::factory()->create(['role' => 'student']);
        DB::table('student_profiles')->where('user_id', $pro->id)->update(['elo_rating' => 1800, 'rank' => 'bronze']);

        (require database_path('migrations/2026_10_21_000011_recompute_battle_ranks_from_elo.php'))->up();

        $this->assertSame('bronze', DB::table('student_profiles')->where('user_id', $student->id)->value('rank'));
        $this->assertSame('platinum', DB::table('student_profiles')->where('user_id', $pro->id)->value('rank'));
    }
}
