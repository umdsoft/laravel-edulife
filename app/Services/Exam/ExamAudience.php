<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentExamProfile;
use App\Models\User;
use Illuminate\Support\LazyCollection;

/**
 * Who sits a session: active students whose exam profile lists the
 * session's subject; a private (retake) session only its one student.
 */
class ExamAudience
{
    /** @return LazyCollection<int, User> */
    public function students(ExamSession $session): LazyCollection
    {
        $query = User::query()->where('role', 'student')->where('status', 'active');

        if ($session->restricted_to_user_id !== null) {
            return $query->whereKey($session->restricted_to_user_id)->lazyById(200);
        }

        $code = $session->subject()->value('code');

        return $query
            ->whereIn('id', StudentExamProfile::query()->whereJsonContains('subjects', $code)->select('user_id'))
            ->lazyById(200);
    }

    public function count(ExamSession $session): int
    {
        $query = User::query()->where('role', 'student')->where('status', 'active');

        if ($session->restricted_to_user_id !== null) {
            return $query->whereKey($session->restricted_to_user_id)->count();
        }

        $code = $session->subject()->value('code');

        return $query
            ->whereIn('id', StudentExamProfile::query()->whereJsonContains('subjects', $code)->select('user_id'))
            ->count();
    }
}
