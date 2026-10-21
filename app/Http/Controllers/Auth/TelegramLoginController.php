<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TelegramLoginService;
use App\Services\Telegram\BotText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md). JSON endpoints of the
 * login / register pages (web group: session + CSRF, guest only):
 *
 *   POST /login/phone          {phone, remember}  -> the bot sends a code (same answer for any phone)
 *   POST /login/phone/verify   {code}             -> logs in, {redirect}
 *   POST /login/telegram       {role, remember}   -> t.me/<bot>?start=login_<token> + QR
 *   POST /login/telegram/status                   -> polled; logs in once the bot / Mini App confirmed
 */
class TelegramLoginController extends Controller
{
    public function __construct(private readonly TelegramLoginService $logins) {}

    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'remember' => ['nullable', 'boolean'],
        ], ['phone.required' => 'Telefon raqamni kiriting.']);

        $phone = TelegramLoginService::normalizePhone($data['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => self::text('login.phone_invalid')]);
        }

        $this->logins->sendCode($request, $phone, (bool) ($data['remember'] ?? false));

        return response()->json([
            'message' => self::text('login.web_sent'),
            'hint' => self::text('login.web_hint'),
            'phone' => $phone,
            'expires_in' => TelegramLoginService::CODE_TTL,
            'resend_in' => 60,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']], ['code.required' => 'Kodni kiriting.']);
        $code = preg_replace('/\D+/', '', $data['code']);
        if (strlen($code) !== 6) {
            throw ValidationException::withMessages(['code' => 'Kod 6 ta raqamdan iborat.']);
        }

        $result = $this->logins->verifyCode($request, $code);
        if (is_string($result)) {
            throw ValidationException::withMessages(['code' => self::text($result, ['left' => $this->logins->attemptsLeft($request)])]);
        }

        return response()->json(['redirect' => $this->logins->home($request, $result)]);
    }

    public function startLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:student,guardian'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $local = app()->environment('local');
        $username = ltrim((string) config('exam.telegram.bot_username'), '@');
        if ($username === '' && ! $local) {
            return response()->json(['message' => self::text('login.not_configured')], 503);
        }

        [, $token] = $this->logins->startLink($request, $data['role'] ?? null, (bool) ($data['remember'] ?? false));
        $link = BotText::botLink('login_'.$token);

        return response()->json([
            'link' => $link,
            'qr' => $link ? (string) QrCode::format('svg')->size(176)->margin(0)->errorCorrection('M')->generate($link) : null,
            // local emulator: /telegram/dev plays the bot (docs/auth/TELEGRAM_LOGIN.md §7)
            'dev_link' => $local ? route('telegram.dev.index', ['bot_start' => 'login_'.$token], false) : null,
            'expires_in' => TelegramLoginService::LINK_TTL,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json($this->logins->status($request));
    }

    private static function text(string $key, array $replace = []): string
    {
        return strip_tags(BotText::get($key, $replace));
    }
}
