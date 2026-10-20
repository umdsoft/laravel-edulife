<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sunday report a guardian received about one child for one exam week
 * (exam_guardian_reports). payload = App\Services\Exam\ReportBuilder output.
 */
class GuardianReport extends Model
{
    use HasUuids;

    protected $table = 'exam_guardian_reports';

    protected $fillable = [
        'guardian_student_id',
        'guardian_id',
        'student_id',
        'week_start',
        'payload',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'payload' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(GuardianStudent::class, 'guardian_student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
