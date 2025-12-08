<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class SubmitGameResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'score' => 'required|integer|min:0',
            'correct_count' => 'required|integer|min:0',
            'total_count' => 'required|integer|min:1',
            'time_spent_seconds' => 'required|integer|min:0',
        ];
    }
}
