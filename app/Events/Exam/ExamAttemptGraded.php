<?php

namespace App\Events\Exam;

use App\Models\Exam\ExamAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An exam attempt was graded: exam_attempts (score, max_score,
 * score_percent, status = graded), exam_answers, the student's topic
 * mastery and, for weekly sessions, exam_student_weekly_results are stored.
 *
 * $regraded is true when `exam:regrade` re-graded an already graded attempt
 * (listeners that message the student usually skip those).
 */
class ExamAttemptGraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ExamAttempt $attempt,
        public bool $regraded = false,
    ) {}
}
