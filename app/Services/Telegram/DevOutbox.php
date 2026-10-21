<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;

/**
 * Local "bot chat" (agent L, docs/auth/TELEGRAM_LOGIN.md §7): with APP_ENV=local and no
 * TELEGRAM_BOT_TOKEN, the messages the bot would send are kept here (cache, last 100) instead
 * of being dropped, and the /telegram/dev emulator shows them with working buttons.
 * Never active outside local; nothing leaves the machine.
 */
final class DevOutbox
{
    private const KEY = 'telegram-dev-outbox';
    private const LIMIT = 100;

    public static function enabled(): bool
    {
        return app()->environment('local') && (bool) config('exam.telegram.dev_outbox', true);
    }

    public static function record(string $method, array $params): TelegramResponse
    {
        if ($method !== 'sendMessage') {
            return TelegramResponse::skipped();
        }
        $messages = self::all();
        $id = ((int) (end($messages)['id'] ?? 0)) + 1;
        $messages[] = [
            'id' => $id,
            'at' => now()->format('H:i:s'),
            'chat_id' => (int) ($params['chat_id'] ?? 0),
            'text' => (string) ($params['text'] ?? ''),
            'markup' => $params['reply_markup'] ?? null,
        ];
        Cache::put(self::KEY, array_slice($messages, -self::LIMIT), now()->addDay());

        return new TelegramResponse(true, 200, null, null, ['message_id' => $id]);
    }

    /** @return list<array{id: int, at: string, chat_id: int, text: string, markup: ?array}> */
    public static function all(): array
    {
        $messages = Cache::get(self::KEY, []);

        return is_array($messages) ? array_values($messages) : [];
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }
}
