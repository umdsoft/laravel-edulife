<?php

namespace App\Services\Telegram;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Named rate limiters of the Telegram endpoints (TW audit).
 *
 * A plain "throttle:20,1" counts every guest request of an IP together, across all routes.
 * Mobile carriers put thousands of phones behind a few IPs (CGNAT), so at 10:00 on Saturday a
 * whole class opening the Mini App would get 429 on the login. The Mini App endpoints are
 * therefore limited per Telegram user (the id in initData — only a bucket key, the HMAC is
 * checked by the controller) with a generous per-IP ceiling; the webhook (Telegram's own few
 * IPs, already authenticated by the secret token) only gets a high ceiling.
 */
final class TelegramRateLimits
{
    public static function register(): void
    {
        // /telegram/app/auth, /reauth, /contact
        RateLimiter::for('telegram-app', fn (Request $request) => [
            Limit::perMinute(30)->by('tg-app:'.self::telegramKey($request)),
            Limit::perMinute(600)->by('tg-app-ip:'.$request->ip()),
        ]);

        // /telegram/app/register
        RateLimiter::for('telegram-register', fn (Request $request) => [
            Limit::perMinute(10)->by('tg-reg:'.self::telegramKey($request)),
            Limit::perMinute(300)->by('tg-reg-ip:'.$request->ip()),
        ]);

        // Mini App battle routes and the site handoff: a counter per route and user. With
        // "throttle:N,1" all of a user's throttled routes share ONE counter, so the 1.5 s state
        // poll of a match (40/min) used up "Do'stni chaqirish" (10/min) for the next minute.
        foreach (['battle-state' => 120, 'battle-challenge' => 10, 'battle-accept' => 20, 'telegram-site-link' => 10, 'telegram-site-login' => 20] as $name => $max) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($max)->by($name.':'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        }

        // /telegram/webhook: updates of every user come from Telegram's few IPs
        RateLimiter::for('telegram-webhook', fn (Request $request) => Limit::perMinute(6000)->by('tg-hook:'.$request->ip()));
    }

    /** The Telegram user id from the (not yet verified) initData, else the IP. */
    public static function telegramKey(Request $request): string
    {
        $initData = (string) $request->input('init_data', '');
        if ($initData !== '' && strlen($initData) <= 8192) {
            parse_str($initData, $fields);
            $user = json_decode((string) ($fields['user'] ?? ''), true);
            if (is_array($user) && isset($user['id']) && is_numeric($user['id'])) {
                return 'u'.(int) $user['id'];
            }
        }

        return 'ip'.$request->ip();
    }
}
