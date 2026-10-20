<?php

namespace App\Services\Telegram;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Http\Controllers\Telegram\MiniAppRegistrationController;
use App\Services\Exam\ReferralService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Handles one Telegram update (webhook body): /start [ref_<code> | link_<token>],
 * the registration buttons, contact, /natijalar, /taklif, /yordam, /bekor,
 * and (Telegram WebView) /imtihon, /battle with web_app buttons.
 * Only private chats are served.
 *
 * TW (WebView-first): the chat only opens the Mini App and delivers notifications.
 * /start answers with one short message and a web_app "Ochish" button; registration,
 * results and the invite page happen in the WebView. The 5-question chat registration
 * stays as a fallback (/royxat) for clients without Mini Apps. A contact the user shares
 * from the Mini App (requestContact) also reaches the bot as a message: it is kept for
 * the WebView registration (MiniAppRegistrationController::CONTACT_CACHE), no reply.
 * my_chat_member tells when the user blocks / unblocks the bot.
 */
class BotUpdateHandler
{
    public function __construct(
        private readonly TelegramClient $client,
        private readonly TelegramMessenger $messenger,
        private readonly BotRegistration $registration,
        private readonly TelegramLinkService $links,
    ) {}

    public function handle(array $update): void
    {
        // TW: the user blocked (kicked) or unblocked the bot
        if (is_array($update['my_chat_member'] ?? null)) {
            $this->onChatMember($update['my_chat_member']);

            return;
        }

        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;
        $from = $message['from'] ?? $callback['from'] ?? null;
        $chat = $message['chat'] ?? $callback['message']['chat'] ?? null;

        if (! is_array($from) || ! isset($from['id']) || ($from['is_bot'] ?? false)) {
            return;
        }
        if (is_array($chat) && ($chat['type'] ?? 'private') !== 'private') {
            return;
        }

        // Per-user flood guard (the route also has an IP rate limit).
        if (RateLimiter::tooManyAttempts('telegram-user:'.$from['id'], 30)) {
            return;
        }
        RateLimiter::hit('telegram-user:'.$from['id'], 60);

        $account = $this->touchAccount($from, is_array($chat) ? ($chat['id'] ?? null) : null);

        if (is_array($callback)) {
            $this->onCallback($account, $callback);

            return;
        }
        if (! is_array($message)) {
            return;
        }

        if (isset($message['contact']) && is_array($message['contact'])) {
            $this->onContact($account, $message['contact']);

            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if ($text === '') {
            return;
        }

        if (preg_match('/^\/start(?:@\w+)?(?:\s+(\S+))?$/u', $text, $m)) {
            $this->onStart($account, $m[1] ?? null);

            return;
        }

        $command = strtolower(strtok($text, " @") ?: '');
        match ($command) {
            '/natijalar', '/results' => $this->sendResults($account),
            // Telegram WebView: the Mini App straight from the chat
            '/imtihon', '/exam' => $this->sendExam($account),
            '/battle', '/jang' => $this->sendBattle($account),
            '/taklif', '/invite' => $this->sendInvite($account),
            '/yordam', '/help' => $this->messenger->send($account, BotText::get('help')),
            '/menu' => $this->sendMenu($account),
            '/bekor', '/cancel' => $this->cancel($account),
            // TW: the chat registration is only a fallback for clients without Mini Apps
            '/royxat', '/register' => $this->restart($account),
            default => $this->onText($account, $text),
        };
    }

    private function touchAccount(array $from, int|string|null $chatId): TelegramAccount
    {
        $account = TelegramAccount::query()->firstOrNew(['telegram_id' => (int) $from['id']]);
        $account->fill([
            'chat_id' => $chatId !== null ? (int) $chatId : ($account->chat_id ?? (int) $from['id']),
            'username' => isset($from['username']) ? mb_substr((string) $from['username'], 0, 64) : null,
            'first_name' => isset($from['first_name']) ? mb_substr((string) $from['first_name'], 0, 128) : null,
            'last_name' => isset($from['last_name']) ? mb_substr((string) $from['last_name'], 0, 128) : null,
            'language_code' => isset($from['language_code']) ? mb_substr((string) $from['language_code'], 0, 10) : null,
            'is_blocked' => false, // they are writing to us
            'last_interaction_at' => now(),
        ])->save();

        return $account;
    }

    private function onStart(TelegramAccount $account, ?string $payload): void
    {
        if ($payload !== null && preg_match('/^link_([A-Za-z0-9]{32})$/', $payload, $m)) {
            $linked = $this->links->consume($account, $m[1]);
            $this->messenger->send($account, BotText::get($linked ? 'link.done' : 'link.expired'));
            if ($linked) {
                $this->sendMenu($account->refresh());
            }

            return;
        }

        // Remember the invite code for the referral funnel (recorded at registration).
        if ($payload !== null && preg_match('/^ref_[A-Za-z0-9]{4,32}$/i', $payload) && ! $account->isLinked()) {
            $account->forceFill(['start_payload' => mb_substr($payload, 0, 64)])->save();
        }

        if ($account->isLinked()) {
            $this->sendMenu($account, true);

            return;
        }

        // TW: one short message + "Ochish"; the Mini App shows the registration screen
        $this->messenger->send($account, BotText::get('welcome_new'), BotText::inline([[BotText::appButton('open', 'exam')]]));
    }

    private function onCallback(TelegramAccount $account, array $callback): void
    {
        $data = (string) ($callback['data'] ?? '');
        if (isset($callback['id'])) {
            $this->client->answerCallbackQuery((string) $callback['id']);
        }

        if (preg_match('/^reg:([a-z_]+):([a-z0-9_]+)$/', $data, $m)) {
            if ($account->isLinked()) {
                $this->sendMenu($account);

                return;
            }
            $this->registration->answer($account, $m[1], $m[2]);

            return;
        }

        match ($data) {
            'menu:results' => $this->sendResults($account),
            'menu:invite' => $this->sendInvite($account),
            default => $this->sendMenu($account),
        };
    }

    /**
     * message.contact: the chat registration's contact step, or (TW) the phone the user shared
     * from the Mini App's requestContact — Telegram verified it is theirs when user_id = from.id.
     */
    private function onContact(TelegramAccount $account, array $contact): void
    {
        $own = (int) ($contact['user_id'] ?? 0) === (int) $account->telegram_id;
        $phone = $own ? BotRegistration::normalizePhone((string) ($contact['phone_number'] ?? '')) : null;
        if ($phone !== null && ! $account->isLinked()) {
            Cache::put(MiniAppRegistrationController::CONTACT_CACHE.$account->telegram_id, $phone, 900);
        }

        if ($this->registration->inProgress($account) && $this->registration->state($account)?->step === BotRegistration::STEP_CONTACT) {
            $this->registration->contact($account, $contact);

            return;
        }
        if (! $own) {
            $this->messenger->send($account, BotText::get('reg.contact_not_own'));
        }
        // shared from the Mini App: the WebView continues, the chat stays quiet
    }

    private function onChatMember(array $update): void
    {
        $id = $update['from']['id'] ?? null;
        $status = (string) ($update['new_chat_member']['status'] ?? '');
        if (! is_numeric($id) || (($update['chat']['type'] ?? 'private') !== 'private')) {
            return;
        }
        $account = TelegramAccount::query()->where('telegram_id', (int) $id)->first();
        if ($account === null || ! in_array($status, ['kicked', 'member'], true)) {
            return;
        }
        $account->forceFill(['is_blocked' => $status === 'kicked'])->save();
        Log::info('telegram: chat member status', ['account' => $account->id, 'status' => $status]);
    }

    /** /bekor: stop the chat registration (fallback) and point to the Mini App. */
    private function cancel(TelegramAccount $account): void
    {
        if ($account->isLinked()) {
            $this->sendMenu($account);

            return;
        }
        $state = $this->registration->state($account);
        if ($state !== null && $state->completed_at === null) {
            $state->fill(['step' => null])->save();
        }
        $this->messenger->send($account, BotText::get('reg.cancelled'), ['remove_keyboard' => true]);
        $this->needRegistration($account);
    }

    private function onText(TelegramAccount $account, string $text): void
    {
        if (! $account->isLinked() && $this->registration->text($account, $text)) {
            return;
        }
        if (! $account->isLinked()) {
            $this->needRegistration($account);

            return;
        }
        $this->messenger->send($account, BotText::get('unknown'), BotText::inline([[BotText::appButton('open', 'exam')]]));
    }

    private function restart(TelegramAccount $account): void
    {
        if ($account->isLinked()) {
            $this->sendMenu($account);

            return;
        }
        $this->messenger->send($account, BotText::get('reg.restarted'), ['remove_keyboard' => true]);
        $this->registration->begin($account);
    }

    private function sendMenu(TelegramAccount $account, bool $greeting = false): void
    {
        $account->loadMissing('user');
        $text = $greeting
            ? BotText::get('welcome_back', ['name' => $account->user?->first_name ?: ($account->first_name ?: '')])
            : BotText::get('menu');

        // TW: everything opens in the Mini App
        $rows = [[BotText::appButton('open', 'exam')]];
        if ($account->user?->role === 'student') {
            $rows[] = [BotText::appButton('results', 'progress'), BotText::appButton('invite', 'invite')];
        } elseif ($account->user?->role === 'guardian') {
            $rows[] = [BotText::appButton('open_report', 'report')];
        }
        $this->messenger->send($account, $text, BotText::inline($rows));
    }

    /** Not registered yet: the Mini App registers (TW). */
    private function needRegistration(TelegramAccount $account): void
    {
        $this->messenger->send($account, BotText::get('results.need_registration'), BotText::inline([[BotText::appButton('open', 'exam')]]));
    }

    /** /natijalar (TW): one line and the results page in the Mini App, not a list in the chat. */
    private function sendResults(TelegramAccount $account): void
    {
        $account->loadMissing('user');
        if (! $account->isLinked()) {
            $this->needRegistration($account);

            return;
        }
        if ($account->user?->role === 'guardian') {
            $this->messenger->send($account, BotText::get('results.guardian'), BotText::inline([
                [BotText::appButton('open_children', 'exam')],
                [BotText::appButton('open_report', 'report')],
            ]));

            return;
        }

        $last = ExamAttempt::query()
            ->with('session:id,title')
            ->where('user_id', $account->user_id)
            ->where('status', AttemptStatus::Graded->value)
            ->latest('graded_at')
            ->first();

        if ($last === null) {
            $this->messenger->send($account, BotText::get('results.none'), BotText::inline([[BotText::appButton('placement', 'exam')]]));

            return;
        }

        $text = BotText::get('results.last', [
            'title' => $last->session?->title ?? '—',
            'score' => self::number($last->score),
            'max' => self::number($last->max_score),
            'percent' => self::number($last->score_percent),
        ]);
        $this->messenger->send($account, $text, BotText::inline([
            [BotText::appButton('open_progress', 'progress')],
            [BotText::appButton('open_result', 'result_'.$last->id)],
        ]));
    }

    // --- Telegram WebView (wave 2): /imtihon and /battle ------------------------------

    /** /imtihon: the exam area (running attempt / next session) with web_app buttons. */
    private function sendExam(TelegramAccount $account): void
    {
        $account->loadMissing('user.examProfile');
        $user = $account->user;
        if ($user === null) {
            $this->needRegistration($account);

            return;
        }
        if ($user->role !== 'student') {
            $this->sendMenu($account);

            return;
        }

        $lines = [BotText::get('exam_cmd.text')];
        $running = ExamAttempt::query()->where('user_id', $user->id)->inProgress()
            ->where('expires_at', '>', now())->with('session:id,title')->latest('started_at')->first();
        $rows = [];
        if ($running !== null) {
            $lines[] = BotText::get('exam_cmd.running', ['title' => $running->session?->title ?? '—']);
            $rows[] = [BotText::appButton('open_exam', 'exam_s_'.$running->session_id)];
        } else {
            $next = ExamSession::query()->public()
                ->where('type', ExamSessionType::Weekly->value)
                ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
                ->where('starts_at', '>', now()->subHours(6))
                ->orderBy('starts_at')
                ->first(['id', 'title', 'starts_at']);
            if ($next !== null) {
                $lines[] = BotText::get('exam_cmd.next', ['title' => $next->title, 'date' => $next->starts_at?->format('d.m.Y H:i')]);
            }
            $rows[] = [BotText::appButton('open_app', 'exam')];
            foreach ((array) ($user->examProfile?->subjects ?? []) as $code) {
                if (in_array($code, [Subject::MATH, Subject::PHYSICS], true) && ! $this->placementDone($user->id, $code)) {
                    $rows[] = [BotText::appButton('placement_'.$code, 'placement_'.$code)];
                }
            }
        }
        $rows[] = [BotText::appButton('results', 'progress')];

        $this->messenger->send($account, implode("\n\n", $lines), BotText::inline($rows));
    }

    /** /battle: the battle lobby in the Mini App. */
    private function sendBattle(TelegramAccount $account): void
    {
        if (! $account->isLinked()) {
            $this->needRegistration($account);

            return;
        }
        $this->messenger->send($account, BotText::get('battle.text'), BotText::inline([[BotText::appButton('open_battle', 'battle')]]));
    }

    private function placementDone(string|int $userId, string $code): bool
    {
        return ExamAttempt::query()
            ->where('user_id', $userId)
            ->where('status', AttemptStatus::Graded->value)
            ->whereHas('session', fn ($q) => $q->where('type', ExamSessionType::Placement->value)->whereHas('subject', fn ($s) => $s->where('code', $code)))
            ->exists();
    }

    // --- end Telegram WebView ---------------------------------------------------------

    /** /taklif (TW): Telegram's share sheet + the invite page in the Mini App. */
    private function sendInvite(TelegramAccount $account): void
    {
        $account->loadMissing('user');
        if ($account->user === null) {
            $this->needRegistration($account);

            return;
        }

        try {
            $code = app(ReferralService::class)->codeFor($account->user);
        } catch (\Throwable $e) {
            Log::warning('telegram: invite code failed', ['account' => $account->id, 'error' => class_basename($e)]);
            $this->messenger->send($account, BotText::get('invite.unavailable'));

            return;
        }

        $link = BotText::botLink($code->startPayload());
        if ($link === null) {
            $this->messenger->send($account, BotText::get('invite.unavailable'));

            return;
        }
        $share = 'https://t.me/share/url?url='.rawurlencode($link).'&text='.rawurlencode((string) __('exam_bot.invite.share_text', [], 'uz'));
        $rows = [[['text' => BotText::button('share_invite'), 'url' => $share]]];
        if ($account->user->role === 'student') {
            $rows[] = [BotText::appButton('invite_page', 'invite')];
        }
        $this->messenger->send($account, BotText::get('invite.short'), BotText::inline($rows));
    }

    public static function number(mixed $value): string
    {
        $value = (float) $value;

        return rtrim(rtrim(number_format($value, 1, ',', ' '), '0'), ',');
    }
}
