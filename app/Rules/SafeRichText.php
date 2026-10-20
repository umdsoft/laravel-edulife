<?php

namespace App\Rules;

use App\Support\RichText\RichTextMedia;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Text field that may contain images and ```graph blocks: images must be our
 * uploads, graphs must pass GraphSpec, per-field limits apply.
 *   'question_text' => ['required', 'string', new SafeRichText],
 *   'content' => ['nullable', 'string', new SafeRichText('lesson')],
 */
class SafeRichText implements ValidationRule
{
    public function __construct(private readonly string $profile = 'question') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        foreach (array_slice(RichTextMedia::errors($value, $this->profile), 0, 4) as $error) {
            $fail($error);
        }
    }
}
