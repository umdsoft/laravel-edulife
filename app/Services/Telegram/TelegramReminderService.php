<?php

namespace App\Services\Telegram;

use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamSession;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Saturday exam reminders (spec section 8): the evening before (Friday for
 * a Saturday exam, exam.telegram.reminders.eve_time), one hour before and at
 * the start. php artisan exam:telegram-reminders runs every five minutes and
 * sends the reminder whose window is open now; ExamSessionOpened also sends
 * the start reminder. telegram_notifications keeps each one to one message.
 */
class TelegramReminderService
{
    public function __construct(private readonly TelegramMessenger $messenger) {}

    /**
     * Sessions with an open reminder window at $now.
     *
     * @return list<array{session: ExamSession, kind: string}>
     */
    public function due(CarbonInterface $now): array
    {
        $before = (int) config('exam.telegram.reminders.before_minutes', 60);
        $grace = (int) config('exam.telegram.reminders.start_grace_minutes', 15);

        $sessions = ExamSession::query()
            ->with('subject:id,code,name_uz')
            ->where('type', ExamSessionType::Weekly->value)
            ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$now->copy()->subMinutes($grace), $now->copy()->addDays(2)])
            ->get();

        $due = [];
        foreach ($sessions as $session) {
            $kind = $this->kindAt($session, $now, $before, $grace);
            if ($kind !== null) {
                $due[] = ['session' => $session, 'kind' => $kind];
            }
        }

        return $due;
    }

    /** The one reminder whose window contains $now (latest window wins). */
    public function kindAt(ExamSession $session, CarbonInterface $now, ?int $before = null, ?int $grace = null): ?string
    {
        $before ??= (int) config('exam.telegram.reminders.before_minutes', 60);
        $grace ??= (int) config('exam.telegram.reminders.start_grace_minutes', 15);
        $start = $session->starts_at->copy();

        $hourFrom = $start->copy()->subMinutes($before);
        $eveFrom = $start->copy()->subDay()->setTimeFromTimeString((string) config('exam.telegram.reminders.eve_time', '19:00'));

        return match (true) {
            $now->gte($start) && $now->lt($start->copy()->addMinutes($grace)) => TelegramNotification::KIND_REMINDER_START,
            $now->gte($hourFrom) && $now->lt($start) => TelegramNotification::KIND_REMINDER_HOUR,
            $now->gte($eveFrom) && $now->lt($hourFrom) => TelegramNotification::KIND_REMINDER_EVE,
            default => null,
        };
    }

    /** @return int messages sent */
    public function send(ExamSession $session, string $kind): int
    {
        if (! $this->messenger->isConfigured()) {
            return 0;
        }

        $session->loadMissing(['subject:id,code,name_uz', 'blueprint:id,duration_min']);
        $text = BotText::get(match ($kind) {
            TelegramNotification::KIND_REMINDER_EVE => 'reminder.eve',
            TelegramNotification::KIND_REMINDER_HOUR => 'reminder.hour',
            default => 'reminder.start',
        }, [
            'title' => $session->title,
            'time' => $session->starts_at?->format('H:i'),
            'duration' => (int) ($session->duration_min ?? $session->blueprint?->duration_min ?? 0),
        ]);
        $markup = BotText::inline([[BotText::appButton('open_exam', 'exam')]]);

        $sent = 0;
        foreach ($this->recipients($session) as $account) {
            $sent += (int) $this->messenger->notifyOnce($account, $kind, $session->id, $text, $markup);
        }

        return $sent;
    }

    /**
     * Linked, not blocked student accounts whose exam profile includes the
     * session's subject.
     *
     * @return Collection<int, TelegramAccount>
     */
    public function recipients(ExamSession $session): Collection
    {
        $code = $session->subject?->code;

        return TelegramAccount::query()
            ->whereNotNull('user_id')
            ->where('is_blocked', false)
            ->whereHas('user', fn ($q) => $q->where('role', 'student')->where('status', '!=', 'blocked'))
            ->with('user.examProfile')
            ->get()
            ->filter(fn (TelegramAccount $a) => $code !== null && $a->user?->examProfile?->studies($code))
            ->values();
    }
}
