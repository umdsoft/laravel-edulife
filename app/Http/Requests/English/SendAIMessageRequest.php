<?php

namespace App\Http\Requests\English;

use Illuminate\Foundation\Http\FormRequest;

class SendAIMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
        ];
    }
}
