<?php

namespace App\Models\Exam;

use App\Models\Course;
use App\Models\LabExperiment;
use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Node of the topic tree: subject -> section (level 1) -> topic (level 2+).
 */
class Topic extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const LEVEL_SECTION = 1;
    public const LEVEL_TOPIC = 2;

    protected $table = 'exam_topics';

    protected $fillable = [
        'subject_id',
        'parent_id',
        'code',
        'name_uz',
        'name_ru',
        'description',
        'level',
        'grade_from',
        'grade_to',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'grade_from' => 'integer',
            'grade_to' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Keep level and subject consistent with the parent.
        static::saving(function (Topic $topic) {
            if ($topic->parent_id === null) {
                $topic->level = self::LEVEL_SECTION;

                return;
            }

            $parent = self::withTrashed()->find($topic->parent_id);
            if ($parent === null) {
                throw new \InvalidArgumentException('Parent topic not found.');
            }
            if ($topic->subject_id !== null && $parent->subject_id !== $topic->subject_id) {
                throw new \InvalidArgumentException('A topic must belong to the subject of its parent.');
            }
            if ($topic->exists && $topic->isDirty('parent_id')
                && in_array($parent->id, $topic->descendantAndSelfIds(), true)) {
                throw new \InvalidArgumentException('A topic can not be moved under its own subtree.');
            }
            $topic->subject_id = $parent->subject_id;
            $topic->level = $parent->level + 1;
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'topic_id');
    }

    public function masteries(): HasMany
    {
        return $this->hasMany(StudentTopicMastery::class, 'topic_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'exam_topic_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'exam_topic_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class, 'exam_topic_id');
    }

    public function labExperiments(): HasMany
    {
        return $this->hasMany(LabExperiment::class, 'exam_topic_id');
    }

    // ==================== SCOPES ====================

    public function scopeSections(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForGrade(Builder $query, int $grade): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('grade_from')->orWhere('grade_from', '<=', $grade))
            ->where(fn ($q) => $q->whereNull('grade_to')->orWhere('grade_to', '>=', $grade));
    }

    // ==================== TREE HELPERS ====================

    public function isSection(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Ids of this topic and all of its descendants (breadth first, one query
     * per tree level; the tree is 2-3 levels deep).
     *
     * @return list<string>
     */
    public function descendantAndSelfIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($frontier, $ids));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /** Level 1 ancestor (the section) of this topic. */
    public function section(): self
    {
        $node = $this;
        while ($node->parent_id !== null) {
            $node = self::withTrashed()->findOrFail($node->parent_id);
        }

        return $node;
    }
}
