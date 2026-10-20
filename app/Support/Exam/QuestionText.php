<?php

namespace App\Support\Exam;

use App\Support\RichText\RichTextMedia;

/**
 * Normalization of question stems for duplicate detection
 * (question_bank.normalized_hash). Two stems that differ only in case,
 * whitespace, HTML tags, decimal comma or trailing punctuation hash equal.
 */
final class QuestionText
{
    public static function normalize(string $text): string
    {
        // images and ```graph blocks are not part of the comparison (docs/exam/03 §1.6); a text
        // that is only media keeps it, so two different pictures never look like one question
        $stripped = RichTextMedia::strip($text);
        if (trim($stripped) !== '') {
            $text = $stripped;
        }
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text, 'UTF-8');
        // Apostrophe variants used in Uzbek Latin: o' g' o` o‘ o’ -> o'
        $text = str_replace(["\u{2018}", "\u{2019}", "\u{02BB}", "\u{02BC}", '`'], "'", $text);
        // Decimal comma between digits -> point (0,5 == 0.5)
        $text = preg_replace('/(?<=\d),(?=\d)/u', '.', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        // Spaces around operators and brackets carry no meaning
        $text = preg_replace('/\s*([=+\-*\/^(){}\[\],;:<>])\s*/u', '$1', $text);
        $text = rtrim(trim($text), " .?!");

        return $text;
    }

    public static function hash(string $text): string
    {
        return sha1(self::normalize($text));
    }
}
