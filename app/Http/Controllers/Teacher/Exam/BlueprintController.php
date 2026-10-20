<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Enums\Exam\ExamFormat;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Http\Controllers\Controller;
use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Services\Exam\ExamReadinessChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Exam blueprints (imtihon rejasi): rules editor with a live bank
 * sufficiency check.
 */
class BlueprintController extends Controller
{
    public function __construct(private readonly ExamReadinessChecker $readiness) {}

    public function index(Request $request): Response
    {
        $blueprints = ExamBlueprint::query()
            ->with('subject')
            ->withCount(['rules', 'sessions'])
            ->orderBy('subject_id')
            ->orderBy('purpose')
            ->orderBy('name')
            ->get();

        return Inertia::render('Teacher/Exam/Blueprints/Index', [
            'blueprints' => $blueprints->map(fn (ExamBlueprint $b) => $this->row($b) + [
                'bank_status' => $this->readiness->forBlueprint($b)['status'],
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $subject = Subject::query()->active()->where('id', $request->query('subject'))->first()
            ?? Subject::query()->active()->orderBy('sort_order')->first();

        return Inertia::render('Teacher/Exam/Blueprints/Form', $this->formProps() + [
            'blueprint' => [
                'id' => null,
                'subject_id' => $subject?->id,
                'name' => '',
                'purpose' => $request->query('purpose') === 'placement' ? 'placement' : 'weekly',
                'format' => ExamFormat::MilliySertifikat->value,
                'duration_min' => 90,
                'wrong_unit_ratio' => 0.5,
                'avoid_repeat_weeks' => (int) config('exam.engine.avoid_repeat_weeks', 4),
                'allow_adjacent_difficulty' => true,
                'shuffle_questions' => true,
                'shuffle_options' => true,
                'description' => '',
                'is_active' => true,
                'rules' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $blueprint = DB::transaction(function () use ($data, $request) {
            $blueprint = ExamBlueprint::create($this->fields($data) + ['created_by' => $request->user()->id]);
            $this->syncRules($blueprint, $data['rules']);

            return $blueprint;
        });

        return redirect()->route('teacher.exam.blueprints.edit', $blueprint)->with('success', 'Imtihon rejasi saqlandi.');
    }

    public function edit(ExamBlueprint $blueprint): Response
    {
        $blueprint->load('rules');

        return Inertia::render('Teacher/Exam/Blueprints/Form', $this->formProps() + [
            'blueprint' => $this->row($blueprint) + [
                'format' => $blueprint->format?->value,
                'wrong_unit_ratio' => (float) $blueprint->wrong_unit_ratio,
                'avoid_repeat_weeks' => $blueprint->avoid_repeat_weeks,
                'allow_adjacent_difficulty' => $blueprint->allow_adjacent_difficulty,
                'shuffle_questions' => $blueprint->shuffle_questions,
                'shuffle_options' => $blueprint->shuffle_options,
                'description' => (string) $blueprint->description,
                'rules' => $blueprint->rules->map(fn (ExamBlueprintRule $r) => [
                    'id' => $r->id,
                    'topic_id' => $r->topic_id,
                    'difficulty_level' => $r->difficulty_level,
                    'part' => $r->part?->value,
                    'count' => $r->count,
                    'points_per_question' => (float) $r->points_per_question,
                ])->values(),
            ],
            'readiness' => $this->readiness->forBlueprint($blueprint),
        ]);
    }

    public function update(Request $request, ExamBlueprint $blueprint): RedirectResponse
    {
        $data = $this->validated($request, $blueprint);

        DB::transaction(function () use ($blueprint, $data) {
            $blueprint->update($this->fields($data));
            $this->syncRules($blueprint, $data['rules']);
        });

        return redirect()->route('teacher.exam.blueprints.edit', $blueprint)->with('success', 'Imtihon rejasi yangilandi.');
    }

    public function destroy(ExamBlueprint $blueprint): RedirectResponse
    {
        $busy = $blueprint->sessions()
            ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
            ->whereNull('restricted_to_user_id')
            ->where('type', '!=', ExamSessionType::Placement->value)
            ->exists();
        if ($busy) {
            return back()->with('error', 'Bu reja bo\'yicha rejalashtirilgan imtihon bor. Avval imtihonni bekor qiling.');
        }

        $blueprint->delete();

        return redirect()->route('teacher.exam.blueprints.index')->with('success', 'Imtihon rejasi o\'chirildi.');
    }

    /** Live "bank yetarliligi" of unsaved rules (rules editor). */
    public function capacity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', 'exists:exam_subjects,id'],
            'allow_adjacent_difficulty' => ['boolean'],
            'rules' => ['present', 'array', 'max:100'],
            'rules.*.key' => ['required', 'string', 'max:64'],
            'rules.*.topic_id' => ['required', 'uuid'],
            'rules.*.difficulty_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rules.*.part' => ['required', Rule::in(['closed', 'open'])],
            'rules.*.count' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json($this->readiness->analyse(
            $data['subject_id'],
            array_map(fn ($r) => [
                'key' => (string) $r['key'],
                'topic_id' => (string) $r['topic_id'],
                'difficulty_level' => isset($r['difficulty_level']) ? (int) $r['difficulty_level'] : null,
                'part' => $r['part'],
                'count' => (int) $r['count'],
            ], $data['rules']),
            (bool) ($data['allow_adjacent_difficulty'] ?? true),
        ));
    }

    private function validated(Request $request, ?ExamBlueprint $blueprint = null): array
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', 'exists:exam_subjects,id'],
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', Rule::in(ExamSessionType::values())],
            'format' => ['nullable', Rule::in(ExamFormat::values())],
            'duration_min' => ['required', 'integer', 'min:5', 'max:300'],
            'wrong_unit_ratio' => ['required', 'numeric', 'min:0', 'max:1'],
            'avoid_repeat_weeks' => ['required', 'integer', 'min:0', 'max:52'],
            'allow_adjacent_difficulty' => ['boolean'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'rules' => ['required', 'array', 'min:1', 'max:100'],
            'rules.*.id' => ['nullable', 'uuid'],
            'rules.*.topic_id' => ['required', 'uuid'],
            'rules.*.difficulty_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rules.*.part' => ['required', Rule::in(['closed', 'open'])],
            'rules.*.count' => ['required', 'integer', 'min:1', 'max:200'],
            'rules.*.points_per_question' => ['required', 'numeric', 'min:0.25', 'max:100'],
        ], [], [
            'name' => 'nomi',
            'rules' => 'qoidalar',
            'duration_min' => 'davomiyligi',
        ]);

        $topics = Topic::query()->whereIn('id', array_column($data['rules'], 'topic_id'))->pluck('subject_id', 'id');
        $errors = [];
        foreach ($data['rules'] as $i => $rule) {
            if (($topics[$rule['topic_id']] ?? null) !== $data['subject_id']) {
                $errors["rules.{$i}.topic_id"] = 'Mavzu tanlangan fanga tegishli emas.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    private function fields(array $data): array
    {
        return [
            'subject_id' => $data['subject_id'],
            'name' => $data['name'],
            'purpose' => $data['purpose'],
            'format' => $data['format'] ?? null,
            'duration_min' => (int) $data['duration_min'],
            'total_questions' => array_sum(array_map(fn ($r) => (int) $r['count'], $data['rules'])),
            'wrong_unit_ratio' => $data['wrong_unit_ratio'],
            'avoid_repeat_weeks' => (int) $data['avoid_repeat_weeks'],
            'allow_adjacent_difficulty' => (bool) ($data['allow_adjacent_difficulty'] ?? true),
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? true),
            'shuffle_options' => (bool) ($data['shuffle_options'] ?? true),
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    /** Update rules in place by id (generated variants keep pointing at them), add new, drop removed. */
    private function syncRules(ExamBlueprint $blueprint, array $rules): void
    {
        $keep = [];
        foreach (array_values($rules) as $i => $rule) {
            $attrs = [
                'topic_id' => $rule['topic_id'],
                'difficulty_level' => $rule['difficulty_level'] ?? null,
                'part' => $rule['part'],
                'count' => (int) $rule['count'],
                'points_per_question' => $rule['points_per_question'],
                'sort_order' => $i + 1,
            ];
            $model = ! empty($rule['id']) ? $blueprint->rules()->whereKey($rule['id'])->first() : null;
            if ($model) {
                $model->update($attrs);
            } else {
                $model = $blueprint->rules()->create($attrs);
            }
            $keep[] = $model->id;
        }

        $blueprint->rules()->whereNotIn('id', $keep)->delete();
    }

    private function row(ExamBlueprint $b): array
    {
        return [
            'id' => $b->id,
            'subject_id' => $b->subject_id,
            'subject' => $b->relationLoaded('subject') && $b->subject ? ['code' => $b->subject->code, 'name' => $b->subject->name_uz] : null,
            'name' => $b->name,
            'purpose' => $b->purpose?->value,
            'duration_min' => $b->duration_min,
            'total_questions' => $b->total_questions,
            'is_active' => $b->is_active,
            'rules_count' => $b->rules_count ?? null,
            'sessions_count' => $b->sessions_count ?? null,
        ];
    }

    private function formProps(): array
    {
        $subjects = Subject::query()->active()->orderBy('sort_order')->get();
        $topics = Topic::query()->active()->whereIn('subject_id', $subjects->pluck('id'))
            ->orderBy('level')->orderBy('sort_order')->get(['id', 'subject_id', 'parent_id', 'name_uz', 'level', 'sort_order']);

        // sections followed by their topics, per subject
        $tree = [];
        foreach ($subjects as $subject) {
            $sections = $topics->where('subject_id', $subject->id)->whereNull('parent_id')->sortBy('sort_order');
            $list = [];
            foreach ($sections as $section) {
                $list[] = ['id' => $section->id, 'name' => $section->name_uz, 'level' => 1];
                foreach ($topics->where('parent_id', $section->id)->sortBy('sort_order') as $child) {
                    $list[] = ['id' => $child->id, 'name' => $child->name_uz, 'level' => 2];
                }
            }
            $tree[$subject->id] = $list;
        }

        return [
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name_uz])->values(),
            'topics' => $tree,
            'purposes' => [
                ['value' => 'weekly', 'label' => 'Shanba imtihoni'],
                ['value' => 'placement', 'label' => 'Qabul testi'],
                ['value' => 'open_regional', 'label' => 'Ochiq viloyat imtihoni'],
            ],
            'formats' => array_map(fn (ExamFormat $f) => ['value' => $f->value, 'label' => $f->label()], ExamFormat::cases()),
        ];
    }
}
