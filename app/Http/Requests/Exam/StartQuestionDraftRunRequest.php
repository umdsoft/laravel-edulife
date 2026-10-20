<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\ExamFormat;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "AI qoralamalar" form: subject, topic, difficulty, format, count, sources.
 * Authorization is done by the route group (admin / teacher).
 */
class StartQuestionDraftRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'uuid', Rule::exists('exam_subjects', 'id')->whereNull('deleted_at')],
            'topic_id' => ['nullable', 'uuid', Rule::exists('exam_topics', 'id')->whereNull('deleted_at')],
            'difficulty_level' => ['required', 'integer', 'between:1,5'],
            'exam_format' => ['nullable', Rule::in(ExamFormat::values())],
            'count' => ['required', 'integer', 'min:1', 'max:'.(int) config('exam.ai.max_count_per_run', 20)],
            'source_document_ids' => ['nullable', 'array', 'max:8'],
            'source_document_ids.*' => ['uuid', Rule::exists('exam_source_documents', 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $subjectId = $this->input('subject_id');
            if ($this->filled('topic_id') && Topic::query()->whereKey($this->input('topic_id'))->value('subject_id') !== $subjectId) {
                $validator->errors()->add('topic_id', "Mavzu tanlangan fanga tegishli emas.");
            }
            $ids = (array) $this->input('source_document_ids', []);
            if ($ids !== [] && ExamSourceDocument::query()->whereIn('id', $ids)->where('subject_id', '!=', $subjectId)->exists()) {
                $validator->errors()->add('source_document_ids', "Manba boshqa fanga tegishli.");
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'subject_id' => 'fan',
            'topic_id' => 'mavzu',
            'difficulty_level' => 'qiyinlik',
            'exam_format' => 'format',
            'count' => 'soni',
            'source_document_ids' => 'manbalar',
        ];
    }
}
