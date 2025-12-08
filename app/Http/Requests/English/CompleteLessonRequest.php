<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class CompleteLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_results' => 'sometimes|array',
            'quiz_results.percentage' => 'sometimes|numeric|min:0|max:100',
            'quiz_results.correct_count' => 'sometimes|integer|min:0',
            'quiz_results.total_count' => 'sometimes|integer|min:1',
            'time_spent_seconds' => 'sometimes|integer|min:0',
        ];
    }
}
