<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How often a question was answered in battles and how often correctly:
 * the empirical difficulty behind its battle value (docs/battle/SCORING.md).
 */
class BattleQuestionStat extends Model
{
    use HasUuids;

    protected $fillable = ['question_id', 'attempts', 'correct'];

    protected $casts = [
        'attempts' => 'integer',
        'correct' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
