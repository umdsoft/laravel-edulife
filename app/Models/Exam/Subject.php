<?php

namespace App\Models\Exam;

use App\Models\Direction;
use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Exam subject: math or physics.
 */
class Subject extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const MATH = 'math';
    public const PHYSICS = 'physics';

    protected $table = 'exam_subjects';

    protected $fillable = [
        'code',
        'name_uz',
        'name_ru',
        'direction_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'subject_id');
    }

    /** Level 1 nodes of the topic tree (bo'limlar). */
    public function sections(): HasMany
    {
        return $this->topics()->whereNull('parent_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'subject_id');
    }

    public function blueprints(): HasMany
    {
        return $this->hasMany(ExamBlueprint::class, 'subject_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'subject_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
