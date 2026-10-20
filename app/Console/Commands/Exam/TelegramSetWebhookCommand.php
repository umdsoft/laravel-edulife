<?php

namespace App\Console\Commands\Exam;

use App\Services\Telegram\BotText;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

/**
 * Stage 6: register the webhook (with the secret token) and the bot's menu
 * button that opens the Mini App. See docs/exam/INTEGRATIONS.md.
 */
class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {--delete : Remove the webhook instead} {--url= : Override the webhook URL}';

    protected $description = 'Register the Telegram bot webhook and Mini App menu button';

    public function handle(TelegramClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        if ($this->option('delete')) {
            $response = $client->deleteWebhook();
            $response->ok ? $this->info('Webhook removed.') : $this->error('Telegram: '.$response->description);

            return $response->ok ? self::SUCCESS : self::FAILURE;
        }

        $secret = (string) config('exam.telegram.webhook_secret');
        if (! preg_match('/^[A-Za-z0-9_\-]{16,256}$/', $secret)) {
            $this->error('TELEGRAM_WEBHOOK_SECRET must be 16-256 characters of A-Z a-z 0-9 _ -.');

            return self::FAILURE;
        }

        $url = (string) ($this->option('url') ?: route('telegram.webhook'));
        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram needs an HTTPS webhook URL, got {$url}. Set APP_URL or pass --url=.");

            return self::FAILURE;
        }

        $response = $client->setWebhook($url, $secret);
        if (! $response->ok) {
            $this->error('Telegram: '.$response->description);

            return self::FAILURE;
        }
        $this->info("Webhook set: {$url}");

        $appUrl = BotText::miniAppUrl();
        if (str_starts_with($appUrl, 'https://')) {
            $menu = $client->setMenuButton((string) __('exam_bot.menu_button', [], 'uz'), $appUrl);
            $menu->ok ? $this->info("Menu button opens {$appUrl}") : $this->warn('Menu button: '.$menu->description);
        } else {
            $this->warn("Mini App URL is not HTTPS ({$appUrl}); menu button not set.");
        }

        return self::SUCCESS;
    }
}
