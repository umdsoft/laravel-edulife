<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lessons' => ['required', 'array', 'min:1', 'max:50'],
            'lessons.*.title' => ['required', 'string', 'max:255'],
            'lessons.*.description' => ['nullable', 'string', 'max:2000'],
            'lessons.*.type' => ['nullable', 'in:video,text,quiz'],
            'lessons.*.is_free' => ['boolean'],
            'lessons.*.is_preview' => ['boolean'],
        ];
    }
}
