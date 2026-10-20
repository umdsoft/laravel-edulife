<?php

namespace Database\Seeders\Exam;

use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\User;
use App\Services\Exam\ExamPlacementService;
use App\Services\Exam\VariantGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 5 demo data, safe to run repeatedly:
 *  - the always-open placement session of every subject (from its placement blueprint);
 *  - the demo student (student@test.uz, TestUserSeeder) gets a complete exam
 *    profile (math + physics, parent consent) so the exam area opens directly;
 *  - one upcoming Saturday session (math, 10:00-13:00, scheduled) with its
 *    variants pre-generated.
 *
 *   php artisan db:seed --class="Database\Seeders\Exam\ExamDemoSessionSeeder"
 */
class ExamDemoSessionSeeder extends Seeder
{
    public const DEMO_STUDENT_EMAIL = 'student@test.uz';

    public function run(): void
    {
        $placement = app(ExamPlacementService::class);
        foreach (Subject::query()->whereIn('code', [Subject::MATH, Subject::PHYSICS])->get() as $subject) {
            $placement->publicSession($subject);
        }

        $this->demoStudentProfile();

        $math = Subject::query()->where('code', Subject::MATH)->first();
        $blueprint = $math ? ExamBlueprint::query()->where('subject_id', $math->id)
            ->purpose(ExamSessionType::Weekly)->active()->orderBy('created_at')->first() : null;
        if (! $blueprint) {
            return;
        }

        $saturday = now()->isSaturday() && now()->lt(now()->setTime(10, 0)) ? now() : now()->next(Carbon::SATURDAY);
        $starts = $saturday->copy()->setTime(10, 0);

        $session = ExamSession::query()->firstOrCreate(
            ['blueprint_id' => $blueprint->id, 'starts_at' => $starts, 'type' => ExamSessionType::Weekly->value],
            [
                'subject_id' => $math->id,
                'title' => 'Shanba imtihoni — '.$starts->format('d.m.Y').' (demo)',
                'ends_at' => $starts->copy()->setTime(13, 0),
                'registration_closes_at' => $starts->copy()->subHour(),
                'status' => ExamSessionStatus::Scheduled,
            ]
        );

        $summary = app(VariantGenerator::class)->generateForSession($session);
        $this->command?->info(sprintf(
            'Exam demo: "%s" (%s), variants: %d new, %d existing.',
            $session->title, $session->status->value, $summary['created'], $summary['existing']
        ));
    }

    private function demoStudentProfile(): void
    {
        $student = User::query()->where('email', self::DEMO_STUDENT_EMAIL)->where('role', 'student')->first();
        if (! $student || StudentExamProfile::query()->where('user_id', $student->id)->exists()) {
            return;
        }

        $attributes = [
            'user_id' => $student->id,
            'grade' => 11,
            'is_graduate' => false,
            'subjects' => [Subject::MATH, Subject::PHYSICS],
            'goal' => 'otm_grant',
            'show_name_publicly' => false,
            'public_alias' => 'Demo o\'quvchi',
        ];
        // stage 7 onboarding columns, when present
        foreach (['parent_consent_at' => now(), 'onboarded_at' => now(), 'filled_by' => 'student', 'parent_consent_source' => 'web_student'] as $column => $value) {
            if (Schema::hasColumn('exam_student_profiles', $column)) {
                $attributes[$column] = $value;
            }
        }

        StudentExamProfile::query()->create($attributes);
    }
}
