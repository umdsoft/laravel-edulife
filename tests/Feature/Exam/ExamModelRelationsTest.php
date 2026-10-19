<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ReferralStatus;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\QuestionErrorReport;
use App\Models\Exam\Referral;
use App\Models\Exam\ReferralCode;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\Topic;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_exam_factory_creates_a_valid_row(): void
    {
        $models = [
            Subject::class, Topic::class, QuestionBankOption::class, QuestionBankAnswer::class,
            QuestionErrorReport::class, ExamBlueprint::class, ExamBlueprintRule::class, ExamSession::class,
            ExamVariant::class, ExamAttempt::class, ExamAnswer::class, StudentTopicMastery::class,
            StudentWeeklyResult::class, StudentExamProfile::class, GuardianStudent::class,
            TelegramAccount::class, ReferralCode::class, Referral::class,
        ];

        foreach ($models as $model) {
            $row = $model::factory()->create();
            $this->assertTrue($row->exists, $model);
            $this->assertTrue(\Illuminate\Support\Str::isUuid($row->getKey()), "{$model} must use a UUID key");
        }

        $this->assertNotNull(ExamQuestionFactory::new()->create()->getKey());
    }

    public function test_topic_tree_relations_and_descendants(): void
    {
        $subject = Subject::factory()->math()->create();
        $section = Topic::factory()->create(['subject_id' => $subject->id]);
        $topic = Topic::factory()->childOf($section)->create();
        $sub = Topic::factory()->childOf($topic)->create();
        $other = Topic::factory()->create(['subject_id' => $subject->id]);

        $this->assertSame(1, $section->fresh()->level);
        $this->assertSame(2, $topic->fresh()->level);
        $this->assertSame(3, $sub->fresh()->level);
        $this->assertTrue($topic->parent->is($section));
        $this->assertEqualsCanonicalizing([$topic->id], $section->children()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$section->id, $topic->id, $sub->id], $section->descendantAndSelfIds());
        $this->assertTrue($sub->section()->is($section));
        $this->assertEqualsCanonicalizing([$section->id, $other->id], $subject->sections()->pluck('id')->all());
    }

    public function test_topic_guards_subject_mismatch_and_cycles(): void
    {
        $section = Topic::factory()->create();
        $child = Topic::factory()->childOf($section)->create();

        try {
            Topic::factory()->create([
                'subject_id' => Subject::factory()->create()->id,
                'parent_id' => $section->id,
            ]);
            $this->fail('child of another subject accepted');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        $section->update(['parent_id' => $child->id]);
    }

    public function test_question_bank_exam_relations(): void
    {
        $question = ExamQuestionFactory::new()->approved()->create();
        $question->refresh();

        $this->assertNotNull($question->subject);
        $this->assertTrue($question->topic->subject->is($question->subject));
        $this->assertCount(4, $question->answerOptions);
        $this->assertNotNull($question->reviewer);
        $this->assertTrue($question->topic->questions()->whereKey($question->id)->exists());

        QuestionErrorReport::factory()->create(['question_id' => $question->id]);
        QuestionErrorReport::factory()->create(['question_id' => $question->id]);
        $this->assertSame(2, $question->fresh()->error_reports_count);
        $this->assertCount(2, $question->errorReports);

        $numeric = ExamQuestionFactory::new()->numericWithUnit()->approved()->create();
        $this->assertCount(1, $numeric->numericAnswers);
    }

    public function test_blueprint_session_variant_attempt_answer_chain(): void
    {
        $question = ExamQuestionFactory::new()->approved()->create();
        $blueprint = ExamBlueprint::factory()->create(['subject_id' => $question->subject_id, 'total_questions' => 1]);
        $rule = ExamBlueprintRule::factory()->create(['blueprint_id' => $blueprint->id, 'topic_id' => $question->topic_id]);
        $session = ExamSession::factory()->create(['subject_id' => $question->subject_id, 'blueprint_id' => $blueprint->id]);
        $student = User::factory()->create();

        $variant = ExamVariant::factory()->create([
            'session_id' => $session->id,
            'user_id' => $student->id,
            'question_ids' => [$question->id],
            'rule_map' => [$question->id => $rule->id],
        ]);
        $attempt = ExamAttempt::factory()->create(['variant_id' => $variant->id]);
        $answer = ExamAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $question->answerOptions()->first()->id,
        ]);

        $this->assertTrue($blueprint->isConsistent());
        $this->assertTrue($rule->blueprint->is($blueprint));
        $this->assertTrue($session->blueprint->is($blueprint));
        $this->assertSame(90, $session->effectiveDurationMin());
        $this->assertTrue($attempt->session->is($session), 'attempt session derived from the variant');
        $this->assertTrue($attempt->user->is($student));
        $this->assertTrue($variant->attempt->is($attempt));
        $this->assertSame([$question->id], $variant->orderedQuestions()->pluck('id')->all());
        $this->assertTrue($answer->selectedOption->question->is($question));
        $this->assertTrue($answer->isAnswered());
        $this->assertSame(AttemptStatus::InProgress, $attempt->status);
        $this->assertCount(1, $student->examAttempts);
        $this->assertCount(1, $session->attempts);
        $this->assertArrayNotHasKey('session_token', $attempt->toArray());
    }

    public function test_expired_scope_finds_attempts_past_deadline(): void
    {
        $late = ExamAttempt::factory()->create(['expires_at' => now()->subMinute()]);
        ExamAttempt::factory()->create(['expires_at' => now()->addHour()]);
        ExamAttempt::factory()->graded(10, 20)->create(['expires_at' => now()->subHour()]);

        $this->assertSame([$late->id], ExamAttempt::expired()->pluck('id')->all());
    }

    public function test_student_progress_relations(): void
    {
        $student = User::factory()->create();
        $mastery = StudentTopicMastery::factory()->create(['user_id' => $student->id]);
        $result = StudentWeeklyResult::factory()->create(['user_id' => $student->id]);
        $profile = StudentExamProfile::factory()->create([
            'user_id' => $student->id, 'subjects' => ['math', 'physics'],
        ]);

        $this->assertTrue($mastery->topic->subject->is($mastery->subject));
        $this->assertTrue($result->session->subject->is($result->subject));
        $this->assertTrue($student->examProfile->is($profile));
        $this->assertTrue($profile->studies('physics'));
        $this->assertCount(1, $student->topicMasteries);
        $this->assertCount(1, $student->weeklyResults);
        $this->assertStringNotContainsString($student->last_name, $profile->publicName(), 'no real name without consent');

        $this->expectException(\InvalidArgumentException::class);
        StudentTopicMastery::factory()->create(['score' => 101]);
    }

    public function test_profile_rejects_unknown_subject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StudentExamProfile::factory()->create(['subjects' => ['english']]);
    }

    public function test_guardian_links_both_directions(): void
    {
        $mother = User::factory()->create(['role' => 'guardian']);
        $kid1 = User::factory()->create();
        $kid2 = User::factory()->create();

        $mother->wards()->attach($kid1->id, ['relation' => 'mother', 'consent_at' => now()]);
        GuardianStudent::factory()->create(['guardian_id' => $mother->id, 'student_id' => $kid2->id]);

        $this->assertEqualsCanonicalizing([$kid1->id, $kid2->id], $mother->wards()->pluck('users.id')->all());
        $this->assertTrue($kid1->guardians()->first()->is($mother));
        $this->assertTrue($kid1->guardians()->first()->pivot->hasConsent());
        $this->assertTrue(\Illuminate\Support\Str::isUuid($kid1->guardians()->first()->pivot->id));

        $this->expectException(\InvalidArgumentException::class);
        GuardianStudent::factory()->create(['guardian_id' => $kid1->id, 'student_id' => $kid1->id]);
    }

    public function test_telegram_and_referrals(): void
    {
        $owner = User::factory()->create();
        $tg = TelegramAccount::factory()->create(['user_id' => $owner->id]);
        $code = ReferralCode::factory()->create(['owner_id' => $owner->id]);
        $referral = Referral::factory()->create(['referral_code_id' => $code->id]);

        $this->assertTrue($owner->telegramAccount->is($tg));
        $this->assertFalse(TelegramAccount::factory()->unlinked()->create()->isLinked());
        $this->assertMatchesRegularExpression('/^[2-9A-HJKMNP-Z]{8}$/', $code->code);
        $this->assertSame('ref_'.$code->code, $code->startPayload());
        $this->assertTrue($referral->referrer->is($owner));
        $this->assertTrue($referral->referred->referredBy->is($referral));
        $this->assertCount(1, $owner->referralsMade);

        $referral->advanceTo(ReferralStatus::PlacementDone);
        $this->assertNotNull($referral->fresh()->placement_done_at);

        $this->expectException(\InvalidArgumentException::class);
        $referral->update(['status' => ReferralStatus::Joined]);
    }
}
