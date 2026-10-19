<?php

namespace App\Models\Exam;

use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Personal variant of one student in one session (unique session + user).
 *
 * question_ids  ordered list of question_bank ids
 * option_orders {question_id: [option_id, ...]} display order of options
 * rule_map      {question_id: blueprint_rule_id} which rule picked it
 * seed          RNG seed used by the generator (audit / reproduction)
 */
class ExamVariant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_variants';

    protected $fillable = [
        'session_id',
        'user_id',
        'seed',
        'question_ids',
        'option_orders',
        'rule_map',
        'used_fallback',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'question_ids' => 'array',
            'option_orders' => 'array',
            'rule_map' => 'array',
            'used_fallback' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): HasOne
    {
        return $this->hasOne(ExamAttempt::class, 'variant_id');
    }

    /** Questions in variant order. */
    public function orderedQuestions(): Collection
    {
        $ids = $this->question_ids ?? [];
        $byId = QuestionBank::query()->whereIn('id', $ids)->get()->keyBy('id');

        return new Collection(array_values(array_filter(array_map(fn ($id) => $byId->get($id), $ids))));
    }
}
