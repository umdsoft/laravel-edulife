<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Route;

/**
 * Links of a friend's battle challenge (Telegram WebView, wave 2):
 *   telegram  https://t.me/<bot>?startapp=battle_<code>  (opens the bot's Main Mini App;
 *             BotFather -> Bot Settings -> Configure Mini App must be set, see INTEGRATIONS.md)
 *   web       https://<site>/student/battles/challenge/<code>
 *   share     https://t.me/share/url?url=...&text=...  (Telegram's "send to a chat" sheet)
 */
final class BattleInviteLinks
{
    /** @return array{code: string, web: ?string, telegram: ?string, share: string, text: string} */
    public static function for(string $code): array
    {
        $web = Route::has('student.battles.challenge.show') ? route('student.battles.challenge.show', $code) : null;
        $username = ltrim((string) config('exam.telegram.bot_username'), '@');
        $telegram = $username !== '' ? 'https://t.me/'.$username.'?startapp=battle_'.$code : null;
        $text = (string) __('exam_bot.battle.invite_share', [], 'uz');
        $target = $telegram ?? $web ?? '';

        return [
            'code' => $code,
            'web' => $web,
            'telegram' => $telegram,
            'share' => 'https://t.me/share/url?url='.rawurlencode($target).'&text='.rawurlencode($text),
            'text' => $text,
        ];
    }
}
