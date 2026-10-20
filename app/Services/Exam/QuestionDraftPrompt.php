<?php

namespace App\Services\Exam;

use RuntimeException;

/**
 * A versioned prompt from resources/prompts/exam/<name>.md.
 *
 * File layout:
 *   ---
 *   name: question_draft
 *   version: 1.0.0
 *   ---
 *   <!-- system -->
 *   ...system prompt...
 *   <!-- user -->
 *   ...user message template with {{placeholders}}...
 *
 * The version from the front matter is stored with every run, so a change of
 * the prompt is visible in the cost / quality log.
 */
final class QuestionDraftPrompt
{
    private function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $system,
        public readonly string $userTemplate,
    ) {}

    public static function load(string $name): self
    {
        if (! preg_match('/^[a-z0-9_\-]+$/', $name)) {
            throw new RuntimeException('Invalid prompt name.');
        }
        $path = resource_path("prompts/exam/{$name}.md");
        if (! is_file($path)) {
            throw new RuntimeException("Prompt file resources/prompts/exam/{$name}.md is missing.");
        }

        return self::parse((string) file_get_contents($path), $name);
    }

    public static function parse(string $raw, string $fallbackName = 'prompt'): self
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $meta = [];
        if (preg_match('/^---\n(.*?)\n---\n/s', $raw, $m)) {
            foreach (explode("\n", $m[1]) as $line) {
                if (preg_match('/^([a-z_]+):\s*(.*)$/', trim($line), $kv)) {
                    $meta[$kv[1]] = trim($kv[2], " \"'");
                }
            }
            $raw = substr($raw, strlen($m[0]));
        }

        $parts = preg_split('/^<!--\s*(system|user)\s*-->\s*$/m', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
        $sections = [];
        for ($i = 1; $i < count($parts); $i += 2) {
            $sections[$parts[$i]] = trim($parts[$i + 1] ?? '');
        }

        if (empty($meta['version']) || empty($sections['system']) || empty($sections['user'])) {
            throw new RuntimeException('Prompt needs a version in the front matter and <!-- system --> / <!-- user --> sections.');
        }

        return new self($meta['name'] ?? $fallbackName, $meta['version'], $sections['system'], $sections['user']);
    }

    /** @param array<string, string|int> $vars */
    public function renderUser(array $vars): string
    {
        // One pass over the template: inserted values (LaTeX may contain
        // "{{...}}") are never scanned again; unknown placeholders become ''.
        $text = preg_replace_callback(
            '/\{\{([a-z_]+)\}\}/',
            fn (array $m) => (string) ($vars[$m[1]] ?? ''),
            $this->userTemplate,
        );

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
