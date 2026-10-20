<?php

namespace App\Http\Controllers\Teacher\Exam\Concerns;

use App\Enums\Exam\ExamFormat;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Rules\SafeRichText;
use App\Services\Exam\QuestionApprovalGuard;
use App\Services\Exam\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared by the bank form and the review queue: validation of the question
 * input and the props a page gets for one question.
 */
trait HandlesBankQuestions
{
    /** Validated editor input (see QuestionEditorService for the shape). */
    protected function validatedQuestion(Request $request, bool $partial = false): array
    {
        // decimal comma from the teacher's keyboard: 0,5 -> 0.5
        $answer = $request->input('answer');
        if (is_array($answer)) {
            foreach (['value', 'tolerance'] as $k) {
                if (isset($answer[$k]) && is_string($answer[$k])) {
                    $answer[$k] = str_replace([',', ' '], ['.', ''], trim($answer[$k]));
                }
            }
            foreach ((array) ($answer['accepted_units'] ?? []) as $i => $u) {
                if (is_array($u) && isset($u['factor']) && is_string($u['factor'])) {
                    $answer['accepted_units'][$i]['factor'] = str_replace(',', '.', trim($u['factor']));
                }
            }
            $request->merge(['answer' => $answer]);
        }

        $type = $request->input('question_type');
        $isChoice = in_array($type, ['single_choice', 'multiple_choice'], true);
        $isNumeric = in_array($type, ['numeric', 'numeric_with_unit'], true);
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'subject_id' => [$req, 'uuid', Rule::exists('exam_subjects', 'id')],
            'topic_id' => ['nullable', 'uuid', Rule::exists('exam_topics', 'id')->whereNull('deleted_at')],
            'question_type' => [$req, Rule::in(QuestionImportService::TYPES)],
            'question_text' => [$req, 'string', 'max:10000', new SafeRichText], // images / graphs: agent M
            // difficulty: always chosen by the teacher, never a silent default (docs/exam/03 §2.1)
            'difficulty_level' => $partial ? ['sometimes', 'required', 'integer', 'between:1,5'] : ['required', 'integer', 'between:1,5'],
            'exam_formats' => ['nullable', 'array'],
            'exam_formats.*' => [Rule::in(ExamFormat::values())],
            'source_ref' => ['nullable', 'string', 'max:255'],
            'solution_text' => ['nullable', 'string', 'max:20000', new SafeRichText],
            'solution_video_url' => ['nullable', 'url:http,https', 'max:500'],
            'units_note' => ['nullable', 'string', 'max:500'],
            'constants' => ['nullable', 'array', 'max:10'],
            'constants.*.key' => ['required', 'string', 'max:20'],
            'constants.*.value' => ['required', 'numeric'],
            'constants.*.unit' => ['nullable', 'string', 'max:20'],
            'options' => [Rule::requiredIf($isChoice && ! $partial), 'array', 'min:4', 'max:5'],
            'options.*.text' => ['required', 'string', 'max:2000', new SafeRichText],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'answer' => [Rule::requiredIf($isNumeric && ! $partial), 'array'],
            'answer.value' => ['nullable', 'numeric'],
            'answer.tolerance_type' => ['nullable', Rule::in(['abs', 'rel'])],
            'answer.tolerance' => ['nullable', 'numeric', 'min:0'],
            'answer.unit' => ['nullable', 'string', 'max:30'],
            'answer.accepted_units' => ['nullable', 'array', 'max:15'],
            'answer.accepted_units.*.unit' => ['required', 'string', 'max:30'],
            'answer.accepted_units.*.factor' => ['required', 'numeric', 'gt:0'],
            'answer.note' => ['nullable', 'string', 'max:255'],
        ], [
            'difficulty_level.required' => 'Qiyinlik darajasini tanlang (1 — juda oson … 5 — juda qiyin).',
            'options.min' => 'Kamida 4 ta variant kerak.',
            'options.max' => 'Ko\'pi bilan 5 ta variant (A–E).',
            'options.*.text.required' => 'Variant matni bo\'sh bo\'lmasin.',
            'answer.accepted_units.*.factor.gt' => 'Birlik koeffitsiyenti musbat son bo\'lishi kerak.',
        ], [
            'subject_id' => 'fan',
            'topic_id' => 'mavzu',
            'question_text' => 'savol sharti',
            'difficulty_level' => 'qiyinlik',
            'solution_video_url' => 'yechim videosi',
            'answer.value' => 'javob',
            'answer.tolerance' => 'xatolik',
        ]);

        // topic must belong to the chosen subject
        $subjectId = $data['subject_id'] ?? null;
        if (! empty($data['topic_id']) && $subjectId
            && Topic::query()->whereKey($data['topic_id'])->value('subject_id') !== $subjectId) {
            throw \Illuminate\Validation\ValidationException::withMessages(['topic_id' => 'Mavzu tanlangan fanga tegishli emas.']);
        }
        // single choice: at most one option marked
        if ($type === 'single_choice'
            && collect($data['options'] ?? [])->where('is_correct', true)->count() > 1) {
            throw \Illuminate\Validation\ValidationException::withMessages(['options' => 'Bitta javobli savolda faqat bitta to\'g\'ri variant bo\'ladi.']);
        }

        if (array_key_exists('constants', $data)) {
            $data['constants'] = collect($data['constants'] ?? [])
                ->mapWithKeys(fn ($c) => [trim($c['key']) => array_filter(['value' => (float) $c['value'], 'unit' => $c['unit'] ?? null], fn ($v) => $v !== null)])
                ->all();
        }
        if (isset($data['options'])) {
            $data['options'] = array_map(fn ($o) => [
                'text' => $o['text'],
                'is_correct' => array_key_exists('is_correct', $o) && $o['is_correct'] !== null ? (bool) $o['is_correct'] : null,
            ], array_values($data['options']));
        }

        return $data;
    }

    /** One question with everything the editor / review screen shows. */
    protected function questionPayload(QuestionBank $q, bool $withViolations = true): array
    {
        $q->loadMissing(['answerOptions', 'numericAnswers', 'topic:id,name_uz,parent_id,code,is_active', 'subject:id,code,name_uz', 'reviewer:id,first_name,last_name', 'creator:id,first_name,last_name']);
        $answer = $q->numericAnswers->first();

        return [
            'id' => $q->id,
            'subject_id' => $q->subject_id,
            'subject' => $q->subject ? ['id' => $q->subject->id, 'code' => $q->subject->code, 'name_uz' => $q->subject->name_uz] : null,
            'topic_id' => $q->topic_id,
            'topic' => $q->topic ? ['id' => $q->topic->id, 'name_uz' => $q->topic->name_uz, 'code' => $q->topic->code, 'is_active' => $q->topic->is_active] : null,
            'question_type' => $q->question_type,
            'question_text' => $q->question_text,
            'difficulty_level' => $q->difficulty_level,
            // observed solve rate next to the teacher's level (suggestion only, never applied)
            'calibration' => app(\App\Services\Exam\DifficultyCalibration::class)->forBank([(string) $q->id => $q->difficulty_level])[(string) $q->id] ?? null,
            'exam_formats' => $q->exam_formats ?? [],
            'source_ref' => $q->source_ref,
            'origin' => $q->origin?->value,
            'review_status' => $q->review_status?->value,
            'reject_reason' => $q->reject_reason,
            'solution_text' => $q->solution_text,
            'solution_video_url' => $q->solution_video_url,
            'units_note' => $q->units_note,
            'constants' => collect($q->constants ?? [])->map(fn ($c, $k) => [
                'key' => $k, 'value' => is_array($c) ? ($c['value'] ?? null) : $c, 'unit' => is_array($c) ? ($c['unit'] ?? null) : null,
            ])->values()->all(),
            'is_demo' => (bool) $q->is_demo,
            'error_reports_count' => (int) $q->error_reports_count,
            'options' => $q->answerOptions->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'text' => $o->text,
                'image' => $o->image,
                'is_correct' => $o->is_correct,
            ])->values()->all(),
            'answer' => $answer ? [
                'value' => $answer->value === null ? null : $this->trimDecimal($answer->value),
                'tolerance_type' => $answer->tolerance_type?->value ?? 'abs',
                'tolerance' => $this->trimDecimal($answer->tolerance),
                'unit' => $answer->unit,
                'accepted_units' => collect($answer->accepted_units ?? [])->map(fn ($f, $u) => ['unit' => $u, 'factor' => $f])->values()->all(),
                'note' => $answer->note,
            ] : null,
            'reviewer' => $q->reviewer ? trim($q->reviewer->first_name.' '.$q->reviewer->last_name) : null,
            'reviewed_at' => $q->reviewed_at?->toIso8601String(),
            'author' => $q->creator ? trim($q->creator->first_name.' '.$q->creator->last_name) : null,
            'created_at' => $q->created_at?->toIso8601String(),
            'violations' => $withViolations ? app(QuestionApprovalGuard::class)->violations($q) : [],
        ];
    }

    protected function subjectsPayload(): array
    {
        return Subject::query()->orderBy('sort_order')->get(['id', 'code', 'name_uz'])
            ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name_uz' => $s->name_uz])->all();
    }

    /** Labels for selects / badges (Uzbek), from the enums. */
    protected function vocabulary(): array
    {
        return [
            'types' => collect(QuestionImportService::TYPES)->map(fn ($t) => ['value' => $t, 'label' => match ($t) {
                'single_choice' => 'Bitta to\'g\'ri javob',
                'multiple_choice' => 'Bir nechta to\'g\'ri javob',
                'numeric' => 'Son javob',
                'numeric_with_unit' => 'Son + birlik',
                default => $t,
            }])->all(),
            'statuses' => collect(ReviewStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->all(),
            'formats' => collect(ExamFormat::cases())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()])->all(),
        ];
    }

    private function trimDecimal(mixed $v): string
    {
        $s = (string) $v;

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}
