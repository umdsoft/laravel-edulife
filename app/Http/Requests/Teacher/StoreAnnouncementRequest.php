<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:20', 'max:10000'],
            'type' => ['required', 'in:info,update,important,promotion'],
            'is_pinned' => ['boolean'],
            'send_notification' => ['boolean'],
            'publish_now' => ['boolean'],
            'published_at' => ['required_without:publish_now', 'nullable', 'date', 'after_or_equal:now'],
        ];
    }
}
