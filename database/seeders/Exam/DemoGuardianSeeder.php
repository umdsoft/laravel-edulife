<?php

namespace Database\Seeders\Exam;

use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo parent account for the guardian panel (exam stage 7), linked to the
 * demo student student@test.uz (TestUserSeeder). Local/testing only;
 * re-running changes nothing.
 *
 *   guardian@test.uz / +998900000010 / password123
 */
class DemoGuardianSeeder extends Seeder
{
    public const EMAIL = 'guardian@test.uz';

    public const PHONE = '+998900000010';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoGuardianSeeder skipped: demo accounts are only seeded in local/testing');

            return;
        }

        $student = User::query()->where('email', 'student@test.uz')->first();
        if (! $student) {
            $this->command?->warn('DemoGuardianSeeder: student@test.uz not found — run TestUserSeeder first');

            return;
        }

        $guardian = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'phone' => self::PHONE,
                'password' => Hash::make('password123'),
                'first_name' => 'Gulnora',
                'last_name' => 'Valiyeva',
                'role' => 'guardian',
                'status' => 'active',
            ]
        );
        if ($guardian->wasRecentlyCreated) {
            $guardian->forceFill(['phone_verified_at' => now(), 'email_verified_at' => now()])->save();
        }

        // the child's exam profile (only when the student has not filled it yet)
        StudentExamProfile::firstOrCreate(
            ['user_id' => $student->id],
            [
                'filled_by' => 'parent',
                'grade' => 10,
                'is_graduate' => false,
                'subjects' => ['math', 'physics'],
                'goal' => 'otm_grant',
                'school_name' => '14-maktab',
                'parent_phone' => self::PHONE,
                'parent_consent_at' => now(),
                'parent_consent_source' => 'web_parent',
                'show_name_publicly' => false,
                'onboarded_at' => now(),
            ]
        );

        GuardianStudent::firstOrCreate(
            ['guardian_id' => $guardian->id, 'student_id' => $student->id],
            [
                'relation' => 'mother',
                'consent_at' => now(),
                'consent_source' => 'web',
                'receives_reports' => true,
                'verified_at' => now(),
            ]
        );

        $this->command?->info('✅ Ota-ona (guardian) yaratildi, student@test.uz ga bog\'langan:');
        $this->command?->line('   📧 Email: '.self::EMAIL.'  📱 '.self::PHONE);
        $this->command?->line('   🔑 Parol: password123');
    }
}
