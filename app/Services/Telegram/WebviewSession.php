<?php

namespace App\Services\Telegram;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * The "this session runs inside the Telegram Mini App" flag (Telegram WebView, wave 2).
 *
 * Set when initData was verified (POST /telegram/app/auth or /reauth); read by
 * HandleInertiaRequests (prop "webview" -> StudentLayout's compact chrome), app.blade.php
 * (loads the Telegram SDK) and ApplyWebviewCookies (SameSite=None cookies on HTTPS).
 * A normal website session never has it, so the website is unchanged.
 */
final class WebviewSession
{
    public const KEY = 'webview';
    public const TELEGRAM = 'telegram';
    private const DEV_MOCK_KEY = 'telegram_dev_mock';

    public static function mark(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::KEY, self::TELEGRAM);
        }
    }

    public static function active(Request $request): bool
    {
        return $request->hasSession() && $request->session()->get(self::KEY) === self::TELEGRAM;
    }

    /** /telegram/app?tgDevMock=1 from the local emulator: use the mock SDK in this session. */
    public static function rememberDevMock(Request $request): void
    {
        if ($request->hasSession() && app()->environment('local') && $request->query('tgDevMock') === '1') {
            $request->session()->put(self::DEV_MOCK_KEY, true);
        }
    }

    public static function usesDevMock(Request $request): bool
    {
        return app()->environment('local') && $request->hasSession() && $request->session()->get(self::DEV_MOCK_KEY) === true;
    }

    /** The Telegram WebApp SDK, or the local emulator's mock of it. */
    public static function sdkUrl(Request $request): string
    {
        if (self::usesDevMock($request) && Route::has('telegram.dev.sdk')) {
            return route('telegram.dev.sdk', [], false);
        }

        return (string) config('exam.telegram.sdk_url', 'https://telegram.org/js/telegram-web-app.js');
    }
}
