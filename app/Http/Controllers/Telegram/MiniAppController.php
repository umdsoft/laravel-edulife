<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\TelegramAccount;
use App\Services\Telegram\BotText;
use App\Services\Telegram\InvalidInitData;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Telegram Mini App entry (stage 6).
 *
 * GET  /telegram/app       — a tiny page that posts Telegram.WebApp.initData
 * POST /telegram/app/auth  — verifies the initData HMAC with the bot token,
 *                            logs the linked user in and returns where to go
 *                            (student.exam.index once stage 5 has it).
 * GET  /telegram/link      — (logged in) deep link that connects the current
 *                            web account to the user's Telegram.
 */
class MiniAppController extends Controller
{
    public function show(Request $request): View
    {
        return view('telegram.mini-app', [
            'to' => (string) $request->query('to', ''),
            'texts' => __('exam_bot.app', [], 'uz'),
        ]);
    }

    public function auth(Request $request, TelegramInitData $verifier): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string', 'max:8192'],
            'to' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $verified = $verifier->verify($data['init_data']);
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

        Auth::login($user);
        $request->session()->regenerate();
        $account->forceFill(['last_interaction_at' => now()])->save();

        return response()->json(['redirect' => $this->target($user->role, $data['to'] ?? $verified['start_param'] ?? null)]);
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

    /** Where the Mini App opens: the exam area of stage 5, or the dashboards. */
    private function target(string $role, ?string $to): string
    {
        if ($role === 'guardian') {
            return Route::has('guardian.dashboard') ? route('guardian.dashboard', [], false) : '/';
        }
        if ($role !== 'student') {
            return '/';
        }

        if (is_string($to) && preg_match('/^result:([0-9a-f\-]{36})$/i', $to, $m) && Route::has('student.exam.result')) {
            return route('student.exam.result', ['attempt' => $m[1]], false);
        }

        $named = match ($to) {
            'progress' => 'student.exam.progress',
            'leaderboard' => 'student.exam.leaderboard',
            default => 'student.exam.index',
        };
        foreach ([$named, 'student.exam.index'] as $name) {
            if (Route::has($name)) {
                return route($name, [], false);
            }
        }

        return Route::has('student.dashboard') ? route('student.dashboard', [], false) : '/student/dashboard';
    }
}
