<?php

namespace App\Policies;

use App\Models\TestAttempt;
use App\Models\User;

class TestAttemptPolicy
{
    public function view(User $user, TestAttempt $attempt): bool
    {
        return (string) $attempt->user_id === (string) $user->id;
    }

    public function update(User $user, TestAttempt $attempt): bool
    {
        return (string) $attempt->user_id === (string) $user->id;
    }
}
