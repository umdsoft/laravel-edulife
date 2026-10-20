<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;

/**
 * "Bank yetarliligi": can the approved bank fill every rule of a blueprint
 * with DISTINCT questions (one question can serve only one rule)?
 *
 * Rules are allocated greedily, the most constrained (smallest candidate
 * pool) first. A greedy success is a real allocation, so "satisfiable" is
 * never a false positive. No fallback to adjacent difficulty here: this is
 * the strict check run before an exam opens (stage 5 adds the fallback).
 */
class BlueprintCapacityChecker
{
    /**
     * @return array{
     *     satisfiable: bool,
     *     total_required: int,
     *     rules: list<array{rule_id: string, required: int, candidates: int, allocated: int, shortfall: int}>
     * }
     */
    public function check(ExamBlueprint $blueprint): array
    {
        $rules = $blueprint->rules()->with('topic')->get();

        $pools = [];
        foreach ($rules as $rule) {
            $pools[$rule->id] = $this->candidateIds($blueprint, $rule);
        }

        $order = $rules->sortBy(fn (ExamBlueprintRule $r) => count($pools[$r->id]) - $r->count)->values();
        $used = [];
        $report = [];

        foreach ($order as $rule) {
            $free = array_values(array_diff($pools[$rule->id], array_keys($used)));
            $take = array_slice($free, 0, $rule->count);
            foreach ($take as $id) {
                $used[$id] = true;
            }
            $report[$rule->id] = [
                'rule_id' => $rule->id,
                'required' => $rule->count,
                'candidates' => count($pools[$rule->id]),
                'allocated' => count($take),
                'shortfall' => $rule->count - count($take),
            ];
        }

        // Report in the blueprint's own rule order.
        $lines = $rules->map(fn ($r) => $report[$r->id])->values()->all();

        return [
            'satisfiable' => collect($lines)->every(fn ($l) => $l['shortfall'] === 0),
            'total_required' => (int) $rules->sum('count'),
            'rules' => $lines,
        ];
    }

    public function __construct(private ?QuestionPicker $picker = null)
    {
        $this->picker ??= new QuestionPicker;
    }

    /** The bank filter of a rule (without its difficulty), shared with the per-level counts. */
    public function ruleFilters(ExamBlueprint $blueprint, ExamBlueprintRule $rule): array
    {
        return [
            'eligible' => true,
            'subject_id' => $blueprint->subject_id,
            'topic_id' => $rule->topic,
            'part' => $rule->part,
        ];
    }

    /** @return list<string> */
    public function candidateIds(ExamBlueprint $blueprint, ExamBlueprintRule $rule): array
    {
        if ($rule->topic === null) {
            return [];
        }

        return $this->picker->query($this->ruleFilters($blueprint, $rule))
            ->difficultyLevel($rule->difficulty_level)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * Approved questions per level 1..5 for a rule's topic and part — what the
     * rules editor shows next to the difficulty choice.
     *
     * @return array<int, int>
     */
    public function levelCounts(ExamBlueprint $blueprint, ExamBlueprintRule $rule): array
    {
        return $rule->topic === null
            ? array_fill_keys([1, 2, 3, 4, 5], 0)
            : $this->picker->levelCounts($this->ruleFilters($blueprint, $rule));
    }
}
