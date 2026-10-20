<?php

namespace App\Services\Exam;

use App\Enums\Exam\BlueprintPart;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\Topic;

/**
 * "Bank yetarliligi" shown to the teacher before a session opens and live
 * in the blueprint rules editor.
 *
 * Per rule: approved candidates, a strict distinct allocation (most
 * constrained rule first, like BlueprintCapacityChecker), the shortfall and
 * whether the neighbouring difficulty (±1) can cover it.
 *   status ok        every rule is filled strictly
 *          fallback  some rule needs the neighbouring difficulty (allowed by the blueprint)
 *          short     the bank can not fill the blueprint even with the fallback
 */
class ExamReadinessChecker
{
    public function __construct(
        private BlueprintCapacityChecker $capacity,
        private ExamAudience $audience,
    ) {}

    /**
     * @param  list<array{key: string, topic_id: string, difficulty_level: ?int, part: string, count: int}>  $rules
     * @return array{status: string, total_required: int, rules: list<array>}
     */
    public function analyse(string $subjectId, array $rules, bool $allowAdjacent): array
    {
        $blueprint = new ExamBlueprint(['subject_id' => $subjectId]);
        $topics = Topic::query()->whereIn('id', array_column($rules, 'topic_id'))->get()->keyBy('id');

        $models = [];
        $pools = [];
        foreach ($rules as $r) {
            $topic = $topics->get($r['topic_id']);
            $model = new ExamBlueprintRule([
                'topic_id' => $r['topic_id'],
                'difficulty_level' => $r['difficulty_level'],
                'part' => $r['part'],
                'count' => (int) $r['count'],
            ]);
            $model->setRelation('topic', $topic);
            $models[$r['key']] = $model;
            $pools[$r['key']] = $topic && $topic->subject_id === $subjectId
                ? array_map('strval', $this->capacity->candidateIds($blueprint, $model))
                : [];
        }

        $order = array_keys($models);
        usort($order, fn ($a, $b) => (count($pools[$a]) - $models[$a]->count) <=> (count($pools[$b]) - $models[$b]->count));

        $used = [];
        $lines = [];
        foreach ($order as $key) {
            $rule = $models[$key];
            $free = array_values(array_filter($pools[$key], fn ($id) => ! isset($used[$id])));
            $take = array_slice($free, 0, $rule->count);
            foreach ($take as $id) {
                $used[$id] = true;
            }
            $lines[$key] = [
                'key' => $key,
                'topic_id' => $rule->topic_id,
                'topic' => $rule->topic?->name_uz,
                'difficulty_level' => $rule->difficulty_level,
                'part' => $rule->part instanceof BlueprintPart ? $rule->part->value : $rule->part,
                'required' => (int) $rule->count,
                'candidates' => count($pools[$key]),
                'allocated' => count($take),
                'shortfall' => (int) $rule->count - count($take),
                'adjacent' => 0,
            ];
        }

        // can the neighbouring difficulty cover the shortfall?
        foreach ($lines as $key => &$line) {
            $rule = $models[$key];
            if ($line['shortfall'] <= 0 || ! $allowAdjacent || $rule->difficulty_level === null || ! $rule->topic) {
                continue;
            }
            $extra = 0;
            foreach ([$rule->difficulty_level - 1, $rule->difficulty_level + 1] as $level) {
                if ($level < 1 || $level > 5 || $extra >= $line['shortfall']) {
                    continue;
                }
                $probe = clone $rule;
                $probe->difficulty_level = $level;
                $probe->setRelation('topic', $rule->topic);
                foreach ($this->capacity->candidateIds($blueprint, $probe) as $id) {
                    $id = (string) $id;
                    if (! isset($used[$id]) && $extra < $line['shortfall']) {
                        $used[$id] = true;
                        $extra++;
                    }
                }
            }
            $line['adjacent'] = $extra;
        }
        unset($line);

        $out = [];
        $status = 'ok';
        foreach ($rules as $r) {
            $line = $lines[$r['key']];
            $line['status'] = $line['shortfall'] === 0 ? 'ok' : ($line['adjacent'] >= $line['shortfall'] ? 'fallback' : 'short');
            $line['reserve'] = $line['required'] > 0 ? round($line['candidates'] / $line['required'], 1) : null;
            if ($line['status'] === 'short') {
                $status = 'short';
            } elseif ($line['status'] === 'fallback' && $status === 'ok') {
                $status = 'fallback';
            }
            $out[] = $line;
        }

        return [
            'status' => $rules === [] ? 'short' : $status,
            'total_required' => array_sum(array_map(fn ($r) => (int) $r['count'], $rules)),
            'rules' => $out,
        ];
    }

    public function forBlueprint(ExamBlueprint $blueprint): array
    {
        $blueprint->loadMissing('rules');
        $rules = $blueprint->rules->map(fn (ExamBlueprintRule $r) => [
            'key' => (string) $r->id,
            'topic_id' => (string) $r->topic_id,
            'difficulty_level' => $r->difficulty_level,
            'part' => $r->part?->value ?? 'closed',
            'count' => (int) $r->count,
        ])->values()->all();

        return $this->analyse($blueprint->subject_id, $rules, (bool) $blueprint->allow_adjacent_difficulty) + [
            'consistent' => $blueprint->isConsistent(),
            'total_questions' => (int) $blueprint->total_questions,
        ];
    }

    public function forSession(ExamSession $session): array
    {
        $blueprint = ExamBlueprint::withTrashed()->with('rules')->findOrFail($session->blueprint_id);
        $variants = ExamVariant::query()->where('session_id', $session->id);

        return [
            'bank' => $this->forBlueprint($blueprint),
            'audience' => $this->audience->count($session),
            'variants' => (clone $variants)->count(),
            'variants_with_fallback' => (clone $variants)->where('used_fallback', true)->count(),
            'variants_generated_at' => $session->variants_generated_at?->toIso8601String(),
        ];
    }
}
