<?php

namespace App\Console\Commands\Exam;

use App\Services\Telegram\BotText;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

/**
 * Telegram WebView (wave 2): the bot's "/" command list (setMyCommands) and the menu
 * button that opens the Mini App (setChatMenuButton, type web_app). telegram:set-webhook
 * runs this too; on its own it changes nothing about the webhook.
 * See docs/exam/INTEGRATIONS.md §2.7.
 */
class TelegramSetupMenuCommand extends Command
{
    protected $signature = 'telegram:setup-menu';

    protected $description = 'Set the Telegram bot commands and the Mini App menu button';

    public function handle(TelegramClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $commands = ['start' => 'Boshlash'] + (array) config('exam.telegram.commands', []);
        $response = $client->setMyCommands($commands);
        $response->ok
            ? $this->info('Commands: /'.implode(', /', array_keys($commands)))
            : $this->warn('Commands: '.$response->description);

        $appUrl = BotText::miniAppUrl();
        if (! str_starts_with($appUrl, 'https://')) {
            $this->warn("Mini App URL is not HTTPS ({$appUrl}); menu button not set.");

            return $response->ok ? self::SUCCESS : self::FAILURE;
        }

        $menu = $client->setMenuButton((string) __('exam_bot.menu_button', [], 'uz'), $appUrl);
        $menu->ok ? $this->info("Menu button opens {$appUrl}") : $this->warn('Menu button: '.$menu->description);

        return $response->ok && $menu->ok ? self::SUCCESS : self::FAILURE;
    }
}
