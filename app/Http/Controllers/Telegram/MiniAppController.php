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
use Illuminate\Support\Facades\Log;
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
            return response()->json(['message' => BotText::get('app.not_registered')], 409);
        }
        if ($user->status === 'blocked') {
            return response()->json(['message' => BotText::get('app.blocked')], 403);
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
