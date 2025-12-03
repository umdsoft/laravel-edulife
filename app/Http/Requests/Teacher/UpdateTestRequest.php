<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_attempts' => ['required', 'integer', 'min:1'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
        ];
    }
}
