<?php

namespace App\Services\Telegram;

/**
 * Server-side check of Telegram Mini App initData
 * (https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app):
 *
 *   data_check_string = every field except "hash", sorted by key, "key=value" joined by "\n"
 *   secret_key        = HMAC_SHA256(key: "WebAppData", message: bot_token)
 *   hash              = hex(HMAC_SHA256(key: secret_key, message: data_check_string))
 *
 * and auth_date must be younger than exam.telegram.init_data_ttl seconds.
 */
class TelegramInitData
{
    /**
     * @return array{user: array<string, mixed>, auth_date: int, start_param: ?string, fields: array<string, string>}
     *
     * @throws InvalidInitData
     */
    public function verify(string $initData, ?int $now = null): array
    {
        $token = self::token();
        if (trim($token) === '') {
            throw new InvalidInitData('not_configured');
        }
        if ($initData === '' || strlen($initData) > 8192) {
            throw new InvalidInitData('malformed');
        }

        $fields = [];
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $fields[urldecode($key)] = urldecode($value);
        }

        $hash = $fields['hash'] ?? null;
        if (! is_string($hash) || ! preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw new InvalidInitData('missing_hash');
        }
        unset($fields['hash']);

        if (! hash_equals(self::sign($fields, $token), $hash)) {
            throw new InvalidInitData('bad_hash');
        }

        $authDate = (int) ($fields['auth_date'] ?? 0);
        $ttl = (int) config('exam.telegram.init_data_ttl', 86400);
        $now ??= time();
        if ($authDate <= 0 || $now - $authDate > $ttl || $authDate - $now > 300) {
            throw new InvalidInitData('expired');
        }

        $user = json_decode($fields['user'] ?? '', true);
        if (! is_array($user) || ! isset($user['id']) || ! is_numeric($user['id'])) {
            throw new InvalidInitData('no_user');
        }

        return [
            'user' => $user,
            'auth_date' => $authDate,
            'start_param' => $fields['start_param'] ?? null,
            'fields' => $fields,
        ];
    }

    /**
     * The token initData is signed with: the bot token; on APP_ENV=local without a bot
     * token, the /telegram/dev emulator's token (Telegram WebView, wave 2).
     */
    public static function token(): string
    {
        $token = (string) config('exam.telegram.bot_token');
        if (trim($token) === '' && app()->environment('local')) {
            return (string) config('exam.telegram.dev_bot_token', '');
        }

        return $token;
    }

    /** Hash of the fields (without "hash") — also used by tests to build valid initData. */
    public static function sign(array $fields, string $botToken): string
    {
        ksort($fields, SORT_STRING);
        $check = implode("\n", array_map(fn ($k, $v) => $k.'='.$v, array_keys($fields), $fields));
        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);

        return hash_hmac('sha256', $check, $secret);
    }
}
