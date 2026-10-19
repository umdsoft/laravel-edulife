<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * student_profiles is the single source of truth for a student's XP, level and
 * coins. Lab experiments, lab badges, the Achievement model and daily missions
 * used to credit the legacy users.coin_balance / users.xp_total columns, which
 * nothing reads, so those amounts never reached the header, history or shop.
 *
 * Moves whatever is left in the legacy columns onto the student profile once.
 * Guard: each row is zeroed in the same transaction after it is moved, so a
 * second run (or a rollback + re-run) finds nothing to move and never double
 * counts. Only students are touched; the columns themselves are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $students = DB::table('users')
                ->where('role', 'student')
                ->where(fn ($q) => $q->where('coin_balance', '>', 0)->orWhere('xp_total', '>', 0))
                ->lockForUpdate()
                ->get(['id', 'coin_balance', 'xp_total']);

            foreach ($students as $student) {
                $coins = max(0, (int) $student->coin_balance);
                $xp = max(0, (int) $student->xp_total);
                $now = now();

                $profile = DB::table('student_profiles')->where('user_id', $student->id)->lockForUpdate()->first();
                if (!$profile) {
                    DB::table('student_profiles')->insert([
                        'id' => (string) Str::uuid7(),
                        'user_id' => $student->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $profile = DB::table('student_profiles')->where('user_id', $student->id)->first();
                }

                $newCoins = (int) $profile->coins + $coins;
                $newXp = (int) $profile->xp + $xp;
                // same formula as StudentProfile::calculateLevel()
                $level = max((int) $profile->level, (int) floor(sqrt($newXp / 100)) + 1);

                DB::table('student_profiles')->where('id', $profile->id)->update([
                    'coins' => $newCoins,
                    'total_coins_earned' => (int) $profile->total_coins_earned + $coins,
                    'xp' => $newXp,
                    'level' => $level,
                    'updated_at' => $now,
                ]);

                if ($coins > 0) {
                    DB::table('coin_transactions')->insert([
                        'id' => (string) Str::uuid7(),
                        'user_id' => $student->id,
                        'type' => 'bonus',
                        'amount' => $coins,
                        'balance_after' => $newCoins,
                        'source' => 'legacy_transfer',
                        'description' => "Avvalgi hisobdagi coinlar ko'chirildi",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // the guard: nothing is left to move on a second run
                DB::table('users')->where('id', $student->id)->update([
                    'coin_balance' => 0,
                    'xp_total' => 0,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Data move; the amounts now live on student_profiles and are not moved back.
    }
};
