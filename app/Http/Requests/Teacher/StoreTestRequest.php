<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            // A lesson / module test must be linked to a module / lesson of THIS course:
            // unlinked it is shown to nobody, and a foreign id would put the test into
            // another teacher's lesson (students see tests by testable_id only).
            'module_id' => [
                Rule::requiredIf(in_array($this->input('type'), ['lesson_test', 'module_test'], true)),
                'nullable', 'uuid',
                Rule::exists('modules', 'id')->where('course_id', $courseId)->whereNull('deleted_at'),
            ],
            'lesson_id' => [
                Rule::requiredIf($this->input('type') === 'lesson_test'),
                'nullable', 'uuid',
                Rule::exists('lessons', 'id')->where('course_id', $courseId)
                    ->where('module_id', (string) $this->input('module_id'))->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['lesson_test', 'module_test', 'final_test'])],
            'max_attempts' => ['required', 'integer', 'min:1'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
        ];
    }
}
