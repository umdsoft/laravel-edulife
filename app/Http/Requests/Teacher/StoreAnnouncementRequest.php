<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // the form always sends publish_now (true/false): a date is needed only when scheduling
        $scheduled = ! $this->boolean('publish_now');

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:20', 'max:10000'],
            'type' => ['required', 'in:info,update,important,promotion'],
            'is_pinned' => ['boolean'],
            'send_notification' => ['boolean'],
            'publish_now' => ['boolean'],
            'published_at' => $scheduled
                ? ['required', 'date', 'after_or_equal:now']
                : ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'published_at.required' => "E'lon qilinadigan sanani tanlang yoki «Hozir e'lon qilish»ni yoqing",
        ];
    }
}
