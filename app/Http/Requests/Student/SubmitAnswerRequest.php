<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'uuid', 'exists:questions,id'],
            'answer' => ['nullable'], // Can be string, array, or boolean depending on question type
            'time_on_question' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
