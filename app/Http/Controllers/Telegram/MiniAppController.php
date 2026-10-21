<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\BotText;
use App\Services\Telegram\InvalidInitData;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramLinkService;
use App\Services\Telegram\TelegramStartParam;
use App\Services\Telegram\WebviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Telegram Mini App entry (stage 6).
 *
 * GET  /telegram/app         — a tiny page that posts Telegram.WebApp.initData
 * POST /telegram/app/auth    — verifies the initData HMAC with the bot token,
 *                              logs the linked user in and returns where to go.
 * POST /telegram/app/reauth  — the same login without a redirect (silent re-login).
 * GET  /telegram/link        — (logged in) deep link that connects the current
 *                              web account to the user's Telegram.
 *
 * Telegram WebView (wave 2): the session gets webview=telegram (compact layout,
 * Inertia prop "webview"), the start parameter / ?to= is resolved by
 * TelegramStartParam, and reauth logs the Telegram user in again when the
 * session cookie was lost (401/419 inside the Mini App).
 * TW (WebView-first): an unregistered Telegram user gets 409 + the WebView registration URL
 * (MiniAppRegistrationController); staff accounts are refused (website only); siteLink /
 * siteLogin hand the session over to the user's browser once ("Saytda ochish").
 * auth and reauth are CSRF-exempt: they are authenticated by the initData HMAC
 * and only accept JSON bodies (a cross-site form can not send application/json
 * without a CORS preflight, and this app allows no cross-origin requests).
 */
class MiniAppController extends Controller
{
    public function show(Request $request): View
    {
        $to = (string) $request->query('to', '');
        // Telegram also hands the start parameter to the page as ?tgWebAppStartParam=
        if ($to === '' && TelegramStartParam::isValid((string) $request->query('tgWebAppStartParam', ''))) {
            $to = (string) $request->query('tgWebAppStartParam');
        }
        WebviewSession::rememberDevMock($request);

        return view('telegram.mini-app', [
            'to' => TelegramStartParam::isValid($to) || preg_match('/^result:[0-9a-f\-]{36}$/i', $to) ? $to : '',
            'returnTo' => TelegramStartParam::safeReturnPath((string) $request->query('return', '')),
            'texts' => __('exam_bot.app', [], 'uz'),
            'sdkUrl' => WebviewSession::sdkUrl($request),
        ]);
    }

    public function auth(Request $request, TelegramInitData $verifier, TelegramStartParam $start): JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => BotText::get('app.error')], 415);
        }
        $data = $request->validate([
            'init_data' => ['required', 'string', 'max:8192'],
            'to' => ['nullable', 'string', 'max:80'],
            'return' => ['nullable', 'string', 'max:300'],
        ]);

        $login = $this->login($request, $verifier, $data['init_data']);
        if ($login instanceof JsonResponse) {
            return $login;
        }
        [$user, $verified] = $login;

        // auth (agent L): t.me/<bot>?start=login_<token> -> the web_app button -> confirm the waiting browser
        $loginToken = \App\Services\Auth\TelegramLoginService::tokenFromParam(($data['to'] ?? null) ?: ($verified['start_param'] ?? null));
        if ($loginToken !== null) {
            return response()->json([
                'redirect' => route('telegram.app.login', ['t' => $loginToken], false),
                'post' => null,
                'csrf_token' => $request->session()->token(),
            ]);
        }

        $return = TelegramStartParam::safeReturnPath($data['return'] ?? null);
        $area = '/'.($user->role === 'guardian' ? 'guardian' : 'student').'/';
        $target = $return !== null && str_starts_with($return, $area)
            ? ['url' => $return]
            : $start->resolve($user, ($data['to'] ?? null) ?: ($verified['start_param'] ?? null));

        return response()->json([
            'redirect' => $target['url'],
            'post' => $target['post'] ?? null,
            'csrf_token' => $request->session()->token(),
        ]);
    }

    /**
     * Silent re-login inside the Mini App: the frontend posts initData again when a
     * request came back 401/419 and retries that request once with the new CSRF token.
     */
    public function reauth(Request $request, TelegramInitData $verifier): JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => BotText::get('app.error')], 415);
        }
        $data = $request->validate(['init_data' => ['required', 'string', 'max:8192']]);

        $login = $this->login($request, $verifier, $data['init_data']);
        if ($login instanceof JsonResponse) {
            return $login;
        }

        return response()->json(['ok' => true, 'csrf_token' => $request->session()->token()]);
    }

    /**
     * TW: "Saytda ochish" — a one-time login link for the user's own browser. Accounts made
     * in Telegram have a random password, so without it the website would only show the
     * login form. Single use, 2 minutes; only from a Mini App session; students and parents.
     */
    public function siteLink(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(WebviewSession::active($request) && in_array($user->role, ['student', 'guardian'], true), 403);
        $path = TelegramStartParam::safeReturnPath((string) $request->input('path', '')) ?? '';

        $token = Str::random(48);
        Cache::put('telegram-site-login:'.$token, ['user' => $user->id, 'path' => $path], 120);

        return response()->json(['url' => route('telegram.site-login', $token)]);
    }

    public function siteLogin(Request $request, string $token): RedirectResponse
    {
        $data = preg_match('/^[A-Za-z0-9]{48}$/', $token) ? Cache::pull('telegram-site-login:'.$token) : null;
        $user = is_array($data) ? User::query()->find($data['user'] ?? null) : null;
        if ($user === null || $user->status === 'blocked' || ! in_array($user->role, ['student', 'guardian'], true)) {
            return redirect()->route('login')->with('error', BotText::get('app.site_link_expired'));
        }

        Auth::login($user);
        $request->session()->regenerate();
        Log::info('telegram: site login from the mini app', ['user' => $user->id]);

        $path = TelegramStartParam::safeReturnPath($data['path'] ?? null);
        $area = '/'.($user->role === 'guardian' ? 'guardian' : 'student').'/';

        return redirect($path !== null && str_starts_with($path, $area) ? $path : ($user->role === 'guardian' ? '/guardian/dashboard' : '/student/dashboard'));
    }

    public function link(Request $request, TelegramLinkService $links): RedirectResponse
    {
        $payload = 'link_'.$links->createToken($request->user());
        $url = BotText::botLink($payload);
        if ($url === null || trim((string) config('exam.telegram.bot_token')) === '') {
            return back()->with('error', BotText::get('link.not_configured'));
        }

        return redirect()->away($url);
    }

    /**
     * Verify initData and log the linked user in (a webview session).
     *
     * @return JsonResponse|array{0: User, 1: array<string, mixed>} an error response, or the user and the verified data
     */
    private function login(Request $request, TelegramInitData $verifier, string $initData): JsonResponse|array
    {
        try {
            $verified = $verifier->verify($initData);
        } catch (InvalidInitData $e) {
            Log::info('telegram: mini app initData rejected', ['reason' => $e->reason]);

            return response()->json(['message' => BotText::get('app.invalid'), 'reason' => $e->reason], 401);
        }

        $account = TelegramAccount::query()->with('user')->where('telegram_id', (int) $verified['user']['id'])->first();
        $user = $account?->user;
        if ($user === null) {
            // TW (WebView-first): an unregistered Telegram user registers inside the Mini App.
            // Whoever was logged in in this webview before (another Telegram account on the same
            // phone) is logged out first.
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            WebviewSession::mark($request);
            $to = (string) ($request->input('to') ?: ($verified['start_param'] ?? ''));

            // auth (agent L): a website login link opens its own screen (link by a shared phone, or register)
            $loginToken = \App\Services\Auth\TelegramLoginService::tokenFromParam($to);

            return response()->json([
                'message' => BotText::get('app.not_registered'),
                'register' => $loginToken !== null
                    ? route('telegram.app.login', ['t' => $loginToken], false)
                    : route('telegram.app.register', TelegramStartParam::isValid($to) ? ['to' => $to] : [], false),
            ], 409);
        }
        if ($user->status === 'blocked') {
            return response()->json(['message' => BotText::get('app.blocked')], 403);
        }
        // TW: staff accounts linked to Telegram are never logged in by initData alone
        // (the Mini App is the student / parent area); they use the website.
        if (! in_array($user->role, ['student', 'guardian'], true)) {
            return response()->json(['message' => BotText::get('app.staff'), 'site' => rtrim((string) config('app.url'), '/').'/login'], 403);
        }

        // the Telegram user is who the Mini App belongs to (also when another account was logged in)
        if ((string) Auth::id() !== (string) $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }
        WebviewSession::mark($request);
        $account->forceFill(['last_interaction_at' => now()])->save();

        return [$user, $verified];
    }
}
