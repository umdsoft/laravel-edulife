<?php

namespace App\Services\Telegram;

use App\Support\RichText\RichTextMedia;
use Illuminate\Support\Facades\Route;

/**
 * Bot texts from lang/uz/exam_bot.php (HTML parse mode). Replacement values
 * are HTML-escaped; the texts themselves may contain Telegram HTML.
 * Images and ```graph blocks in a replacement value (a question text) become
 * "[rasm]" / "[grafik]" — Telegram messages cannot show them.
 */
final class BotText
{
    public static function get(string $key, array $replace = []): string
    {
        $escaped = array_map(fn ($v) => htmlspecialchars(RichTextMedia::placeholders((string) $v), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $replace);

        return (string) __('exam_bot.'.$key, $escaped, 'uz');
    }

    /**
     * A question / solution text for a plain Telegram message: media → "[rasm]" / "[grafik]",
     * whitespace collapsed, cut to $limit characters, HTML-escaped.
     */
    public static function questionText(?string $text, int $limit = 300): string
    {
        $plain = trim((string) preg_replace('/[ \t]*\n[ \t]*/u', "\n", RichTextMedia::placeholders((string) $text)));
        $plain = (string) preg_replace('/\n{3,}/u', "\n\n", $plain);

        return htmlspecialchars(mb_strimwidth($plain, 0, $limit, '…', 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Raw (unescaped) text for button labels. */
    public static function button(string $key): string
    {
        return (string) __('exam_bot.btn.'.$key, [], 'uz');
    }

    /** HTTPS URL of the Mini App (optionally opening a section). */
    public static function miniAppUrl(?string $to = null): string
    {
        $base = (string) (config('exam.telegram.mini_app_url') ?: (Route::has('telegram.app') ? route('telegram.app') : url('/telegram/app')));

        return $to ? $base.(str_contains($base, '?') ? '&' : '?').'to='.urlencode($to) : $base;
    }

    /** Inline button that opens the Mini App (web_app needs HTTPS; plain link otherwise). */
    public static function appButton(string $labelKey, ?string $to = null): array
    {
        $url = self::miniAppUrl($to);

        return str_starts_with($url, 'https://')
            ? ['text' => self::button($labelKey), 'web_app' => ['url' => $url]]
            : ['text' => self::button($labelKey), 'url' => $url];
    }

    /** @param list<list<array>> $rows */
    public static function inline(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }

    public static function botLink(string $payload): ?string
    {
        $username = ltrim((string) config('exam.telegram.bot_username'), '@');

        return $username === '' ? null : 'https://t.me/'.$username.'?start='.urlencode($payload);
    }
}
