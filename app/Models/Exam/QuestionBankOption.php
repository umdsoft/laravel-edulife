<?php

namespace App\Models\Exam;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Answer option (A..E) of a closed question.
 *
 * is_correct stays NULL until the teacher marks the correct option; AI
 * drafts never write it (they may only fill ai_suggested_correct).
 */
class QuestionBankOption extends Model
{
    use HasFactory, HasUuids;

    public const LABELS = ['A', 'B', 'C', 'D', 'E'];

    protected $table = 'question_bank_options';

    protected $fillable = [
        'question_id',
        'label',
        'text',
        'image',
        'is_correct',
        'ai_suggested_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'ai_suggested_correct' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuestionBankOption $option) {
            $option->label = strtoupper((string) $option->label);
            if (! in_array($option->label, self::LABELS, true)) {
                throw new \InvalidArgumentException("Invalid option label [{$option->label}], expected A-E.");
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    public function isMarked(): bool
    {
        return $this->is_correct !== null;
    }
}
