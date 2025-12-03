<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pdf_template' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'], // 10MB max
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'direction_id' => ['nullable', 'uuid', 'exists:directions,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'placeholders' => ['required', 'array'],
            'placeholders.student_name' => ['required', 'array'],
            'placeholders.student_name.x' => ['required', 'numeric'],
            'placeholders.student_name.y' => ['required', 'numeric'],
            'placeholders.course_title' => ['required', 'array'],
            'placeholders.completion_date' => ['required', 'array'],
            'placeholders.certificate_number' => ['required', 'array'],
            'placeholders.qr_code' => ['required', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Shablon nomini kiriting',
            'pdf_template.required' => 'PDF shablon faylini yuklang',
            'pdf_template.mimes' => 'Faqat PDF, PNG, JPG formatdagi fayllar',
            'pdf_template.max' => 'Fayl hajmi 10MB dan oshmasligi kerak',
            'placeholders.required' => 'Placeholder pozitsiyalarini kiriting',
        ];
    }
}
