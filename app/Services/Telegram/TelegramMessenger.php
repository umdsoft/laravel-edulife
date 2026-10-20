<?php

namespace App\Services\Telegram;

use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Sends bot messages to linked Telegram accounts.
 *
 * notifyOnce() records every attempt in telegram_notifications; the unique
 * (account, kind, ref_id) key guarantees one message per reminder / result /
 * report even if the scheduler and an event listener both fire.
 * A 403 from Telegram marks the account as blocked (no more messages).
 */
class TelegramMessenger
{
    public function __construct(private readonly TelegramClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /** Plain reply (bot conversation). */
    public function send(TelegramAccount $account, string $text, ?array $markup = null): TelegramResponse
    {
        $chatId = $account->chat_id ?: $account->telegram_id;
        $response = $this->client->sendMessage($chatId, $text, $markup);
        if ($response->isBlocked() && ! $account->is_blocked) {
            $account->forceFill(['is_blocked' => true])->save();
            Log::info('telegram: account blocked the bot', ['account' => $account->id]);
        }

        return $response;
    }

    /** @return bool true when a new message was sent */
    public function notifyOnce(TelegramAccount $account, string $kind, string $refId, string $text, ?array $markup = null): bool
    {
        if (! $this->isConfigured()) {
            Log::info('telegram: bot token not set, notification skipped', ['kind' => $kind]);

            return false;
        }
        if ($account->is_blocked || $account->user_id === null) {
            return false;
        }

        try {
            $record = TelegramNotification::create([
                'telegram_account_id' => $account->id,
                'kind' => $kind,
                'ref_id' => $refId,
                'status' => 'pending',
            ]);
        } catch (UniqueConstraintViolationException) {
            return false; // already sent (or being sent)
        }

        $response = $this->send($account, $text, $markup);
        $record->forceFill([
            'status' => $response->ok ? 'sent' : ($response->isBlocked() ? 'blocked' : 'failed'),
            'error' => $response->ok ? null : mb_substr((string) ($response->errorCode ?? '').' '.($response->description ?? ''), 0, 255),
            'sent_at' => $response->ok ? now() : null,
        ])->save();

        // A transient failure may be retried by the next scheduler pass.
        if (! $response->ok && ! $response->isBlocked()) {
            $record->delete();
        }

        $delay = (int) config('exam.telegram.send_delay_ms', 0);
        if ($delay > 0 && ! app()->runningUnitTests()) {
            usleep($delay * 1000);
        }

        return $response->ok;
    }
}
