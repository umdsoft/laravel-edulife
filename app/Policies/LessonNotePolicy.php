<?php

namespace App\Policies;

use App\Models\LessonNote;
use App\Models\User;

class LessonNotePolicy
{
    public function update(User $user, LessonNote $note): bool
    {
        return (string) $note->user_id === (string) $user->id;
    }

    public function delete(User $user, LessonNote $note): bool
    {
        return (string) $note->user_id === (string) $user->id;
    }
}
