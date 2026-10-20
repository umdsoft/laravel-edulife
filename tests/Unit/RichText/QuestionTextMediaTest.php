<?php

namespace Tests\Unit\RichText;

use App\Support\Exam\QuestionText;
use PHPUnit\Framework\TestCase;

/** Duplicate detection ignores images and graph blocks (docs/exam/03 §1.6). */
class QuestionTextMediaTest extends TestCase
{
    public function test_media_is_stripped_before_hashing(): void
    {
        $a = "Grafikdan foydalanib, \$f(2)\$ ni toping.\n```graph\n{\"x\":[-5,5],\"y\":[-3,9],\"functions\":[{\"expr\":\"x^2-2\"}]}\n```";
        $b = "Grafikdan  foydalanib, \$f(2)\$ ni toping.\n![chizma](/storage/questions/2026/10/abc.webp){width=50%}";
        $this->assertSame(QuestionText::hash('Grafikdan foydalanib, $f(2)$ ni toping.'), QuestionText::hash($a));
        $this->assertSame(QuestionText::hash($a), QuestionText::hash($b));
    }

    public function test_media_only_texts_stay_distinct(): void
    {
        $this->assertNotSame(
            QuestionText::hash('![a](/storage/questions/2026/10/one.webp)'),
            QuestionText::hash('![a](/storage/questions/2026/10/two.webp)'),
        );
    }
}
