<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\QuestionBank;

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

    /** @return list<string> */
    public function candidateIds(ExamBlueprint $blueprint, ExamBlueprintRule $rule): array
    {
        return QuestionBank::query()
            ->examEligible()
            ->forSubject($blueprint->subject_id)
            ->inTopicTree($rule->topic)
            ->difficultyLevel($rule->difficulty_level)
            ->forPart($rule->part)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }
}
