<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_text' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
            'options.*.match_text' => ['nullable', 'string'],
            'options.*.correct_position' => ['nullable', 'integer'],
        ];
    }
}
