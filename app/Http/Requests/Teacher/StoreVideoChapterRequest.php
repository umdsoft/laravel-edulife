<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
