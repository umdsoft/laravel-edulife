<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Teacher's source material for AI drafts (stage 4). The AI only restates
 * problems from these texts; it does not invent them (spec section 1).
 */
class ExamSourceDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'exam_source_documents';

    protected $fillable = [
        'subject_id',
        'topic_id',
        'title',
        'source_ref',
        'disk',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'content_text',
        'char_count',
        'is_active',
        'uploaded_by',
    ];

    protected $hidden = ['content_text'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'char_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExamSourceDocument $doc) {
            if ($doc->isDirty('content_text')) {
                $doc->char_count = mb_strlen((string) $doc->content_text, 'UTF-8');
            }
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Documents usable for a topic: attached to the topic, to one of its
     * ancestors or descendants, or to the whole subject (topic_id NULL).
     */
    public function scopeUsableFor(Builder $query, Subject $subject, ?Topic $topic): Builder
    {
        $query->where('subject_id', $subject->id);
        if ($topic === null) {
            return $query;
        }

        $ids = $topic->descendantAndSelfIds();
        $parent = $topic->parent_id;
        $guard = 0;
        while ($parent !== null && $guard++ < 10) {
            $ids[] = $parent;
            $parent = Topic::query()->whereKey($parent)->value('parent_id');
        }

        return $query->where(fn (Builder $q) => $q->whereNull('topic_id')->orWhereIn('topic_id', $ids));
    }
}
