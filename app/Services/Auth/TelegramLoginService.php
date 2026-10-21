<?php

namespace App\Services\Auth;

use App\Models\Exam\TelegramAccount;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use App\Services\Exam\ReferralService;
use App\Services\Telegram\BotRegistration;
use App\Services\Telegram\BotText;
use App\Services\Telegram\TelegramMessenger;
use App\Services\Telegram\TelegramRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md).
 *
 * Two ways, both bound to the browser session that started them (the request id is kept in that
 * session, so a code / an approval / a confirmed deep link logs in only that browser):
 *
 *  code  the user types a phone; if it belongs to a student / guardian with a linked Telegram
 *        account, the bot sends a 6-digit code (HMAC at rest, 5 minutes, 5 tries, single use, a new
 *        code cancels the old one) with "Ha, bu men" / "Bu men emas" buttons;
 *  link  t.me/<bot>?start=login_<token> (sha256 at rest, 10 minutes): the bot answers with a web_app
 *        button, the Mini App confirms (an already linked Telegram account, or a phone Telegram
 *        verified -> link / register) and the waiting page logs in.
 *
 * The answer to "send me a code" never tells whether the phone exists. Staff accounts never log in
 * this way and are never linked by phone (they keep email / password). No phone, code or token
 * is ever logged.
 */
class TelegramLoginService
{
    public const CODE_TTL = 300;
    public const LINK_TTL = 600;
    public const MAX_ATTEMPTS = 5;
    public const SESSION = 'telegram_login';
    public const ROLES = ['student', 'guardian'];
    public const TOKEN_PATTERN = '/^login_([A-Za-z0-9]{32})$/';

    public function __construct(
        private readonly TelegramMessenger $messenger,
        private readonly TelegramRegistration $registration,
        private readonly ReferralService $referrals,
    ) {}

    public static function normalizePhone(?string $raw): ?string
    {
        return BotRegistration::normalizePhone((string) $raw);
    }

    /** "login_<token>" -> token, else null. */
    public static function tokenFromParam(?string $param): ?string
    {
        return is_string($param) && preg_match(self::TOKEN_PATTERN, $param, $m) ? $m[1] : null;
    }

    // ------------------------------------------------------------------ code ----------

    /** Issues a code; delivers it only when the phone has a linked Telegram account. Same outcome for the caller either way. */
    public function sendCode(Request $request, string $phone, bool $remember): TelegramLoginRequest
    {
        $user = User::query()->where('phone', $phone)->first();
        $account = null;
        if ($user !== null && in_array($user->role, self::ROLES, true) && $user->status !== 'blocked') {
            $account = TelegramAccount::query()->where('user_id', $user->id)->latest('linked_at')->first();
        }

        // a new code invalidates the old ones (this browser's and this user's)
        $this->cancelSessionRequest($request, TelegramLoginRequest::KIND_CODE, 'replaced');
        if ($account !== null) {
            TelegramLoginRequest::query()->where('user_id', $user->id)->where('kind', TelegramLoginRequest::KIND_CODE)
                ->where('status', TelegramLoginRequest::PENDING)
                ->update(['status' => TelegramLoginRequest::CANCELLED, 'reason' => 'replaced']);
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $login = TelegramLoginRequest::create([
            'kind' => TelegramLoginRequest::KIND_CODE,
            'user_id' => $account !== null ? $user->id : null,
            'telegram_account_id' => $account?->id,
            'remember' => $remember,
            'expires_at' => now()->addSeconds(self::CODE_TTL),
        ]);
        $login->forceFill(['code_hash' => $this->hashCode($login->id, $code)])->save();
        $this->remember($request, $login);

        $delivered = false;
        if ($account !== null) {
            $response = $this->messenger->send($account, BotText::get('login.code', ['code' => $code, 'minutes' => (int) (self::CODE_TTL / 60)]), BotText::inline([
                [['text' => self::button('copy'), 'copy_text' => ['text' => $code]]],
                [
                    ['text' => self::button('approve'), 'callback_data' => 'login_ok:'.$login->id],
                    ['text' => self::button('deny'), 'callback_data' => 'login_no:'.$login->id],
                ],
            ]));
            $delivered = $response->ok;
            if (! $delivered) {
                $login->forceFill(['reason' => $response->isBlocked() ? 'bot_blocked' : 'not_delivered'])->save();
            }
        }
        Log::info('telegram login: code requested', ['request' => $login->id, 'delivered' => $delivered]);

        return $login;
    }

    /** @return User|string the logged-in user, or an error key (exam_bot.login.*) */
    public function verifyCode(Request $request, string $code): User|string
    {
        $login = $this->sessionRequest($request, TelegramLoginRequest::KIND_CODE);
        if ($login === null || ! in_array($login->status, [TelegramLoginRequest::PENDING, TelegramLoginRequest::APPROVED], true)) {
            return 'login.code_expired';
        }
        if ($login->expires_at->isPast()) {
            $this->close($login, TelegramLoginRequest::EXPIRED, 'timeout');

            return 'login.code_expired';
        }
        if ($login->attempts >= self::MAX_ATTEMPTS) {
            return 'login.code_attempts_over';
        }

        TelegramLoginRequest::query()->whereKey($login->id)->increment('attempts');
        $login->refresh();
        $ok = $login->user_id !== null && $login->code_hash !== null && hash_equals($login->code_hash, $this->hashCode($login->id, $code));
        if (! $ok) {
            if ($login->attempts >= self::MAX_ATTEMPTS) {
                $this->close($login, TelegramLoginRequest::EXPIRED, 'attempts');

                return 'login.code_attempts_over';
            }

            return 'login.code_wrong';
        }

        return $this->consume($request, $login, 'telegram_code');
    }

    public function attemptsLeft(Request $request): int
    {
        $login = $this->sessionRequest($request, TelegramLoginRequest::KIND_CODE);

        return $login === null ? 0 : max(0, self::MAX_ATTEMPTS - $login->attempts);
    }

    /** Bot callback "login_ok:<id>" / "login_no:<id>". @return string reply key */
    public function answerCode(TelegramAccount $account, string $id, bool $approve): string
    {
        $login = Str::isUuid($id) ? TelegramLoginRequest::query()->find($id) : null;
        if ($login === null || $login->kind !== TelegramLoginRequest::KIND_CODE || ! $login->isOpen()
            || $login->telegram_account_id !== $account->id || $login->user_id !== $account->user_id) {
            return 'login.approve_expired';
        }

        $done = TelegramLoginRequest::query()->whereKey($login->id)->where('status', TelegramLoginRequest::PENDING)->update($approve
            ? ['status' => TelegramLoginRequest::APPROVED, 'approved_at' => now()]
            : ['status' => TelegramLoginRequest::CANCELLED, 'reason' => 'denied']);
        Log::info('telegram login: code answered in the bot', ['request' => $login->id, 'approved' => $approve && $done > 0]);

        return $done === 0 ? 'login.approve_expired' : ($approve ? 'login.approved' : 'login.denied');
    }

    // ------------------------------------------------------------------ deep link -----

    /** @return array{0: TelegramLoginRequest, 1: string} the request and its token */
    public function startLink(Request $request, ?string $role, bool $remember): array
    {
        $this->cancelSessionRequest($request, TelegramLoginRequest::KIND_LINK, 'replaced');
        $token = Str::random(32);
        $login = TelegramLoginRequest::create([
            'kind' => TelegramLoginRequest::KIND_LINK,
            'role' => in_array($role, self::ROLES, true) ? $role : null,
            'remember' => $remember,
            'expires_at' => now()->addSeconds(self::LINK_TTL),
        ]);
        $login->forceFill(['token_hash' => hash('sha256', $token)])->save();
        $this->remember($request, $login);
        Log::info('telegram login: deep link issued', ['request' => $login->id]);

        return [$login, $token];
    }

    public function findLink(string $token): ?TelegramLoginRequest
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            return null;
        }

        return TelegramLoginRequest::query()->where('token_hash', hash('sha256', $token))
            ->where('kind', TelegramLoginRequest::KIND_LINK)->first();
    }

    /**
     * The Telegram side of a deep link (bot /start, the Mini App, the chat fallback).
     *
     * @param  ?string  $phone  a phone Telegram verified as this user's (shared contact), or null
     * @return array{status: string, reason?: string, user?: User}
     *         status: confirmed | need_contact | register | refused | expired
     */
    public function resolve(TelegramAccount $account, TelegramLoginRequest $login, ?string $phone = null): array
    {
        // a second open of an already confirmed link by the same Telegram user (e.g. after the registration)
        if (in_array($login->status, [TelegramLoginRequest::APPROVED, TelegramLoginRequest::USED], true)
            && $login->telegram_account_id === $account->id && $login->user_id !== null && $login->user_id === $account->user_id) {
            return ['status' => 'confirmed', 'user' => $login->user];
        }
        if (! $login->isOpen() || ($login->telegram_account_id !== null && $login->telegram_account_id !== $account->id)) {
            return ['status' => 'expired', 'reason' => 'login.expired'];
        }
        if (! $this->claim($login, $account)) {
            return ['status' => 'expired', 'reason' => 'login.expired'];
        }

        $account->loadMissing('user');
        $user = $account->user;
        if ($user === null && $phone !== null) {
            $linked = $this->registration->linkByVerifiedPhone($account, $phone);
            if (is_string($linked)) {
                return $this->refuse($login, $linked);
            }
            if ($linked === null) {
                return ['status' => 'register'];
            }
            $user = $linked;
            $account->setRelation('user', $user);
        }
        if ($user === null) {
            return ['status' => 'need_contact'];
        }
        if ($user->status === 'blocked') {
            return $this->refuse($login, 'login.blocked');
        }
        if (! in_array($user->role, self::ROLES, true)) {
            return $this->refuse($login, 'login.staff');
        }

        return $this->approve($login, $user) ? ['status' => 'confirmed', 'user' => $user] : ['status' => 'expired', 'reason' => 'login.expired'];
    }

    /**
     * The first Telegram user who opens a link owns it (bot /start or the Mini App): a QR code seen by
     * someone else stays useless to them. @return bool this account owns the (open) link
     */
    public function claim(TelegramLoginRequest $login, TelegramAccount $account): bool
    {
        if (! $login->isOpen()) {
            return false;
        }
        if ($login->telegram_account_id === null) {
            TelegramLoginRequest::query()->whereKey($login->id)->whereNull('telegram_account_id')->update(['telegram_account_id' => $account->id]);
            $login->telegram_account_id = TelegramLoginRequest::query()->whereKey($login->id)->value('telegram_account_id');
        }

        return $login->telegram_account_id === $account->id;
    }

    /** The Mini App registration (or the chat fallback) created the account for this link. */
    public function confirmRegistered(TelegramAccount $account, string $token, User $user): bool
    {
        $login = $this->findLink($token);
        if ($login === null || ! $login->isOpen() || ($login->telegram_account_id !== null && $login->telegram_account_id !== $account->id)) {
            return false;
        }
        $login->telegram_account_id = $account->id;

        return $this->approve($login, $user);
    }

    /** Chat fallback: a new phone gets a bare account (the website onboarding asks the rest). */
    public function registerBare(TelegramAccount $account, TelegramLoginRequest $login, string $phone): array
    {
        $user = $this->registration->bare($account, $phone, $login->role ?? 'student');
        if (is_string($user)) {
            return $this->refuse($login, $user);
        }

        return $this->approve($login, $user) ? ['status' => 'confirmed', 'user' => $user, 'new' => true] : ['status' => 'expired', 'reason' => 'login.expired'];
    }

    // ------------------------------------------------------------------ the browser ----

    /**
     * Polled by the waiting page: logs in when the bot / the Mini App approved one of this
     * session's requests. @return array<string, mixed>
     */
    public function status(Request $request): array
    {
        $out = [];
        foreach ([TelegramLoginRequest::KIND_LINK, TelegramLoginRequest::KIND_CODE] as $kind) {
            $login = $this->sessionRequest($request, $kind);
            if ($login === null) {
                continue;
            }
            if ($login->status === TelegramLoginRequest::APPROVED) {
                $user = $this->consume($request, $login, $kind === TelegramLoginRequest::KIND_CODE ? 'telegram_approve' : 'telegram_link');
                if ($user instanceof User) {
                    // an account created through this link: the invite code this browser
                    // remembered (/r/{code}, /register?ref=) counts as its referral
                    if ($kind === TelegramLoginRequest::KIND_LINK && $user->created_at?->gte($login->created_at)) {
                        $this->referrals->captureForNewUser($user, $request);
                    }

                    return ['status' => 'ok', 'redirect' => $this->home($request, $user)];
                }
                continue;
            }
            $state = $login->status === TelegramLoginRequest::PENDING && $login->expires_at->isPast() ? TelegramLoginRequest::EXPIRED : $login->status;
            $out[$kind] = ['status' => $state];
            if ($state === TelegramLoginRequest::REFUSED && $login->reason) {
                $out[$kind]['message'] = strip_tags(BotText::get($login->reason));
            } elseif ($state === TelegramLoginRequest::CANCELLED && $login->reason === 'denied') {
                $out[$kind]['message'] = strip_tags(BotText::get('login.web_denied'));
            }
        }

        return ['status' => 'waiting'] + $out;
    }

    public function home(Request $request, User $user): string
    {
        $home = match ($user->role) {
            'super_admin', 'admin' => '/admin/dashboard',
            'teacher' => '/teacher/dashboard',
            'guardian' => '/guardian/dashboard',
            default => '/student/dashboard',
        };

        return (string) $request->session()->pull('url.intended', $home);
    }

    // ------------------------------------------------------------------ internals -----

    private function consume(Request $request, TelegramLoginRequest $login, string $method): User|string
    {
        $user = $login->user_id ? User::query()->find($login->user_id) : null;
        if ($user === null || $user->status === 'blocked' || ! in_array($user->role, self::ROLES, true)) {
            $this->close($login, TelegramLoginRequest::REFUSED, 'login.blocked');

            return 'login.code_expired';
        }
        $used = TelegramLoginRequest::query()->whereKey($login->id)
            ->whereIn('status', [TelegramLoginRequest::PENDING, TelegramLoginRequest::APPROVED])
            ->update(['status' => TelegramLoginRequest::USED, 'used_at' => now()]);
        if ($used === 0) {
            return 'login.code_expired';
        }

        $request->session()->forget(self::SESSION);
        $request->attributes->set('login_method', $method); // AuditLog description (AppServiceProvider)
        Auth::login($user, $login->remember);
        $request->session()->regenerate();
        Log::info('telegram login: signed in', ['request' => $login->id, 'user' => $user->id, 'method' => $method]);

        return $user;
    }

    private function approve(TelegramLoginRequest $login, User $user): bool
    {
        $done = TelegramLoginRequest::query()->whereKey($login->id)->where('status', TelegramLoginRequest::PENDING)
            ->update(['status' => TelegramLoginRequest::APPROVED, 'user_id' => $user->id, 'telegram_account_id' => $login->telegram_account_id, 'approved_at' => now()]);
        Log::info('telegram login: deep link confirmed', ['request' => $login->id, 'user' => $user->id, 'ok' => $done > 0]);

        return $done > 0;
    }

    /** @return array{status: string, reason: string} */
    private function refuse(TelegramLoginRequest $login, string $reason): array
    {
        $reason = str_starts_with($reason, 'reg.') ? match ($reason) {
            'reg.already_linked' => 'login.other_telegram',
            'reg.role_conflict' => 'login.staff',
            default => 'login.refused',
        } : $reason;
        $this->close($login, TelegramLoginRequest::REFUSED, $reason);
        Log::info('telegram login: refused', ['request' => $login->id, 'reason' => $reason]);

        return ['status' => 'refused', 'reason' => $reason];
    }

    private function close(TelegramLoginRequest $login, string $status, ?string $reason): void
    {
        TelegramLoginRequest::query()->whereKey($login->id)->whereIn('status', [TelegramLoginRequest::PENDING, TelegramLoginRequest::APPROVED])
            ->update(['status' => $status, 'reason' => $reason]);
        $login->status = $status;
        $login->reason = $reason;
    }

    private function remember(Request $request, TelegramLoginRequest $login): void
    {
        $request->session()->put(self::SESSION.'.'.$login->kind, $login->id);
    }

    private function sessionRequest(Request $request, string $kind): ?TelegramLoginRequest
    {
        $id = $request->session()->get(self::SESSION.'.'.$kind);

        return is_string($id) && Str::isUuid($id) ? TelegramLoginRequest::query()->find($id) : null;
    }

    private function cancelSessionRequest(Request $request, string $kind, string $reason): void
    {
        $login = $this->sessionRequest($request, $kind);
        if ($login !== null && $login->status === TelegramLoginRequest::PENDING) {
            $this->close($login, TelegramLoginRequest::CANCELLED, $reason);
        }
    }

    private function hashCode(string $id, string $code): string
    {
        return hash_hmac('sha256', $id.'|'.$code, (string) config('app.key'));
    }

    public static function button(string $key): string
    {
        return (string) __('exam_bot.login.btn.'.$key, [], 'uz');
    }
}
