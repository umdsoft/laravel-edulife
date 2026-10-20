<?php

namespace Tests\Unit\RichText;

use App\Support\RichText\GraphExpression;
use App\Support\RichText\GraphSpec;
use App\Support\RichText\RichTextMedia;
use PHPUnit\Framework\TestCase;

/** GraphSpec / GraphExpression / RichTextMedia — the PHP twins of Utils/graph and Utils/richText.js. */
class GraphSpecTest extends TestCase
{
    private const IMG = '/storage/questions/2026/10/9b2f3c4e-1a2b-4c5d-8e9f-0a1b2c3d4e5f.webp';

    public function test_valid_specs_pass(): void
    {
        $this->assertSame([], GraphSpec::validateJson('{"x":[-5,5],"y":[-3,9],"grid":true,"functions":[{"expr":"x^2-2","label":"f(x)"}],"points":[{"x":2,"y":2,"label":"A"}],"axes":{"x":"t, s","y":"v, m/s"}}'));
        $this->assertSame([], GraphSpec::validate([
            'x' => [0, 10], 'y' => [-2, 8], 'equal' => true, 'ticks' => [1, 2], 'width' => 70,
            'polylines' => [['points' => [[0, 0], [2, 4], [6, 4], [8, 0]], 'label' => 'v(t)', 'color' => 'red']],
            'segments' => [['from' => [0, 0], 'to' => [3, 0], 'dashed' => true]],
            'vectors' => [['from' => [0, 0], 'to' => [2, 3], 'label' => 'F']],
            'polygons' => [['points' => [[0, 0], [4, 0], [0, 3]], 'fill' => true]],
            'circles' => [['center' => [1, 1], 'r' => 2]],
            'labels' => [['x' => 1, 'y' => 1, 'text' => 'O']],
            'angles' => [['vertex' => [0, 0], 'from' => [4, 0], 'to' => [0, 3], 'right' => true]],
        ]));
    }

    public function test_invalid_specs_are_reported_in_uzbek(): void
    {
        $this->assertStringContainsString('JSON', GraphSpec::validateJson('{x:[1,2]}')[0]);
        $this->assertStringContainsString('"x" oralig\'i', implode(' ', GraphSpec::validateJson('{"x":[5,-5],"y":[0,1]}')));
        $this->assertStringContainsString('Noma\'lum kalit: "onload"', implode(' ', GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"onload":"x"}')));
        $this->assertStringContainsString('Funksiya 1', implode(' ', GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"functions":[{"expr":"system(1)"}]}')));
        $this->assertStringContainsString('rang', implode(' ', GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"points":[{"x":0,"y":0,"color":"#f00"}]}')));
        $this->assertStringContainsString('juda kichik', implode(' ', GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"ticks":0.001}')));
        $this->assertStringContainsString('40 belgi', implode(' ', GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"labels":[{"x":0,"y":0,"text":"'.str_repeat('a', 41).'"}]}')));
        $this->assertNotEmpty(GraphSpec::validateJson('[1,2]'));
        $this->assertNotEmpty(GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"functions":{"expr":"x"}}'));
        $this->assertNotEmpty(GraphSpec::validateJson('{"x":[0,"1"],"y":[0,1]}'));
        $this->assertNotEmpty(GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"grid":"yes"}'));
        $this->assertNotEmpty(GraphSpec::validateJson('{"x":[0,1],"y":[0,1],"polylines":[{"points":[[0,0]]}]}'));
    }

    public function test_expression_grammar_matches_the_js_parser(): void
    {
        $at = fn (string $e, float $x) => GraphExpression::evaluate(GraphExpression::parse($e), $x);
        $this->assertEqualsWithDelta(7, $at('x^2-2', 3), 1e-9);
        $this->assertEqualsWithDelta(-9, $at('-x^2', 3), 1e-9);
        $this->assertEqualsWithDelta(512, $at('2^3^2', 0), 1e-9);
        $this->assertEqualsWithDelta(8, $at('(x-1)(x+1)', 3), 1e-9);
        $this->assertEqualsWithDelta(2 * M_PI, $at('2pix', 1), 1e-9);
        $this->assertEqualsWithDelta(6, $at('2|x|', -3), 1e-9);
        $this->assertEqualsWithDelta(10, $at('5t', 2), 1e-9);
        $this->assertEqualsWithDelta(-2, $at('x^(1/3)', -8), 1e-9);
        $this->assertEqualsWithDelta(2, $at('max(x, 2)', 1), 1e-9);
        $this->assertNan($at('sqrt(x)', -1));

        foreach (['alert(1)', 'constructor', 'x;1', '__proto__', 'x=1', 'sin x', '(x', 'x)', '', 'max(1)', 'sqrt(1,2)', '$x', 'exec("ls")'] as $bad) {
            $this->assertNotNull(GraphExpression::error($bad), $bad);
        }
        foreach (['x^2-2', '-0.5x^2+3', 'tan(x)', '1/x', '10 - 9.8t', 'x·2', '−x', '3e2*x'] as $ok) {
            $this->assertNull(GraphExpression::error($ok), $ok);
        }
    }

    public function test_every_example_in_the_teacher_guide_is_valid(): void
    {
        $guide = (string) file_get_contents(dirname(__DIR__, 3).'/docs/exam/RASM_VA_GRAFIK.md');
        $graphs = RichTextMedia::graphs($guide);
        $this->assertGreaterThanOrEqual(6, count($graphs));
        foreach ($graphs as $json) {
            $this->assertSame([], GraphSpec::validateJson($json), $json);
        }
    }

    public function test_rich_text_media_rules(): void
    {
        $graph = "```graph\n{\"x\":[-5,5],\"y\":[-3,9],\"functions\":[{\"expr\":\"x^2-2\"}]}\n```";
        $text = "Chizmaga qarang:\n![ABC uchburchak](".self::IMG."){width=60%}\n{$graph}\nJavob?";

        $this->assertSame([], RichTextMedia::errors($text));
        $this->assertTrue(RichTextMedia::hasMedia($text));
        $this->assertSame(['questions/2026/10/9b2f3c4e-1a2b-4c5d-8e9f-0a1b2c3d4e5f.webp'], RichTextMedia::storagePaths($text));
        $this->assertSame("Chizmaga qarang:\n[rasm]\n[grafik]\nJavob?", RichTextMedia::placeholders($text));
        $this->assertSame("Chizmaga qarang:\n \n\nJavob?", RichTextMedia::strip($text));

        $this->assertStringContainsString('platformaga yuklangan', implode(' ', RichTextMedia::errors('![x](https://evil.example/a.png)')));
        $this->assertNotEmpty(RichTextMedia::errors('![x](/storage/questions/2026/10/a.svg)'));
        $this->assertNotEmpty(RichTextMedia::errors('![x](/storage/questions/2026/10/a.webp?v=1)'));
        $this->assertFalse(RichTextMedia::isAllowedUrl("/storage/questions/2026/10/a.webp\n"));
        $this->assertStringContainsString('yopilishi kerak',implode(' ', RichTextMedia::errors("```graph\n{\"x\":[0,1],\"y\":[0,1]}\n")));
        $this->assertStringContainsString('Grafik:', implode(' ', RichTextMedia::errors("```graph\n{\"x\":[0,1]}\n```")));
        $this->assertStringContainsString('6 ta rasm', implode(' ', RichTextMedia::errors(str_repeat('![a]('.self::IMG.') ', 7))));
        $this->assertSame([], RichTextMedia::errors(str_repeat('![a]('.self::IMG.') ', 7), 'lesson'));
        $this->assertStringContainsString('3 ta grafik', implode(' ', RichTextMedia::errors(implode("\n", array_fill(0, 4, $graph)))));
        $this->assertSame([], RichTextMedia::errors('Oddiy matn $x^2$, ```kod``` ham bor'));
        $this->assertSame('![chizma](/storage/x.webp)', RichTextMedia::imageMarkdown('/storage/x.webp', '[]'));
    }
}
