<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVocabularyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quality' => 'required|integer|min:0|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'quality.min' => 'Quality must be between 0 (blackout) and 5 (perfect)',
            'quality.max' => 'Quality must be between 0 (blackout) and 5 (perfect)',
        ];
    }
}
