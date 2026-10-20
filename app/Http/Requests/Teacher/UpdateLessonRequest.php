<?php

namespace App\Http\Requests\Teacher;

use App\Rules\SafeRichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['video', 'text', 'quiz'])],
            'content' => ['nullable', 'string', new SafeRichText('lesson')], // images / graphs: agent M
            'is_free' => ['boolean'],
            'is_preview' => ['boolean'],
        ];
    }
}
