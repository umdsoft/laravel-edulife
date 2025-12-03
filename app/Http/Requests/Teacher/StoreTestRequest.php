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
        return [
            'module_id' => ['nullable', 'exists:modules,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
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
