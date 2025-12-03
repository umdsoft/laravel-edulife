<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificateTemplateRequest extends FormRequest
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
            'pdf_template' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'], // Optional on update
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
}
