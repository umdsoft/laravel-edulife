<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'lesson_id' => ['nullable', 'uuid', 'exists:lessons,id'],
        ];
    }
}
