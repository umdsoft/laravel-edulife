<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Validation\Validator;

/**
 * Type-specific checks on the option rows of a test question, so a question
 * that can never be answered correctly is not saved.
 */
trait ValidatesQuestionOptions
{
    abstract protected function questionType(): ?string;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $options = collect($this->input('options', []));
            $correct = $options->filter(fn ($o) => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))->count();

            $error = match ($this->questionType()) {
                'single_choice' => $options->count() < 2 ? "Kamida 2 ta variant kiriting"
                    : ($correct !== 1 ? "Bitta to'g'ri javobni belgilang" : null),
                'multiple_choice' => $options->count() < 2 ? "Kamida 2 ta variant kiriting"
                    : ($correct < 1 ? "Kamida bitta to'g'ri javobni belgilang" : null),
                'true_false' => $options->count() !== 2 || $correct !== 1 ? "Rost yoki Yolg'on javobini belgilang" : null,
                'matching' => $options->count() < 2 ? "Kamida 2 ta juftlik kiriting"
                    : ($options->contains(fn ($o) => blank($o['match_text'] ?? null)) ? "Har bir juftlikning ikkala tomonini to'ldiring" : null),
                'ordering' => $options->count() < 2 ? "Kamida 2 ta element kiriting" : null,
                default => null,
            };

            if ($error) {
                $validator->errors()->add('options', $error);
            }
        });
    }
}
