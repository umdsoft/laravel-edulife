<?php

namespace App\Services\Exam;

use App\Enums\Exam\BlueprintPart;
use App\Enums\Exam\ExamQuestionType;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\QuestionBankOption;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Blueprint -> personal variant.
 *
 * For every rule (topic subtree x difficulty x part x count) it draws only
 * exam-eligible (approved) questions, never the same question for two
 * rules, and prefers questions the student has not seen in the blueprint's
 * last `avoid_repeat_weeks` weeks. When the bank is short it
 *   1. reuses recently seen questions             (warning "repeat"),
 *   2. fills from the neighbouring difficulty ±1  (warning "adjacent", used_fallback = true)
 *      when the blueprint allows it,
 *   3. leaves the rule short                      (warning "shortage").
 * Question order (closed part first, then open) and option order are
 * shuffled with a Mt19937 RNG seeded by the stored `seed`, so
 * plan(blueprint, seed, seen) is reproducible for audits.
 */
class VariantGenerator
{
    public function __construct(private ExamAudience $audience) {}

    /** Variant of $user in $session (the existing one, or a new one). */
    public function generate(ExamSession $session, User $user, ?int $seed = null, ?array $pools = null): ExamVariant
    {
        $existing = ExamVariant::query()->where('session_id', $session->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $blueprint = $this->blueprint($session);
        $pools ??= $this->pools($blueprint);
        $seed ??= random_int(1, 2_147_483_647);
        $seen = $this->recentlySeen($user, $session, (int) $blueprint->avoid_repeat_weeks);
        $plan = $this->plan($blueprint, $seed, $seen, $pools);

        try {
            return ExamVariant::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'seed' => $seed,
                'question_ids' => $plan['question_ids'],
                'option_orders' => $plan['option_orders'],
                'rule_map' => $plan['rule_map'],
                'used_fallback' => $plan['used_fallback'],
                'warnings' => $plan['warnings'] ?: null,
                'generated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // a parallel request created it first
            return ExamVariant::query()->where('session_id', $session->id)->where('user_id', $user->id)->firstOrFail();
        }
    }

    /**
     * Pre-generate the variants of every student of the session's audience.
     *
     * @return array{created: int, existing: int, with_fallback: int, short: int}
     */
    public function generateForSession(ExamSession $session): array
    {
        $blueprint = $this->blueprint($session);
        $pools = $this->pools($blueprint);
        $summary = ['created' => 0, 'existing' => 0, 'with_fallback' => 0, 'short' => 0];

        foreach ($this->audience->students($session) as $user) {
            $variant = $this->generate($session, $user, null, $pools);
            $variant->wasRecentlyCreated ? $summary['created']++ : $summary['existing']++;
            if ($variant->used_fallback) {
                $summary['with_fallback']++;
            }
            if (collect($variant->warnings ?? [])->contains('type', 'shortage')) {
                $summary['short']++;
            }
        }

        $session->forceFill(['variants_generated_at' => now()])->save();

        return $summary;
    }

    /**
     * Candidate snapshot of a blueprint: per rule the strict pool and the
     * adjacent-difficulty pools, plus question types and option ids.
     *
     * @return array{rules: array<string, array{strict: list<string>, adjacent: array<int, list<string>>}>, types: array<string, string>, options: array<string, list<string>>}
     */
    public function pools(ExamBlueprint $blueprint): array
    {
        $blueprint->loadMissing('rules.topic');
        $rules = [];
        $all = [];

        foreach ($blueprint->rules as $rule) {
            $topicIds = $rule->topic ? $rule->topic->descendantAndSelfIds() : [];
            $base = fn () => QuestionBank::query()
                ->examEligible()
                ->forSubject($blueprint->subject_id)
                ->whereIn('topic_id', $topicIds)
                ->forPart($rule->part)
                ->orderBy('id');

            $strict = $this->ids($base()->difficultyLevel($rule->difficulty_level));
            $adjacent = [];
            if ($blueprint->allow_adjacent_difficulty && $rule->difficulty_level !== null) {
                foreach ([$rule->difficulty_level - 1, $rule->difficulty_level + 1] as $level) {
                    if ($level >= 1 && $level <= 5) {
                        $adjacent[$level] = $this->ids($base()->where('difficulty_level', $level));
                    }
                }
            }

            $rules[$rule->id] = ['strict' => $strict, 'adjacent' => $adjacent];
            $all = [...$all, ...$strict, ...array_merge([], ...array_values($adjacent))];
        }

        $all = array_values(array_unique($all));
        $types = [];
        $options = [];
        foreach (array_chunk($all, 500) as $chunk) {
            foreach (QuestionBank::query()->whereIn('id', $chunk)->pluck('question_type', 'id') as $id => $type) {
                $types[(string) $id] = (string) $type;
            }
            $rows = QuestionBankOption::query()->whereIn('question_id', $chunk)
                ->orderBy('sort_order')->orderBy('label')->get(['id', 'question_id']);
            foreach ($rows as $option) {
                $options[(string) $option->question_id][] = (string) $option->id;
            }
        }

        return ['rules' => $rules, 'types' => $types, 'options' => $options];
    }

    /**
     * Deterministic selection: the same blueprint, bank snapshot, seed and
     * seen-set always give the same variant.
     *
     * @param  list<string>  $seen
     * @return array{question_ids: list<string>, option_orders: array<string, list<string>>, rule_map: array<string, string>, warnings: list<array>, used_fallback: bool}
     */
    public function plan(ExamBlueprint $blueprint, int $seed, array $seen = [], ?array $pools = null): array
    {
        $pools ??= $this->pools($blueprint);
        $blueprint->loadMissing('rules');
        $rng = new Randomizer(new Mt19937($seed));
        $seenSet = array_fill_keys($seen, true);

        /** @var list<ExamBlueprintRule> $rules */
        $rules = $blueprint->rules->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values()->all();

        // the most constrained rule draws first (same idea as BlueprintCapacityChecker)
        $slack = fn (ExamBlueprintRule $r) => count($pools['rules'][$r->id]['strict'] ?? []) - (int) $r->count;
        $order = $rules;
        usort($order, fn (ExamBlueprintRule $a, ExamBlueprintRule $b) => $slack($a) <=> $slack($b)
            ?: $a->sort_order <=> $b->sort_order
            ?: strcmp((string) $a->id, (string) $b->id));

        $used = [];
        $picks = [];
        $warnings = [];
        $usedFallback = false;

        foreach ($order as $rule) {
            $need = (int) $rule->count;
            $pool = $pools['rules'][$rule->id] ?? ['strict' => [], 'adjacent' => []];

            [$fresh, $stale] = $this->partition($pool['strict'], $used, $seenSet);
            $taken = array_slice($this->shuffle($rng, $fresh), 0, $need);

            if (count($taken) < $need && $stale !== []) {
                $repeat = array_slice($this->shuffle($rng, $stale), 0, $need - count($taken));
                $taken = [...$taken, ...$repeat];
                $warnings[] = ['type' => 'repeat', 'rule_id' => $rule->id, 'count' => count($repeat)];
            }

            if (count($taken) < $need && $pool['adjacent'] !== []) {
                $usedHere = array_fill_keys($taken, true) + $used;
                $adjacentIds = [];
                $levelOf = [];
                foreach ($pool['adjacent'] as $level => $ids) {
                    foreach ($ids as $id) {
                        $levelOf[$id] ??= $level;
                        $adjacentIds[] = $id;
                    }
                }
                [$aFresh, $aStale] = $this->partition(array_values(array_unique($adjacentIds)), $usedHere, $seenSet);
                $fill = array_slice([...$this->shuffle($rng, $aFresh), ...$this->shuffle($rng, $aStale)], 0, $need - count($taken));
                if ($fill !== []) {
                    $taken = [...$taken, ...$fill];
                    $usedFallback = true;
                    $levels = array_count_values(array_map(fn ($id) => $levelOf[$id], $fill));
                    ksort($levels);
                    $warnings[] = [
                        'type' => 'adjacent',
                        'rule_id' => $rule->id,
                        'from' => $rule->difficulty_level,
                        'levels' => $levels,
                        'count' => count($fill),
                    ];
                }
            }

            if (count($taken) < $need) {
                $warnings[] = ['type' => 'shortage', 'rule_id' => $rule->id, 'missing' => $need - count($taken)];
            }

            foreach ($taken as $id) {
                $used[$id] = true;
            }
            $picks[$rule->id] = $taken;
        }

        // closed part first, then open; each in rule order, shuffled when asked
        $questionIds = [];
        $ruleMap = [];
        foreach ([BlueprintPart::Closed, BlueprintPart::Open] as $part) {
            $ids = [];
            foreach ($rules as $rule) {
                if ($rule->part !== $part) {
                    continue;
                }
                foreach ($picks[$rule->id] ?? [] as $id) {
                    $ids[] = $id;
                    $ruleMap[$id] = $rule->id;
                }
            }
            if ($blueprint->shuffle_questions) {
                $ids = $this->shuffle($rng, $ids);
            }
            $questionIds = [...$questionIds, ...$ids];
        }

        $optionOrders = [];
        foreach ($questionIds as $id) {
            $optionIds = $pools['options'][$id] ?? [];
            if ($optionIds === []) {
                continue;
            }
            $keep = ! $blueprint->shuffle_options || ($pools['types'][$id] ?? null) === ExamQuestionType::TrueFalse->value;
            $optionOrders[$id] = $keep ? $optionIds : $this->shuffle($rng, $optionIds);
        }

        return [
            'question_ids' => $questionIds,
            'option_orders' => $optionOrders,
            'rule_map' => $ruleMap,
            'warnings' => $warnings,
            'used_fallback' => $usedFallback,
        ];
    }

    /**
     * Questions in the student's variants of the same subject generated in
     * the last $weeks weeks (other sessions only).
     *
     * @return list<string>
     */
    public function recentlySeen(User $user, ExamSession $session, int $weeks): array
    {
        if ($weeks <= 0) {
            return [];
        }

        $sessionIds = ExamSession::withTrashed()->where('subject_id', $session->subject_id)->select('id');

        return ExamVariant::query()
            ->where('user_id', $user->id)
            ->where('session_id', '!=', $session->id)
            ->whereIn('session_id', $sessionIds)
            ->where('generated_at', '>=', now()->subWeeks($weeks))
            ->pluck('question_ids')
            ->flatten()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function blueprint(ExamSession $session): ExamBlueprint
    {
        return ExamBlueprint::withTrashed()->with('rules.topic')->findOrFail($session->blueprint_id);
    }

    /** @return list<string> */
    private function ids($query): array
    {
        return $query->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    /** @return array{0: list<string>, 1: list<string>} [fresh, seen] among the not-yet-used ids */
    private function partition(array $ids, array $used, array $seen): array
    {
        $fresh = [];
        $stale = [];
        foreach ($ids as $id) {
            if (isset($used[$id])) {
                continue;
            }
            if (isset($seen[$id])) {
                $stale[] = $id;
            } else {
                $fresh[] = $id;
            }
        }

        return [$fresh, $stale];
    }

    private function shuffle(Randomizer $rng, array $items): array
    {
        return $items === [] ? [] : $rng->shuffleArray(array_values($items));
    }
}
