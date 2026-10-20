<?php

namespace App\Services;

use App\Models\Setting;

/**
 * The tunable numbers of battle question selection and scoring
 * (docs/battle/SCORING.md). Each one can be overridden by a `settings` row
 * with the same key (group "battle"); a missing or invalid row keeps the
 * default, which is clamped to a sane range.
 */
class BattleScoringSettings
{
    public const LEVELS = ['easy', 'medium', 'hard'];

    public const DEFAULTS = [
        // value of a question: V = base × weight(p̂)
        'battle_scoring_base' => 100,
        'battle_scoring_model' => 'linear',      // linear: 1 + k·(1 − p̂) | logit: e^(β·b), b = ln((1 − p̂)/p̂)
        'battle_scoring_k' => 2.0,
        'battle_scoring_logit_beta' => 0.5,
        // Bayesian smoothing: p̂ = (correct + m·p₀) / (attempts + m)
        'battle_scoring_prior_strength' => 10,
        'battle_scoring_prior_easy' => 0.80,
        'battle_scoring_prior_medium' => 0.60,
        'battle_scoring_prior_hard' => 0.35,
        // course test answers count as this fraction of a battle attempt (0 = ignore them)
        'battle_scoring_test_weight' => 0.5,
        // in the battle: points = V × solvers^(−γ) × (1 + λ·speed)
        'battle_scoring_solver_exponent' => 1.0,
        'battle_scoring_speed_bonus' => 0.2,
        // level bands of p̂: easy ≥ 0.70 > medium ≥ 0.40 > hard
        'battle_level_easy_min_p' => 0.70,
        'battle_level_hard_max_p' => 0.40,
        // "automatic" difficulty from the players' average ELO
        'battle_elo_medium_from' => 1200,
        'battle_elo_hard_from' => 1500,
        // questions either player saw in their last N battles are avoided
        'battle_recent_battles' => 20,
    ];

    private function raw(string $key)
    {
        return Setting::get($key, self::DEFAULTS[$key]);
    }

    private function number(string $key, float $min, float $max): float
    {
        $value = $this->raw($key);
        $value = is_numeric($value) ? (float) $value : (float) self::DEFAULTS[$key];

        return min($max, max($min, $value));
    }

    public function base(): int
    {
        return (int) round($this->number('battle_scoring_base', 1, 100000));
    }

    public function model(): string
    {
        return $this->raw('battle_scoring_model') === 'logit' ? 'logit' : 'linear';
    }

    public function k(): float
    {
        return $this->number('battle_scoring_k', 0, 20);
    }

    public function logitBeta(): float
    {
        return $this->number('battle_scoring_logit_beta', 0, 3);
    }

    public function priorStrength(): float
    {
        return $this->number('battle_scoring_prior_strength', 0, 1000);
    }

    /** The prior solve rate p₀ of a question's labelled difficulty. */
    public function prior(?string $label): float
    {
        $label = in_array($label, self::LEVELS, true) ? $label : 'medium';

        return $this->number("battle_scoring_prior_{$label}", 0.01, 0.99);
    }

    public function testWeight(): float
    {
        return $this->number('battle_scoring_test_weight', 0, 1);
    }

    public function solverExponent(): float
    {
        return $this->number('battle_scoring_solver_exponent', 0, 3);
    }

    public function speedBonus(): float
    {
        return $this->number('battle_scoring_speed_bonus', 0, 1);
    }

    public function easyMinP(): float
    {
        return $this->number('battle_level_easy_min_p', 0.02, 0.99);
    }

    public function hardMaxP(): float
    {
        return min($this->easyMinP(), $this->number('battle_level_hard_max_p', 0.01, 0.98));
    }

    public function eloMediumFrom(): int
    {
        return (int) $this->number('battle_elo_medium_from', 0, 5000);
    }

    public function eloHardFrom(): int
    {
        return (int) max($this->eloMediumFrom(), $this->number('battle_elo_hard_from', 0, 5000));
    }

    public function recentBattles(): int
    {
        return (int) $this->number('battle_recent_battles', 0, 500);
    }
}
