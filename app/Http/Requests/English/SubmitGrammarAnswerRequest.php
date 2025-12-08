<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class SubmitGrammarAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer' => 'required|string',
        ];
    }
}
