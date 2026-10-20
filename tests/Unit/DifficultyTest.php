<?php

namespace Tests\Unit;

use App\Support\Difficulty;
use PHPUnit\Framework\TestCase;

class DifficultyTest extends TestCase
{
    public function test_levels_map_to_bands(): void
    {
        $this->assertSame(['easy', 'easy', 'medium', 'hard', 'hard'], array_map([Difficulty::class, 'bandOf'], [1, 2, 3, 4, 5]));
        $this->assertNull(Difficulty::bandOf(null));
        $this->assertNull(Difficulty::bandOf(0));
        $this->assertNull(Difficulty::bandOf(6));
    }

    public function test_bands_map_back_to_levels_and_round_trip(): void
    {
        $this->assertSame([1, 2], Difficulty::levelsOf('easy'));
        $this->assertSame([3], Difficulty::levelsOf('medium'));
        $this->assertSame([4, 5], Difficulty::levelsOf('hard'));
        $this->assertSame([], Difficulty::levelsOf('mixed'));
        foreach (Difficulty::BANDS as $band) {
            foreach (Difficulty::levelsOf($band) as $level) {
                $this->assertSame($band, Difficulty::bandOf($level));
            }
            $this->assertSame($band, Difficulty::bandOf(Difficulty::defaultLevelOf($band)));
        }
    }

    public function test_labels_are_the_one_uzbek_scale(): void
    {
        $this->assertSame('1 — juda oson', Difficulty::label(1));
        $this->assertSame("3 — o'rta", Difficulty::label(3));
        $this->assertSame('5 — juda qiyin', Difficulty::label(5));
        $this->assertSame('Qiyin', Difficulty::bandLabel('hard'));
        $this->assertCount(5, Difficulty::levelOptions());
        $this->assertSame(['value' => 'easy', 'label' => 'Oson'], Difficulty::bandOptions()[0]);
    }

    public function test_parse_level_is_strict(): void
    {
        $this->assertSame(4, Difficulty::parseLevel('4'));
        $this->assertSame(2, Difficulty::parseLevel(' 2 '));
        $this->assertSame(5, Difficulty::parseLevel(5));
        $this->assertNull(Difficulty::parseLevel('oson'));
        $this->assertNull(Difficulty::parseLevel('3.5'));
        $this->assertNull(Difficulty::parseLevel(0));
        $this->assertNull(Difficulty::parseLevel(''));
        $this->assertNull(Difficulty::parseLevel(null));
    }

    public function test_legacy_bank_column_value(): void
    {
        $this->assertSame(['easy', 'easy', 'medium', 'hard', 'expert'], array_map([Difficulty::class, 'legacyBankValue'], [1, 2, 3, 4, 5]));
    }

    public function test_solve_rate_suggestion_uses_battle_thresholds(): void
    {
        $this->assertSame('easy', Difficulty::bandForSolveRate(0.82));
        $this->assertSame('medium', Difficulty::bandForSolveRate(0.55));
        $this->assertSame('hard', Difficulty::bandForSolveRate(0.39));
        $this->assertSame(1, Difficulty::levelForSolveRate(0.9));
        $this->assertSame(2, Difficulty::levelForSolveRate(0.75));
        $this->assertSame(3, Difficulty::levelForSolveRate(0.5));
        $this->assertSame(4, Difficulty::levelForSolveRate(0.3));
        $this->assertSame(5, Difficulty::levelForSolveRate(0.1));
    }
}
