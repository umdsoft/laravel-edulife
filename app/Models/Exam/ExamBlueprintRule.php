<?php

namespace App\Models\Exam;

use App\Enums\Exam\BlueprintPart;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a blueprint: <count> questions of <topic subtree>,
 * <difficulty_level> (NULL = any) in the closed or open <part>.
 */
class ExamBlueprintRule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_blueprint_rules';

    protected $fillable = [
        'blueprint_id',
        'topic_id',
        'difficulty_level',
        'part',
        'count',
        'points_per_question',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_level' => 'integer',
            'part' => BlueprintPart::class,
            'count' => 'integer',
            'points_per_question' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExamBlueprintRule $rule) {
            if ($rule->count < 1) {
                throw new \InvalidArgumentException('Blueprint rule count must be at least 1.');
            }
            if ($rule->difficulty_level !== null && ($rule->difficulty_level < 1 || $rule->difficulty_level > 5)) {
                throw new \InvalidArgumentException('Blueprint rule difficulty must be 1..5 or NULL.');
            }
        });
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
