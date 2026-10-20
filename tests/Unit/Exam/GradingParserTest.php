<?php

namespace Tests\Unit\Exam;

use App\Services\Exam\Grading\NumberParser;
use App\Services\Exam\Grading\UnitNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradingParserTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            ['12', 12.0], ['-3', -3.0], ['0,5', 0.5], ['0.5', 0.5], ['.5', 0.5], ['1/2', 0.5], ['-3/4', -0.75],
            ['12 500', 12500.0], ['2e-3', 0.002], ['2,5·10^-3', 0.0025], ['2.5*10^(-3)', 0.0025],
            ['2,5×10⁻³', 0.0025], ['3·10⁸', 3.0e8], ['10^6', 1.0e6], ['−4', -4.0], [' 7 ', 7.0],
        ];
    }

    #[DataProvider('numbers')]
    public function test_parses_student_numbers(string $input, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, NumberParser::parse($input), abs($expected) * 1e-12 + 1e-12);
    }

    public function test_rejects_garbage_and_ambiguity(): void
    {
        foreach (['', 'abc', '1,234.5', '1,2,3', '1/0', '2x', '--1', null] as $input) {
            $this->assertNull(NumberParser::parse($input), var_export($input, true));
        }
    }

    public function test_unit_spellings_normalise(): void
    {
        $this->assertTrue(UnitNormalizer::same('m/s^2', 'm/s²'));
        $this->assertTrue(UnitNormalizer::same(' m / s ** 2 ', 'm/s²'));
        $this->assertTrue(UnitNormalizer::same('N*m', 'N·m'));
        $this->assertTrue(UnitNormalizer::same('km / soat', 'km/soat'));
        $this->assertTrue(UnitNormalizer::same('µF', 'μF'));
        $this->assertTrue(UnitNormalizer::same('kOm', 'kΩ'));
        $this->assertFalse(UnitNormalizer::same('mA', 'MA'), 'case matters');
        $this->assertSame('', UnitNormalizer::normalize(null));
    }
}
