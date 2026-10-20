<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Difficulty;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    use ValidatesQuestionOptions;

    protected function questionType(): ?string
    {
        return $this->input('type');
    }

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
            // easy / medium / hard: battles use it to pick questions of one level.
            // Always chosen by the teacher, no silent "medium" (docs/exam/03 §2.1).
            'difficulty' => ['required', Rule::in(Difficulty::BANDS)],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_text' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
            'options.*.match_text' => ['nullable', 'string'],
            'options.*.correct_position' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return ['difficulty.required' => "Qiyinlik darajasini tanlang: oson, o'rta yoki qiyin."];
    }
}
