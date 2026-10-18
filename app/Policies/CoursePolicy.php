<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Teachers may only manage their own courses.
     */
    public function view(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    private function owns(User $user, Course $course): bool
    {
        return $course->exists
            && $user->role === 'teacher'
            && (string) $course->teacher_id === (string) $user->id;
    }
}
