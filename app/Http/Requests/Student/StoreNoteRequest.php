<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'video_timestamp' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'in:yellow,blue,green,pink'],
        ];
    }
}
