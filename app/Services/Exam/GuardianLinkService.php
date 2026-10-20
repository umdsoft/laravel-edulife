<?php

namespace App\Services\Exam;

use App\Enums\Exam\GuardianRelation;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Guardian (ota-ona) <-> child links.
 *
 * There is no SMS provider in the project (registration auto-verifies the
 * phone, SmsLog has no sender), so a phone can not be proven by a code.
 * Conservative flow:
 *   1. the parent (role guardian) enters the child's phone and gives consent
 *      -> a pending guardian_student row (consent_at set, verified_at NULL);
 *   2. the teacher/admin approves or rejects it (teacher.exam.guardian-links);
 *      the page shows whether the parent's phone equals the parent phone the
 *      student entered at onboarding;
 *   3. only approved links (verified_at set, not rejected) give access to the
 *      child's data and receive the Sunday report.
 */
class GuardianLinkService
{
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        return preg_match('/^998\d{9}$/', $digits) ? '+'.$digits : null;
    }

    public function request(User $guardian, string $childPhone, GuardianRelation $relation): GuardianStudent
    {
        $phone = self::normalizePhone($childPhone);
        $child = $phone ? User::query()->where('phone', $phone)->where('role', 'student')->first() : null;
        if (! $child) {
            throw ValidationException::withMessages([
                'phone' => 'Bu telefon raqami bilan ro\'yxatdan o\'tgan o\'quvchi topilmadi. Raqamni tekshiring.',
            ]);
        }
        if ($child->id === $guardian->id) {
            throw ValidationException::withMessages(['phone' => 'O\'zingizni farzand sifatida bog\'lay olmaysiz.']);
        }

        $link = GuardianStudent::query()->firstOrNew(['guardian_id' => $guardian->id, 'student_id' => $child->id]);
        if ($link->exists && $link->isVerified()) {
            throw ValidationException::withMessages(['phone' => 'Bu farzand allaqachon bog\'langan.']);
        }

        // a new or a re-sent (previously rejected) request goes back to the queue
        $link->fill([
            'relation' => $relation,
            'consent_at' => now(),
            'consent_source' => 'web',
            'receives_reports' => true,
            'verified_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'reject_reason' => null,
        ])->save();

        Log::info('exam.guardian.link_requested', ['link' => $link->id]);

        return $link;
    }

    public function approve(GuardianStudent $link, User $approver): GuardianStudent
    {
        $link->forceFill([
            'verified_at' => now(),
            'approved_by' => $approver->id,
            'rejected_at' => null,
            'reject_reason' => null,
        ])->save();

        Log::info('exam.guardian.link_approved', ['link' => $link->id, 'by' => $approver->id]);

        return $link;
    }

    public function reject(GuardianStudent $link, User $approver, ?string $reason = null): GuardianStudent
    {
        $link->forceFill([
            'verified_at' => null,
            'approved_by' => $approver->id,
            'rejected_at' => now(),
            'reject_reason' => $reason ? mb_substr($reason, 0, 255) : null,
        ])->save();

        Log::info('exam.guardian.link_rejected', ['link' => $link->id, 'by' => $approver->id]);

        return $link;
    }

    /** The parent phone the student gave at onboarding equals the guardian's own phone. */
    public function phoneMatches(GuardianStudent $link, ?User $guardian = null): bool
    {
        $guardianPhone = self::normalizePhone(($guardian ?? User::query()->find($link->guardian_id))?->phone);
        $parentPhone = self::normalizePhone(StudentExamProfile::query()->where('user_id', $link->student_id)->value('parent_phone'));

        return $guardianPhone !== null && $guardianPhone === $parentPhone;
    }

    /** Approved children of a guardian (ids). */
    public function childIds(User $guardian): array
    {
        return GuardianStudent::query()->verified()->where('guardian_id', $guardian->id)->pluck('student_id')->all();
    }
}
