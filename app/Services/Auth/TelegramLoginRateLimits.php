<?php

namespace App\Services\Auth;

use App\Services\Telegram\BotText;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Named rate limiters of the website login through the bot (docs/auth/TELEGRAM_LOGIN.md §5).
 * Per phone and per session where it matters, with a per-IP ceiling high enough for a school
 * behind one carrier IP (CGNAT). Lockouts answer JSON 429 with an Uzbek message.
 */
final class TelegramLoginRateLimits
{
    public static function register(): void
    {
        RateLimiter::for('telegram-login-send', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by('tl-send-phone:'.self::phoneKey($request))->response(self::lockout(...)),
            Limit::perDay(10)->by('tl-send-phone-day:'.self::phoneKey($request))->response(self::lockout(...)),
            Limit::perMinute(30)->by('tl-send-ip:'.$request->ip())->response(self::lockout(...)),
        ]);
        RateLimiter::for('telegram-login-verify', fn (Request $request) => [
            Limit::perMinute(10)->by('tl-verify-s:'.self::sessionKey($request))->response(self::lockout(...)),
            Limit::perMinute(60)->by('tl-verify-ip:'.$request->ip())->response(self::lockout(...)),
        ]);
        RateLimiter::for('telegram-login-link', fn (Request $request) => [
            Limit::perMinute(10)->by('tl-link-s:'.self::sessionKey($request))->response(self::lockout(...)),
            Limit::perMinute(30)->by('tl-link-ip:'.$request->ip())->response(self::lockout(...)),
        ]);
        RateLimiter::for('telegram-login-status', fn (Request $request) => [
            Limit::perMinute(60)->by('tl-status-s:'.self::sessionKey($request))->response(self::lockout(...)),
            Limit::perMinute(1200)->by('tl-status-ip:'.$request->ip())->response(self::lockout(...)),
        ]);
    }

    private static function lockout(Request $request, array $headers)
    {
        $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));

        return response()->json(['message' => strip_tags(BotText::get('login.too_many', ['seconds' => $seconds]))], 429, $headers);
    }

    private static function phoneKey(Request $request): string
    {
        return TelegramLoginService::normalizePhone((string) $request->input('phone', '')) ?? 'invalid';
    }

    private static function sessionKey(Request $request): string
    {
        return $request->hasSession() ? sha1($request->session()->getId()) : 'ip'.$request->ip();
    }
}
