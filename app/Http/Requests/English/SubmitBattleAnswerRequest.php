<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class SubmitBattleAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer' => 'required|string',
            'time_ms' => 'required|integer|min:0|max:60000',
        ];
    }
}
