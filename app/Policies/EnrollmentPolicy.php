<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * The student themself, or the teacher who owns the course.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ((string) $enrollment->user_id === (string) $user->id) {
            return true;
        }

        return $user->role === 'teacher'
            && (string) $enrollment->course?->teacher_id === (string) $user->id;
    }
}
