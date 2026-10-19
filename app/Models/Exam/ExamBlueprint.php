<?php

namespace App\Models\Exam;

use App\Enums\Exam\ExamFormat;
use App\Enums\Exam\ExamSessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Exam plan: which topics, difficulties and how many questions.
 */
class ExamBlueprint extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'exam_blueprints';

    protected $fillable = [
        'subject_id',
        'name',
        'purpose',
        'format',
        'duration_min',
        'total_questions',
        'wrong_unit_ratio',
        'avoid_repeat_weeks',
        'allow_adjacent_difficulty',
        'shuffle_questions',
        'shuffle_options',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => ExamSessionType::class,
            'format' => ExamFormat::class,
            'duration_min' => 'integer',
            'total_questions' => 'integer',
            'wrong_unit_ratio' => 'decimal:2',
            'avoid_repeat_weeks' => 'integer',
            'allow_adjacent_difficulty' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ExamBlueprintRule::class, 'blueprint_id')->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'blueprint_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePurpose(Builder $query, ExamSessionType $purpose): Builder
    {
        return $query->where('purpose', $purpose->value);
    }

    /** Sum of rule counts; must equal total_questions. */
    public function rulesQuestionCount(): int
    {
        return (int) $this->rules()->sum('count');
    }

    public function isConsistent(): bool
    {
        return $this->rulesQuestionCount() === (int) $this->total_questions;
    }
}
