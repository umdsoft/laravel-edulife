<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Javob matni kiritilishi kerak',
            'content.min' => 'Javob kamida 10 ta belgidan iborat bo\'lishi kerak',
        ];
    }
}
