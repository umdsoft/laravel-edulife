<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin Telegram Bot API client over Laravel's Http client.
 *
 * Chosen over nutgram/nutgram or irazasyed/telegram-bot-sdk: the bot needs
 * five methods (sendMessage, answerCallbackQuery, setWebhook, deleteWebhook,
 * setChatMenuButton) and its own conversation state in the database; a
 * framework would add a dependency, its own routing/cache-backed state and
 * a second way to mock HTTP. Http::fake() covers every call in tests.
 *
 * While exam.telegram.bot_token is empty nothing is sent (log line, no PII).
 * The token is part of the URL, so URLs and exception messages are never logged.
 */
class TelegramClient
{
    public function isConfigured(): bool
    {
        return trim((string) config('exam.telegram.bot_token')) !== '';
    }

    public function call(string $method, array $params = []): TelegramResponse
    {
        if (! $this->isConfigured()) {
            Log::info('telegram: bot token not set, call skipped', ['method' => $method]);

            return TelegramResponse::skipped();
        }

        $url = rtrim((string) config('exam.telegram.api_base', 'https://api.telegram.org'), '/')
            .'/bot'.config('exam.telegram.bot_token').'/'.$method;

        try {
            $response = Http::asJson()->acceptJson()
                ->timeout((int) config('exam.telegram.timeout_seconds', 10))
                ->post($url, $params);
        } catch (ConnectionException) {
            Log::warning('telegram: connection failed', ['method' => $method]);

            return new TelegramResponse(false, 0, null, 'connection failed');
        }

        $json = (array) ($response->json() ?? []);
        $ok = $response->successful() && ($json['ok'] ?? false) === true;
        if (! $ok) {
            Log::warning('telegram: API error', [
                'method' => $method,
                'status' => $response->status(),
                'error_code' => $json['error_code'] ?? null,
            ]);
        }

        return new TelegramResponse(
            $ok,
            $response->status(),
            isset($json['error_code']) ? (int) $json['error_code'] : ($ok ? null : $response->status()),
            isset($json['description']) ? mb_substr((string) $json['description'], 0, 200) : null,
            $json['result'] ?? null,
        );
    }

    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): TelegramResponse
    {
        $params = [
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 4096),
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = $replyMarkup;
        }

        return $this->call('sendMessage', $params);
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): TelegramResponse
    {
        return $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ], fn ($v) => $v !== null));
    }

    public function setWebhook(string $url, ?string $secret): TelegramResponse
    {
        return $this->call('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secret ?: null,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => false,
        ], fn ($v) => $v !== null));
    }

    public function deleteWebhook(): TelegramResponse
    {
        return $this->call('deleteWebhook');
    }

    /** The bot's menu button opens the Mini App. */
    public function setMenuButton(string $text, string $webAppUrl): TelegramResponse
    {
        return $this->call('setChatMenuButton', [
            'menu_button' => ['type' => 'web_app', 'text' => $text, 'web_app' => ['url' => $webAppUrl]],
        ]);
    }
}
