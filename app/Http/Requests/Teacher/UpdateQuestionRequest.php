<?php

namespace App\Http\Requests\Teacher;

use App\Rules\SafeRichText;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\Difficulty;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    use ValidatesQuestionOptions;

    protected function questionType(): ?string
    {
        return $this->route('question')?->type;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', new SafeRichText], // images / graphs: agent M
            'points' => ['required', 'integer', 'min:1'],
            // easy / medium / hard: battles use it to pick questions of one level.
            // Omitted keeps the saved level; it can never be cleared (docs/exam/03 §2.1).
            'difficulty' => ['sometimes', 'required', Rule::in(Difficulty::BANDS)],
            'explanation' => ['nullable', 'string', new SafeRichText],
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_text' => ['required', 'string', new SafeRichText],
            'options.*.is_correct' => ['boolean'],
            'options.*.match_text' => ['nullable', 'string', new SafeRichText],
            'options.*.correct_position' => ['nullable', 'integer'],
        ];
    }
}
