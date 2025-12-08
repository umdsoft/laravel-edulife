<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class CompleteUnitTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => 'required|integer|min:0|max:100',
            'answers' => 'sometimes|array',
            'time_spent_seconds' => 'sometimes|integer|min:0',
        ];
    }
}
