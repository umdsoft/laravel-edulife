<?php

namespace Tests\Feature\Student;

use App\Models\LabBadge;
use App\Services\CoinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Rewards and purchases change student_profiles; the header read users.*.
 */
class CoinsSingleSourceTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_shared_props_show_the_student_profile_balance(): void
    {
        $student = $this->studentUser();
        $student->forceFill(['coin_balance' => 999, 'xp_total' => 999])->save();
        $student->studentProfile->update(['xp' => 120, 'level' => 2]);
        app(CoinService::class)->addCoins($student, 50, 'test', 'Sinov');

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.coin_balance', 50)
                ->where('auth.user.xp_total', 120)
                ->where('auth.user.level', 2)
                ->where('auth.studentProfile.coins', 50));

        $this->assertSame(50, $student->fresh()->coins);
    }

    /** QA3-S-06: "Jami olingan" counted a tournament refund as earned (605) */
    public function test_coin_history_totals_do_not_count_refunds_as_earnings(): void
    {
        $student = $this->studentUser();
        $coins = app(CoinService::class);
        $coins->addCoins($student, 100, 'olympiad_reward', 'Olimpiada mukofoti');
        $coins->spendCoins($student, 50, 'tournament', 'Turnirga kirish');
        $coins->addCoins($student, 50, 'tournament_refund', 'Turnirdan chiqish');
        $coins->spendCoins($student, 30, 'shop', 'Avatar');

        $this->actingAs($student->fresh())->get(route('student.coins.index'))
            ->assertInertia(fn ($page) => $page->where('balance', 70)->where('total_earned', 100)->where('total_spent', 30));
        $this->assertSame(100, (int) $student->studentProfile()->first()->total_coins_earned);
    }

    public function test_lab_badge_and_legacy_user_helpers_credit_the_student_profile(): void
    {
        $student = $this->studentUser();

        $badge = LabBadge::create([
            'slug' => 'birinchi-tajriba',
            'name' => 'Birinchi tajriba',
            'name_uz' => 'Birinchi tajriba',
            'description_uz' => 'Birinchi tajribani yakunlang',
            'icon' => 'beaker',
            'earn_condition' => ['type' => 'experiments_completed', 'count' => 1],
            'xp_reward' => 10,
            'coin_reward' => 5,
        ]);
        $this->assertTrue($badge->awardTo($student));

        // the User helpers used by lab attempts, achievements and daily missions
        $student->addCoins(30, 'lab_experiment', 'Lab yakunlandi');
        $student->addXp(40);

        $profile = $student->studentProfile->fresh();
        $this->assertSame(35, (int) $profile->coins);
        $this->assertSame(50, (int) $profile->xp);
        $this->assertSame(35, $student->fresh()->coins);

        // the legacy users.* columns are no longer written
        $legacy = DB::table('users')->where('id', $student->id)->first();
        $this->assertSame(0, (int) $legacy->coin_balance);
        $this->assertSame(0, (int) $legacy->xp_total);

        // history balance_after follows the profile balance
        $this->assertSame(35, (int) DB::table('coin_transactions')->where('user_id', $student->id)
            ->where('source', 'lab_experiment')->value('balance_after'));

        $this->assertTrue($student->spendCoins(20, 'shop', 'Sotib olindi'));
        $this->assertSame(15, (int) $student->studentProfile->fresh()->coins);
    }

    public function test_legacy_balances_are_moved_to_the_profile_once(): void
    {
        $student = $this->studentUser();
        $student->studentProfile->update(['coins' => 13, 'total_coins_earned' => 13, 'xp' => 215, 'level' => 2]);
        DB::table('users')->where('id', $student->id)->update(['coin_balance' => 335, 'xp_total' => 800]);

        $teacher = $this->teacher();
        DB::table('users')->where('id', $teacher->id)->update(['coin_balance' => 2000]);

        $migration = require database_path('migrations/2026_10_21_000006_move_legacy_user_balances_to_student_profiles.php');
        $migration->up();
        $migration->up(); // a second run must not double count

        $profile = $student->studentProfile->fresh();
        $this->assertSame(348, (int) $profile->coins);
        $this->assertSame(1015, (int) $profile->xp);
        $this->assertSame(4, (int) $profile->level);
        $this->assertSame(1, DB::table('coin_transactions')->where('user_id', $student->id)->where('source', 'legacy_transfer')->count());

        $legacy = DB::table('users')->where('id', $student->id)->first();
        $this->assertSame(0, (int) $legacy->coin_balance);
        $this->assertSame(0, (int) $legacy->xp_total);

        // non-students are left alone
        $this->assertSame(2000, (int) DB::table('users')->where('id', $teacher->id)->value('coin_balance'));
        $this->assertNull($teacher->fresh()->studentProfile);
    }
}
