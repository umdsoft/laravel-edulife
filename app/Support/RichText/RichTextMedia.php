<?php

namespace App\Support\RichText;

/**
 * Images and graphs inside teacher text (question stem, option, solution,
 * lesson) — server mirror of resources/js/Utils/richText.js:
 *
 *   ![chizma](/storage/questions/2026/10/<uuid>.webp){width=60%}
 *   ```graph
 *   {"x":[-5,5],"y":[-3,9],"functions":[{"expr":"x^2-2"}]}
 *   ```
 *
 * Only our own uploads (/storage/questions/YYYY/MM/<name>.<ext>) are allowed;
 * graph blocks must pass GraphSpec. Used by the RichTextMedia validation rule,
 * duplicate detection (strip), Telegram texts (placeholders) and the prune
 * command (paths).
 */
final class RichTextMedia
{
    public const ALLOWED_URL = '#^/storage/questions/\d{4}/\d{2}/[A-Za-z0-9_-]{1,80}\.(?:webp|png|jpe?g|gif)\z#';

    public const IMAGE_RE = '/!\[([^\]\n]{0,300})\]\(\s*<?([^)\s>]{1,500})>?(?:\s+"[^"\n]*")?\s*\)(?:\{\s*width\s*=\s*(\d{1,4})\s*(%|px)?\s*\})?/u';

    public const GRAPH_RE = '/(^|\n)[ \t]*```graph[ \t]*\r?\n([\s\S]*?)\r?\n?[ \t]*```[ \t]*(?=\r?\n|$)/u';

    /** Per-field limits: a question field / a lesson text. */
    public const LIMITS = [
        'question' => ['images' => 6, 'graphs' => 3],
        'lesson' => ['images' => 40, 'graphs' => 20],
    ];

    public static function isAllowedUrl(string $url): bool
    {
        return (bool) preg_match(self::ALLOWED_URL, $url);
    }

    /** @return list<array{alt:string, url:string, allowed:bool}> */
    public static function images(?string $text): array
    {
        if ($text === null || ! str_contains($text, '![')) {
            return [];
        }
        preg_match_all(self::IMAGE_RE, $text, $m, PREG_SET_ORDER);

        return array_map(fn ($x) => ['alt' => trim($x[1]), 'url' => $x[2], 'allowed' => self::isAllowedUrl($x[2])], $m);
    }

    /** @return list<string> JSON sources of the ```graph blocks */
    public static function graphs(?string $text): array
    {
        if ($text === null || ! str_contains($text, '```graph')) {
            return [];
        }
        preg_match_all(self::GRAPH_RE, $text, $m);

        return $m[2];
    }

    public static function hasMedia(?string $text): bool
    {
        return collect(self::images($text))->contains('allowed', true) || self::graphs($text) !== [];
    }

    /**
     * Problems of one text field (empty = fine), Uzbek.
     *
     * @param  string  $profile  'question' | 'lesson' (see LIMITS)
     */
    public static function errors(?string $text, string $profile = 'question'): array
    {
        if ($text === null || $text === '') {
            return [];
        }
        $limits = self::LIMITS[$profile] ?? self::LIMITS['question'];
        $errors = [];

        $images = self::images($text);
        foreach ($images as $img) {
            if (! $img['allowed']) {
                $errors[] = 'Rasm faqat platformaga yuklangan bo\'lishi kerak ("Rasm" tugmasi): '.mb_strimwidth($img['url'], 0, 60, '…');
            }
        }
        if (count($images) > $limits['images']) {
            $errors[] = "Bitta maydonda ko'pi bilan {$limits['images']} ta rasm bo'lishi mumkin.";
        }

        $graphs = self::graphs($text);
        if (count($graphs) > $limits['graphs']) {
            $errors[] = "Bitta maydonda ko'pi bilan {$limits['graphs']} ta grafik bo'lishi mumkin.";
        }
        foreach ($graphs as $i => $json) {
            foreach (array_slice(GraphSpec::validateJson($json), 0, 3) as $e) {
                $errors[] = (count($graphs) > 1 ? 'Grafik '.($i + 1).': ' : 'Grafik: ').$e;
            }
        }
        // an opening fence that the block pattern did not match (not closed)
        if (substr_count($text, '```graph') > count($graphs)) {
            $errors[] = 'Grafik bloki yopilmagan: oxirida ``` qatori kerak.';
        }

        return $errors;
    }

    /** Text without images and graph blocks (duplicate detection). */
    public static function strip(?string $text): string
    {
        $text = (string) $text;
        if (! str_contains($text, '![') && ! str_contains($text, '```graph')) {
            return $text;
        }
        $text = preg_replace(self::GRAPH_RE, '$1', $text) ?? $text;

        return trim(preg_replace(self::IMAGE_RE, ' ', $text) ?? $text);
    }

    /** Text with images → "[rasm]", graphs → "[grafik]" (Telegram, plain-text previews). */
    public static function placeholders(?string $text): string
    {
        $text = (string) $text;
        if (! str_contains($text, '![') && ! str_contains($text, '```graph')) {
            return $text;
        }
        $text = preg_replace(self::GRAPH_RE, "\$1[grafik]", $text) ?? $text;

        return preg_replace(self::IMAGE_RE, '[rasm]', $text) ?? $text;
    }

    /** Public-disk paths (questions/YYYY/MM/x.webp) of our images referenced in the text. */
    public static function storagePaths(?string $text): array
    {
        return collect(self::images($text))->where('allowed', true)
            ->map(fn ($i) => substr($i['url'], strlen('/storage/')))
            ->unique()->values()->all();
    }

    /** Markdown for an uploaded image. */
    public static function imageMarkdown(string $url, string $alt = 'chizma'): string
    {
        $alt = trim(str_replace(['[', ']', "\n", "\r"], ' ', $alt)) ?: 'chizma';

        return "![{$alt}]({$url})";
    }
}
