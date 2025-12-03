<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'true_false', 'fill_blank', 'matching', 'ordering'])],
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
