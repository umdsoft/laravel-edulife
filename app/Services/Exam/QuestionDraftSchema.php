<?php

namespace App\Services\Exam;

use App\Support\Exam\QuestionText;
use App\Support\RichText\RichTextMedia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The strict JSON contract of an AI draft response.
 *
 * jsonSchema() is sent to the Messages API as output_config.format (structured
 * outputs). The API constrains the shape, but every item is validated again
 * here because numeric/length/array-size limits are not part of what the API
 * enforces, and because a model reply is untrusted input.
 *
 * Item contract:
 *   stem (LaTeX-markdown), options[4..5] {label A..E in order, text},
 *   topic_code (one of the allowed codes), difficulty_level 1..5,
 *   source_ref, units_note (required for physics), suggested_correct_label.
 */
final class QuestionDraftSchema
{
    public const LABELS = ['A', 'B', 'C', 'D', 'E'];
    public const MIN_OPTIONS = 4;
    public const MAX_OPTIONS = 5;

    /** @return array<string, mixed> */
    public static function jsonSchema(): array
    {
        $nullableString = ['anyOf' => [['type' => 'string'], ['type' => 'null']]];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['questions'],
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['stem', 'options', 'topic_code', 'difficulty_level', 'source_ref', 'units_note', 'suggested_correct_label'],
                        'properties' => [
                            'stem' => ['type' => 'string', 'description' => 'Problem statement, Uzbek Latin, LaTeX in $...$'],
                            'options' => [
                                'type' => 'array',
                                'description' => '4 or 5 options labelled A, B, C, D (E); exactly one is correct',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['label', 'text'],
                                    'properties' => [
                                        'label' => ['type' => 'string', 'enum' => self::LABELS],
                                        'text' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                            'topic_code' => ['type' => 'string'],
                            'difficulty_level' => ['type' => 'integer', 'description' => '1..5'],
                            'source_ref' => ['type' => 'string'],
                            'units_note' => $nullableString,
                            'suggested_correct_label' => ['anyOf' => [['type' => 'string', 'enum' => self::LABELS], ['type' => 'null']]],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Decode the model's text and check the top-level shape.
     *
     * @return list<mixed>|null the raw items, or null when the reply is not the contract
     */
    public static function decode(string $text): ?array
    {
        $text = trim($text);
        // Tolerate a ```json fence even though structured outputs should not produce one.
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $text, $m)) {
            $text = $m[1];
        }

        try {
            $data = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data) || ! isset($data['questions']) || ! is_array($data['questions']) || ! array_is_list($data['questions'])) {
            return null;
        }

        return $data['questions'];
    }

    /**
     * Validate one item. Returns [normalized item, []] or [null, errors].
     *
     * @param  list<string>  $allowedTopicCodes
     * @return array{0: array<string, mixed>|null, 1: list<string>}
     */
    public static function validateItem(mixed $item, array $allowedTopicCodes, bool $physics): array
    {
        if (! is_array($item)) {
            return [null, ['item is not an object']];
        }

        $unknown = array_diff(array_keys($item), ['stem', 'options', 'topic_code', 'difficulty_level', 'source_ref', 'units_note', 'suggested_correct_label']);
        $validator = Validator::make($item, [
            'stem' => ['required', 'string', 'min:10', 'max:5000'],
            'options' => ['required', 'array', 'list', 'min:'.self::MIN_OPTIONS, 'max:'.self::MAX_OPTIONS],
            'options.*' => ['required', 'array:label,text'],
            'options.*.label' => ['required', 'string', 'in:'.implode(',', self::LABELS)],
            'options.*.text' => ['required', 'string', 'max:1000'],
            'topic_code' => ['required', 'string', Rule::in($allowedTopicCodes)],
            'difficulty_level' => ['required', 'integer', 'between:1,5'],
            'source_ref' => ['required', 'string', 'max:255'],
            'units_note' => [$physics ? 'required' : 'nullable', 'string', 'max:500'],
            'suggested_correct_label' => ['nullable', 'string', 'in:'.implode(',', self::LABELS)],
        ]);

        $errors = $validator->errors()->all();
        if ($unknown !== []) {
            $errors[] = 'unknown fields: '.implode(', ', $unknown);
        }
        if ($errors !== []) {
            return [null, array_values($errors)];
        }

        $options = $item['options'];
        $labels = array_column($options, 'label');
        if ($labels !== array_slice(self::LABELS, 0, count($options))) {
            $errors[] = 'options must be labelled A, B, C, D (E) in order';
        }
        // "$1$", "$ 1 $" and "1" are the same option
        $texts = array_map(fn ($o) => preg_replace('/[\s$]+/u', '', QuestionText::normalize((string) $o['text'])), $options);
        if (in_array('', $texts, true)) {
            $errors[] = 'an option text is empty';
        }
        if (count(array_unique($texts)) !== count($texts)) {
            $errors[] = 'option texts must differ';
        }
        if (trim(strip_tags((string) $item['stem'])) === '') {
            $errors[] = 'stem is empty';
        }
        // media (agent M): the model cannot make pictures; a ```graph block must pass GraphSpec
        foreach (array_merge([['stem', (string) $item['stem']]], array_map(fn ($o) => ['option '.$o['label'], (string) $o['text']], $options)) as [$where, $text]) {
            if (RichTextMedia::images($text) !== []) {
                $errors[] = "{$where}: images are not allowed in AI drafts";
            }
            foreach (array_slice(RichTextMedia::errors($text), 0, 3) as $e) {
                if (! str_contains(mb_strtolower($e), 'rasm')) { // images are reported above
                    $errors[] = "{$where}: invalid graph — {$e}";
                }
            }
        }
        $suggested = $item['suggested_correct_label'] ?? null;
        if ($suggested !== null && ! in_array($suggested, $labels, true)) {
            $errors[] = 'suggested_correct_label is not one of the options';
        }
        if ($errors !== []) {
            return [null, $errors];
        }

        return [[
            'stem' => trim((string) $item['stem']),
            'options' => array_map(fn ($o) => ['label' => $o['label'], 'text' => trim((string) $o['text'])], $options),
            'topic_code' => $item['topic_code'],
            'difficulty_level' => (int) $item['difficulty_level'],
            'source_ref' => trim((string) $item['source_ref']),
            'units_note' => isset($item['units_note']) ? trim((string) $item['units_note']) : null,
            'suggested_correct_label' => $suggested,
        ], []];
    }
}
