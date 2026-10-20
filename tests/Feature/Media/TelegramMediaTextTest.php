<?php

namespace Tests\Feature\Media;

use App\Services\Telegram\BotText;
use Tests\TestCase;

/** Telegram messages cannot show pictures: images / graphs become "[rasm]" / "[grafik]". */
class TelegramMediaTextTest extends TestCase
{
    public function test_question_text_replaces_media_and_escapes(): void
    {
        $text = "Grafikka qarang <b>:</b>\n```graph\n{\"x\":[0,1],\"y\":[0,1]}\n```\n![chizma](/storage/questions/2026/10/abc.webp){width=50%}  va javob?";

        $this->assertSame("Grafikka qarang &lt;b&gt;:&lt;/b&gt;\n[grafik]\n[rasm]  va javob?", BotText::questionText($text));
        $this->assertSame('ab…', BotText::questionText('abcdef', 3));
    }

    public function test_replacement_values_of_bot_texts_get_placeholders(): void
    {
        app('translator')->addLines(['exam_bot.test_media' => 'Savol: :q'], 'uz');

        $this->assertSame('Savol: [rasm] toping', BotText::get('test_media', ['q' => '![a](/storage/questions/2026/10/a.webp) toping']));
    }
}
