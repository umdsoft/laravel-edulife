<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Teachers type links the way they share them: "@ustoz", "t.me/ustoz",
     * "example.uz". Turn those into full https:// URLs instead of rejecting them.
     */
    protected function prepareForValidation(): void
    {
        $hosts = ['telegram' => 'https://t.me/', 'instagram' => 'https://instagram.com/', 'youtube' => 'https://youtube.com/@'];

        $links = $this->input('social_links');
        if (is_array($links)) {
            foreach ($links as $key => $value) {
                $links[$key] = self::normalizeUrl($value, $hosts[$key] ?? null);
            }
            $this->merge(['social_links' => $links]);
        }

        if ($this->has('website')) {
            $this->merge(['website' => self::normalizeUrl($this->input('website'))]);
        }
    }

    public static function normalizeUrl(mixed $value, ?string $handleBase = null): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $value = trim($value);
        if ($value === '' || preg_match('#^https?://#i', $value)) {
            return $value;
        }
        // "@ustoz" → profile URL of that network
        if ($handleBase && preg_match('/^@([A-Za-z0-9_.]{2,64})$/', $value, $m)) {
            return $handleBase.$m[1];
        }
        // "t.me/ustoz", "www.example.uz" → https://…
        if (preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)+(/\S*)?$#i', $value)) {
            return 'https://'.$value;
        }

        return $value;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email,' . auth()->id()],
            'bio' => ['nullable', 'string', 'max:2000'],
            'headline' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'array', 'max:30'],
            'expertise.*' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:255'],
        ];
    }
}
