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
            // Allowlist both the client extension and the detected content type, so no
            // .php/.html/.svg can land on the public disk
            'file' => [
                'required', 'file', 'max:102400', // 100MB
                'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,md,jpg,jpeg,png,gif,mp3,wav,mp4,mov,py,css,json',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,md,jpg,jpeg,png,gif,mp3,wav,mp4,mov,json',
            ],
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
