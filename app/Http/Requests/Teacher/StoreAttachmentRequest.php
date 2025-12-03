<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'], // 100MB
            'title' => ['nullable', 'string', 'max:255'],
            'is_downloadable' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Fayl tanlanishi kerak',
            'file.max' => 'Fayl hajmi 100MB dan oshmasligi kerak',
        ];
    }
}
