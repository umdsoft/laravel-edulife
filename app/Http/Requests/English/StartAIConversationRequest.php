<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class StartAIConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => 'required|string|max:255',
            'scenario' => 'sometimes|string|in:general,restaurant,shopping,travel,job_interview',
            'difficulty' => 'sometimes|string|in:beginner,intermediate,advanced',
        ];
    }
}
