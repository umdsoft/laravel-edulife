<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return (string) $review->user_id === (string) $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return (string) $review->user_id === (string) $user->id;
    }

    /**
     * Only the teacher who owns the reviewed course may reply.
     */
    public function reply(User $user, Review $review): bool
    {
        return $user->role === 'teacher'
            && (string) $review->course?->teacher_id === (string) $user->id;
    }
}
