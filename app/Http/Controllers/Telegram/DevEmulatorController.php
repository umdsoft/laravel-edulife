<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramStartParam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Telegram WebView (wave 2): a local Mini App emulator, so the owner and Playwright can
 * try the exam and battle "inside Telegram" without Telegram.
 *
 * GET  /telegram/dev          emulator page: demo user, start parameter, theme, viewport;
 *                             the app runs in an iframe and the page plays Telegram's part
 *                             (header with BackButton, MainButton bar, events log)
 * POST /telegram/dev/sign     initData for the chosen user, signed server-side with the bot
 *                             token (or exam.telegram.dev_bot_token when no token is set)
 * GET  /telegram/dev/sdk.js   the mock window.Telegram.WebApp (same postMessage protocol as
 *                             Telegram Web, so nothing is loaded from telegram.org)
 *
 * POST /telegram/dev/contact  (TW) what Telegram.WebApp.requestContact() hands back: a contact
 *                             signed like initData, for the WebView registration
 *
 * TW (WebView-first): the demo list has students and parents; "fresh" opens the app as a new,
 * unregistered Telegram user (a fake id, no account) to try the WebView registration.
 * Staff accounts are not offered (the Mini App refuses them).
 *
 * Everything answers 404 unless APP_ENV=local. Nothing is sent to Telegram.
 * A demo user without a Telegram account gets one with a fake id (username "dev_emulator",
 * is_blocked = true so no bot message is ever addressed to it).
 */
class DevEmulatorController extends Controller
{
    public const FAKE_ID_BASE = 7_000_000_000;

    public function index(Request $request): View
    {
        $this->guard();

        $students = User::query()
            ->whereIn('role', ['student', 'guardian'])
            ->where('status', '!=', 'blocked')
            ->orderBy('role')->orderBy('first_name')
            ->limit(300)
            ->get(['id', 'first_name', 'last_name', 'phone', 'role']);

        return view('telegram.dev', [
            'students' => $students->map(fn (User $u) => [
                'id' => $u->id,
                'label' => ($u->role === 'guardian' ? '[ota-ona] ' : '').trim($u->first_name.' '.$u->last_name).($u->phone ? ' · '.$u->phone : ''),
            ])->values(),
            'initial' => [
                'user' => (string) $request->query('user', ''),
                'start' => TelegramStartParam::isValid((string) $request->query('start', '')) ? (string) $request->query('start') : 'exam',
                'theme' => $request->query('theme') === 'dark' ? 'dark' : 'light',
                'w' => max(300, min(1024, (int) $request->query('w', 390))),
                'h' => max(480, min(1400, (int) $request->query('h', 780))),
                'autostart' => $request->boolean('autostart'),
                // TW: the phone requestContact() shares (a random free +99890 number by default)
                'phone' => preg_match('/^\+?\d{9,15}$/', (string) $request->query('phone', '')) ? (string) $request->query('phone') : '+99890'.random_int(1000000, 9999999),
            ],
        ]);
    }

    public function sign(Request $request): JsonResponse
    {
        $this->guard();
        $data = $request->validate([
            'user_id' => ['required_without:fresh', 'nullable', 'exists:users,id'],
            'fresh' => ['nullable', 'boolean'],
            'telegram_id' => ['nullable', 'integer', 'min:'.(self::FAKE_ID_BASE + 1)],
            'first_name' => ['nullable', 'string', 'max:64'],
            'start_param' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]*$/'],
        ]);

        if ($request->boolean('fresh')) {
            // TW: a new Telegram user without an account (WebView registration); an earlier fresh id may be reused
            $id = (int) ($data['telegram_id'] ?? 0) ?: $this->freeFakeId();
            $account = TelegramAccount::query()->where('telegram_id', $id)->first();
            $user = $account?->user;
            $name = (string) ($data['first_name'] ?? 'Sinov');
            $account ??= new TelegramAccount(['telegram_id' => $id, 'username' => null]);
            $user ??= new User(['first_name' => $name, 'last_name' => 'Telegram']);
        } else {
            $user = User::query()->whereIn('role', ['student', 'guardian'])->findOrFail($data['user_id']);
            $account = TelegramAccount::query()->where('user_id', $user->id)->first() ?? $this->fakeAccount($user);
        }

        $fields = [
            'query_id' => 'DEV'.Str::random(20),
            'user' => json_encode(array_filter([
                'id' => (int) $account->telegram_id,
                'first_name' => (string) ($user->first_name ?: 'Demo'),
                'last_name' => (string) ($user->last_name ?? ''),
                'username' => $account->username,
                'language_code' => 'uz',
                'allows_write_to_pm' => true,
            ], fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'auth_date' => (string) time(),
            'signature' => 'dev-emulator',
        ];
        if (! empty($data['start_param'])) {
            $fields['start_param'] = $data['start_param'];
        }
        $fields['hash'] = TelegramInitData::sign($fields, TelegramInitData::token());

        return response()->json([
            'init_data' => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
            'telegram_id' => (int) $account->telegram_id,
            'app_url' => route('telegram.app', ['tgDevMock' => 1], false),
        ]);
    }

    /** TW: a requestContact() response for the emulated Telegram user (signed like initData). */
    public function contact(Request $request): JsonResponse
    {
        $this->guard();
        $data = $request->validate([
            'telegram_id' => ['required', 'integer', 'min:1'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $fields = [
            'contact' => json_encode([
                'user_id' => (int) $data['telegram_id'],
                'phone_number' => preg_replace('/[^0-9+]/', '', $data['phone']),
                'first_name' => 'Sinov',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'auth_date' => (string) time(),
        ];
        $fields['hash'] = TelegramInitData::sign($fields, TelegramInitData::token());

        return response()->json(['response' => http_build_query($fields, '', '&', PHP_QUERY_RFC3986)]);
    }

    private function freeFakeId(): int
    {
        do {
            $id = self::FAKE_ID_BASE + random_int(1, 999_999_999);
        } while (TelegramAccount::query()->where('telegram_id', $id)->exists());

        return $id;
    }

    public function sdk(): Response
    {
        $this->guard();

        return response((string) file_get_contents(resource_path('telegram/webapp-mock.js')), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function fakeAccount(User $user): TelegramAccount
    {
        do {
            $id = self::FAKE_ID_BASE + random_int(1, 999_999_999);
        } while (TelegramAccount::query()->where('telegram_id', $id)->exists());

        return TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_id' => $id,
            'chat_id' => $id,
            'username' => 'dev_emulator',
            'first_name' => mb_substr((string) ($user->first_name ?: 'Demo'), 0, 128),
            'language_code' => 'uz',
            'is_blocked' => true,
            'linked_at' => now(),
        ]);
    }

    private function guard(): void
    {
        abort_unless(app()->environment('local'), 404);
    }
}
