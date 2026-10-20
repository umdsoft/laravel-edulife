<?php

namespace Tests\Feature\Exam;

use App\Events\Exam\ExamAttemptGraded;
use App\Events\Exam\ExamSessionOpened;
use App\Events\Exam\WeeklyReportReady;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramNotification;
use App\Models\User;
use App\Services\Telegram\TelegramReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Stage 6: reminder windows and recipients, and the exam event listeners
 * (only when the bot is configured, each message once).
 */
class TelegramNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        config([
            'exam.telegram.bot_token' => '111:TEST',
            'exam.telegram.mini_app_url' => 'https://exam.example.uz/telegram/app',
            'exam.telegram.reminders.eve_time' => '19:00',
            'exam.telegram.reminders.before_minutes' => 60,
            'exam.telegram.reminders.start_grace_minutes' => 15,
        ]);
        $this->math = Subject::factory()->math()->create();
    }

    private function examSession(Carbon $startsAt, array $attrs = []): ExamSession
    {
        return ExamSession::factory()->create([
            'subject_id' => $this->math->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
            'registration_closes_at' => null,
            'title' => 'Shanba imtihoni',
        ] + $attrs);
    }

    private function student(array $subjects = ['math'], array $account = []): User
    {
        $user = User::factory()->create(['role' => 'student']);
        StudentExamProfile::factory()->create(['user_id' => $user->id, 'subjects' => $subjects]);
        TelegramAccount::factory()->create(['user_id' => $user->id] + $account);

        return $user;
    }

    private function sentCount(): int
    {
        return count(Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/sendMessage')));
    }

    // ---- reminders ----

    public function test_reminder_windows_friday_evening_hour_before_and_start(): void
    {
        $saturday = Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent');
        $session = $this->examSession($saturday);
        $service = app(TelegramReminderService::class);

        $at = fn (string $t) => $service->kindAt($session, Carbon::parse($t, 'Asia/Tashkent'));
        $this->assertNull($at('2026-10-23 18:59'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_EVE, $at('2026-10-23 19:00'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_EVE, $at('2026-10-24 08:59'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_HOUR, $at('2026-10-24 09:00'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_HOUR, $at('2026-10-24 09:59'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_START, $at('2026-10-24 10:00'));
        $this->assertSame(TelegramNotification::KIND_REMINDER_START, $at('2026-10-24 10:14'));
        $this->assertNull($at('2026-10-24 10:15'));
    }

    public function test_due_selects_only_weekly_scheduled_or_open_sessions(): void
    {
        $now = Carbon::parse('2026-10-23 20:00', 'Asia/Tashkent');
        $weekly = $this->examSession(Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent'));
        $this->examSession(Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent'), ['status' => 'cancelled']);
        $this->examSession(Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent'), ['type' => 'open_regional']);
        $this->examSession(Carbon::parse('2026-10-31 10:00', 'Asia/Tashkent')); // next week: too early

        $due = app(TelegramReminderService::class)->due($now);

        $this->assertCount(1, $due);
        $this->assertTrue($due[0]['session']->is($weekly));
        $this->assertSame(TelegramNotification::KIND_REMINDER_EVE, $due[0]['kind']);
    }

    public function test_reminder_goes_to_students_of_the_subject_once(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 09:10', 'Asia/Tashkent'));
        $this->examSession(Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent'));
        $mathStudent = $this->student(['math']);
        $this->student(['physics']);                         // other subject
        $this->student(['math'], ['is_blocked' => true]);    // blocked the bot
        TelegramAccount::factory()->unlinked()->create();    // not registered

        $this->artisan('exam:telegram-reminders')->assertSuccessful();
        $this->artisan('exam:telegram-reminders')->assertSuccessful();

        $this->assertSame(1, $this->sentCount());
        $chatId = TelegramAccount::query()->where('user_id', $mathStudent->id)->value('chat_id');
        Http::assertSent(fn (Request $r) => $r->data()['chat_id'] === $chatId && str_contains($r->data()['text'], '1 soatdan keyin'));
        $this->assertDatabaseHas('telegram_notifications', ['kind' => 'reminder_hour', 'status' => 'sent']);
    }

    public function test_reminders_do_nothing_without_the_bot_token(): void
    {
        config(['exam.telegram.bot_token' => '']);
        $this->travelTo(Carbon::parse('2026-10-24 09:10', 'Asia/Tashkent'));
        $this->examSession(Carbon::parse('2026-10-24 10:00', 'Asia/Tashkent'));
        $this->student();

        $this->artisan('exam:telegram-reminders')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, TelegramNotification::count());
    }

    public function test_session_opened_event_sends_the_start_reminder_once(): void
    {
        $session = $this->examSession(now()->subMinute());
        $this->student();

        ExamSessionOpened::dispatch($session);
        app(TelegramReminderService::class)->send($session, TelegramNotification::KIND_REMINDER_START);

        $this->assertSame(1, $this->sentCount());
        Http::assertSent(fn (Request $r) => str_contains($r->data()['text'], 'boshlandi'));
    }

    // ---- graded attempt ----

    private function gradedAttempt(User $student): ExamAttempt
    {
        $session = $this->examSession(now()->subHours(3));

        return ExamAttempt::factory()->graded(24, 30)->create([
            'variant_id' => \App\Models\Exam\ExamVariant::factory()->create(['session_id' => $session->id, 'user_id' => $student->id])->id,
        ]);
    }

    public function test_graded_attempt_goes_to_the_student_and_approved_guardians_only(): void
    {
        $student = $this->student();
        $approved = User::factory()->create(['role' => 'guardian']);
        $pending = User::factory()->create(['role' => 'guardian']);
        TelegramAccount::factory()->create(['user_id' => $approved->id]);
        TelegramAccount::factory()->create(['user_id' => $pending->id]);
        GuardianStudent::factory()->create(['guardian_id' => $approved->id, 'student_id' => $student->id, 'verified_at' => now()]);
        GuardianStudent::factory()->create(['guardian_id' => $pending->id, 'student_id' => $student->id, 'verified_at' => null]);

        $attempt = $this->gradedAttempt($student);
        ExamAttemptGraded::dispatch($attempt);
        ExamAttemptGraded::dispatch($attempt); // regrade / duplicate event: no second message

        $this->assertSame(2, $this->sentCount());
        $studentChat = TelegramAccount::query()->where('user_id', $student->id)->value('chat_id');
        $guardianChat = TelegramAccount::query()->where('user_id', $approved->id)->value('chat_id');
        Http::assertSent(fn (Request $r) => $r->data()['chat_id'] === $studentChat && str_contains($r->data()['text'], '24 / 30'));
        Http::assertSent(fn (Request $r) => $r->data()['chat_id'] === $guardianChat && str_contains($r->data()['text'], $student->first_name));
    }

    public function test_listeners_send_nothing_when_the_bot_is_not_configured(): void
    {
        config(['exam.telegram.bot_token' => '']);
        $student = $this->student();
        $guardian = User::factory()->create(['role' => 'guardian']);
        TelegramAccount::factory()->create(['user_id' => $guardian->id]);
        $link = GuardianStudent::factory()->create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'verified_at' => now()]);

        ExamAttemptGraded::dispatch($this->gradedAttempt($student));
        WeeklyReportReady::dispatch($link, ['week_start' => '2026-10-19', 'student' => ['first_name' => 'Aziz'], 'subjects' => [], 'recommendations' => []]);

        Http::assertNothingSent();
        $this->assertSame(0, TelegramNotification::count());
    }

    // ---- Sunday report ----

    public function test_weekly_report_goes_to_the_guardian_telegram(): void
    {
        $student = $this->student();
        $guardian = User::factory()->create(['role' => 'guardian']);
        TelegramAccount::factory()->create(['user_id' => $guardian->id]);
        $link = GuardianStudent::factory()->create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'verified_at' => now()]);

        $report = [
            'week_start' => '2026-10-19',
            'student' => ['first_name' => 'Aziz'],
            'subjects' => [
                ['code' => 'math', 'name' => 'Matematika', 'score' => 24, 'max_score' => 30, 'percent' => 80, 'delta' => 6.5, 'topics' => []],
                ['code' => 'physics', 'name' => 'Fizika', 'percent' => null],
            ],
            'recommendations' => [['code' => 'math.trig', 'name' => 'Trigonometriya', 'subject' => 'math', 'score' => 40]],
            'report_id' => 'rep-1',
            'url' => 'https://exam.example.uz/guardian/reports/rep-1',
        ];
        WeeklyReportReady::dispatch($link, $report);
        WeeklyReportReady::dispatch($link, $report);

        $this->assertSame(1, $this->sentCount());
        Http::assertSent(function (Request $r) {
            $text = $r->data()['text'];

            return str_contains($text, 'Aziz') && str_contains($text, 'Matematika: <b>80%</b> (+6,5)')
                && str_contains($text, 'Fizika: bu hafta imtihon topshirilmadi') && str_contains($text, 'Trigonometriya')
                && ($r->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null) === 'https://exam.example.uz/guardian/reports/rep-1';
        });
    }

    public function test_weekly_report_is_not_sent_for_unapproved_links(): void
    {
        $student = $this->student();
        $guardian = User::factory()->create(['role' => 'guardian']);
        TelegramAccount::factory()->create(['user_id' => $guardian->id]);
        $link = GuardianStudent::factory()->create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'verified_at' => null]);

        WeeklyReportReady::dispatch($link, ['week_start' => '2026-10-19', 'student' => ['first_name' => 'Aziz'], 'subjects' => []]);

        $this->assertSame(0, $this->sentCount());
    }
}
