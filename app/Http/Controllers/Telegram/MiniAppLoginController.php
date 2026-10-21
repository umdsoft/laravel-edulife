<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\TelegramAccount;
use App\Services\Auth\TelegramLoginService;
use App\Services\Exam\GuardianLinkService;
use App\Services\Telegram\BotText;
use App\Services\Telegram\InvalidInitData;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramStartParam;
use App\Services\Telegram\WebviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Website login, the Telegram side (agent L, docs/auth/TELEGRAM_LOGIN.md §3): the bot's web_app
 * button "Saytga kirishni tasdiqlash" opens /telegram/app?to=login_<token>, which lands here.
 *
 * GET  /telegram/app/login?t=<token>  Inertia page Telegram/LoginConfirm (no login form, ever)
 * POST /telegram/app/login            {init_data, token, contact?} -> confirmed | need_contact |
 *                                     register | refused | expired
 *
 * Authenticated by the initData HMAC on every call; JSON only; CSRF-exempt like /telegram/app/auth.
 * A confirmed Telegram user is also signed in inside the Mini App (the same users.id as the website).
 */
class MiniAppLoginController extends Controller
{
    public function show(Request $request): Response
    {
        $token = (string) $request->query('t', '');

        return Inertia::render('Telegram/LoginConfirm', [
            'token' => preg_match('/^[A-Za-z0-9]{32}$/', $token) ? $token : null,
        ]);
    }

    public function confirm(Request $request, TelegramInitData $verifier, TelegramLoginService $logins, TelegramStartParam $start): JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => BotText::get('app.error')], 415);
        }
        $data = $request->validate([
            'init_data' => ['required', 'string', 'max:8192'],
            'token' => ['required', 'string', 'size:32'],
            'contact' => ['nullable', 'string', 'max:4096'],
        ]);

        try {
            $verified = $verifier->verify($data['init_data']);
        } catch (InvalidInitData $e) {
            Log::info('telegram login: mini app initData rejected', ['reason' => $e->reason]);

            return response()->json(['status' => 'error', 'message' => BotText::get('app.invalid')], 401);
        }
        $tg = $verified['user'];
        $account = $this->account($tg);
        WebviewSession::mark($request);

        $login = $logins->findLink($data['token']);
        if ($login === null) {
            return response()->json(['status' => 'expired', 'message' => strip_tags(BotText::get('login.expired'))]);
        }

        // the phone Telegram verified: a requestContact response now, or the contact the bot received
        $phone = null;
        if (! empty($data['contact'])) {
            try {
                $phone = GuardianLinkService::normalizePhone($verifier->verifyContact($data['contact'], (int) $tg['id']));
            } catch (InvalidInitData $e) {
                Log::info('telegram login: shared contact rejected', ['reason' => $e->reason]);

                return response()->json(['status' => 'need_contact', 'message' => BotText::get('app.contact_invalid')]);
            }
            if ($phone === null) {
                return response()->json(['status' => 'need_contact', 'message' => BotText::get('reg.phone_uz_only')]);
            }
            Cache::put(MiniAppRegistrationController::CONTACT_CACHE.$tg['id'], $phone, 900);
        } else {
            $cached = Cache::get(MiniAppRegistrationController::CONTACT_CACHE.$tg['id']);
            $phone = is_string($cached) ? GuardianLinkService::normalizePhone($cached) : null;
        }

        $result = $logins->resolve($account, $login, $phone);

        return match ($result['status']) {
            'confirmed' => $this->confirmed($request, $result['user'], $start),
            'need_contact' => response()->json(['status' => 'need_contact']),
            // a new phone: the Mini App registration (TelegramRegistration rules) confirms the link when it is done
            'register' => response()->json(['status' => 'register', 'register' => route('telegram.app.register', ['to' => 'login_'.$data['token']], false)]),
            default => response()->json(['status' => $result['status'], 'message' => strip_tags(BotText::get($result['reason'] ?? 'login.expired'))]),
        };
    }

    private function confirmed(Request $request, $user, TelegramStartParam $start): JsonResponse
    {
        if ((string) Auth::id() !== (string) $user->id) {
            // no remember cookie: it would be SameSite=Lax in Telegram Web's iframe; every open signs in from initData
            Auth::login($user);
            $request->session()->regenerate();
        }
        WebviewSession::mark($request);

        return response()->json([
            'status' => 'confirmed',
            'message' => strip_tags(BotText::get('login.confirmed')),
            'redirect' => $start->resolve($user, 'exam')['url'],
            'csrf_token' => $request->session()->token(),
        ]);
    }

    private function account(array $tg): TelegramAccount
    {
        $account = TelegramAccount::query()->firstOrNew(['telegram_id' => (int) $tg['id']]);
        if (! $account->exists) {
            $account->chat_id = (int) $tg['id'];
        }
        $account->fill([
            'username' => isset($tg['username']) ? mb_substr((string) $tg['username'], 0, 64) : $account->username,
            'first_name' => isset($tg['first_name']) ? mb_substr((string) $tg['first_name'], 0, 128) : $account->first_name,
            'last_name' => isset($tg['last_name']) ? mb_substr((string) $tg['last_name'], 0, 128) : $account->last_name,
            'last_interaction_at' => now(),
        ])->save();

        return $account;
    }
}
