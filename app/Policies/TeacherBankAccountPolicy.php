<?php

namespace App\Policies;

use App\Models\TeacherBankAccount;
use App\Models\User;

class TeacherBankAccountPolicy
{
    public function update(User $user, TeacherBankAccount $bankAccount): bool
    {
        return (string) $bankAccount->teacher_id === (string) $user->id;
    }

    public function delete(User $user, TeacherBankAccount $bankAccount): bool
    {
        return (string) $bankAccount->teacher_id === (string) $user->id;
    }
}
