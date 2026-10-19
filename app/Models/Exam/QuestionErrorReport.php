<?php

namespace App\Models\Exam;

use App\Enums\Exam\ErrorReportStatus;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Savolda xato bor" report. Creating one increments
 * question_bank.error_reports_count (deleting decrements it).
 */
class QuestionErrorReport extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'question_error_reports';

    protected $fillable = [
        'question_id',
        'user_id',
        'attempt_id',
        'message',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ErrorReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (QuestionErrorReport $report) {
            QuestionBank::withTrashed()->whereKey($report->question_id)->increment('error_reports_count');
        });
        static::deleted(function (QuestionErrorReport $report) {
            QuestionBank::withTrashed()->whereKey($report->question_id)
                ->where('error_reports_count', '>', 0)
                ->decrement('error_reports_count');
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ErrorReportStatus::Open->value);
    }
}
