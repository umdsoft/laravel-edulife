<?php

namespace App\Services\Telegram;

use App\Enums\Exam\GuardianRelation;
use App\Enums\Exam\StudentGoal;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Exam\GuardianLinkService;
use App\Services\Exam\ReferralService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The one place that turns a Telegram identity into an account (TW: WebView-first).
 * Used by the WebView registration (MiniAppRegistrationController) and the chat
 * fallback (BotRegistration), so both follow the same rules:
 *
 *  - a phone that already belongs to an account links that account ONLY when Telegram
 *    verified the phone (contact shared by the Telegram user themself) — a typed phone
 *    never takes an existing account over;
 *  - by phone only student accounts (student path) and guardian accounts (guardian path)
 *    are linked; teacher / admin accounts never are (they link from the site, /telegram/link);
 *  - an account already linked to another Telegram account is not moved;
 *  - the exam profile is written like the website onboarding (OnboardingController):
 *    the first consent time is kept, a graduate has no grade;
 *  - the invite code from /start ref_<code> or startapp=ref_<code> goes to ReferralService.
 *
 * Returns the user, or a lang key (exam_bot.*) explaining the refusal.
 */
class TelegramRegistration
{
    public function __construct(
        private readonly ReferralService $referrals,
        private readonly GuardianLinkService $guardianLinks,
    ) {}

    /**
     * @param  array{phone: string, phone_verified: bool, filled_by: string, grade: string, subject: string, goal: string,
     *               parent_phone: ?string, school_name?: ?string, show_name_publicly?: bool, first_name?: ?string,
     *               last_name?: ?string, ref?: ?string}  $a
     */
    public function student(TelegramAccount $account, array $a): User|string
    {
        $parent = $a['filled_by'] === 'parent';

        $user = DB::transaction(function () use ($account, $a, $parent) {
            $user = $this->existing($account, $a['phone'], (bool) $a['phone_verified'], 'student');
            if (is_string($user)) {
                return $user;
            }
            $user ??= $this->createUser($account, $a, 'student');

            $profile = StudentExamProfile::query()->firstOrNew(['user_id' => $user->id]);
            $showName = (bool) ($a['show_name_publicly'] ?? false);
            $profile->fill([
                'grade' => $a['grade'] === 'graduate' ? null : (int) $a['grade'],
                'is_graduate' => $a['grade'] === 'graduate',
                'subjects' => $a['subject'] === 'both' ? [Subject::MATH, Subject::PHYSICS] : [$a['subject']],
                'goal' => StudentGoal::from($a['goal']),
                'filled_by' => $parent ? 'parent' : 'student',
                'parent_phone' => $parent ? $a['phone'] : GuardianLinkService::normalizePhone($a['parent_phone'] ?? null),
                'parent_consent_at' => $profile->parent_consent_at ?? now(),
                'parent_consent_source' => $profile->parent_consent_source ?? ($parent ? 'telegram_parent' : 'telegram_student'),
                'onboarded_at' => $profile->onboarded_at ?? now(),
            ]);
            if (array_key_exists('school_name', $a)) {
                $profile->school_name = trim((string) $a['school_name']) ?: null;
            }
            if (array_key_exists('show_name_publicly', $a)) {
                $profile->show_name_publicly = $showName;
                $profile->public_name_consent_at = $showName ? ($profile->public_name_consent_at ?? now()) : null;
            }
            $profile->save();

            $this->link($account, $user);

            return $user;
        });

        return $this->finish($account, $user, $a['ref'] ?? null, 'student');
    }

    /**
     * A parent account (role guardian) and, when the child's phone is given, the link request
     * that the teacher approves (GuardianLinkService — the same queue as the website).
     *
     * @param  array{phone: string, phone_verified: bool, first_name?: ?string, last_name?: ?string,
     *               child_phone?: ?string, relation?: ?string, ref?: ?string}  $a
     *
     * @throws \Illuminate\Validation\ValidationException the child was not found (nothing is created)
     */
    public function guardian(TelegramAccount $account, array $a): User|string
    {
        $user = DB::transaction(function () use ($account, $a) {
            $user = $this->existing($account, $a['phone'], (bool) $a['phone_verified'], 'guardian');
            if (is_string($user)) {
                return $user;
            }
            $user ??= $this->createUser($account, $a, 'guardian');

            if (! empty($a['child_phone'])) {
                $link = $this->guardianLinks->request($user, (string) $a['child_phone'], GuardianRelation::tryFrom((string) ($a['relation'] ?? '')) ?? GuardianRelation::Guardian);
                $link->forceFill(['consent_source' => 'telegram'])->save();
            }

            $this->link($account, $user);

            return $user;
        });

        return $this->finish($account, $user, $a['ref'] ?? null, 'guardian');
    }

    // --- auth: Telegram login (agent L, docs/auth/TELEGRAM_LOGIN.md) ---------------------

    /**
     * Website login through the bot: a phone Telegram verified (shared contact) links the
     * student / guardian account that owns it. null = no account has this phone.
     * Staff are never linked by phone; an account linked to another Telegram is not moved.
     */
    public function linkByVerifiedPhone(TelegramAccount $account, string $phone): User|string|null
    {
        return DB::transaction(function () use ($account, $phone) {
            $user = User::query()->where('phone', $phone)->lockForUpdate()->first();
            if ($user === null) {
                return null;
            }
            if (! in_array($user->role, ['student', 'guardian'], true)) {
                return 'login.staff';
            }
            if ($user->status === 'blocked') {
                return 'login.blocked';
            }
            $existing = $this->existing($account, $phone, true, $user->role);
            if (is_string($existing)) {
                return $existing;
            }
            if ($user->phone_verified_at === null) {
                $user->forceFill(['phone_verified_at' => now()])->save();
            }
            $this->link($account, $user);
            Log::info('telegram: account linked by a verified phone (site login)', ['account' => $account->id, 'user' => $user->id]);

            return $user;
        });
    }

    /**
     * Website login, chat fallback (no Mini App): a new verified phone gets an account without
     * the exam profile — the website onboarding (EnsureExamOnboarded) asks grade / subject / consent.
     */
    public function bare(TelegramAccount $account, string $phone, string $role): User|string
    {
        $role = $role === 'guardian' ? 'guardian' : 'student';
        $user = DB::transaction(function () use ($account, $phone, $role) {
            $user = $this->existing($account, $phone, true, $role);
            if (is_string($user)) {
                return $user;
            }
            $user ??= $this->createUser($account, ['phone' => $phone, 'phone_verified' => true], $role);
            $this->link($account, $user);

            return $user;
        });

        return $this->finish($account, $user, null, $role);
    }

    // --- end auth: Telegram login ---------------------------------------------------------

    /**
     * The account that owns $phone (null = none), or a refusal key.
     */
    private function existing(TelegramAccount $account, string $phone, bool $verified, string $role): User|string|null
    {
        $user = User::query()->where('phone', $phone)->lockForUpdate()->first();
        if ($user === null) {
            return null;
        }
        if (! $verified) {
            return 'reg.phone_taken';
        }
        if ($user->role !== $role) {
            return 'reg.role_conflict';
        }
        $other = TelegramAccount::query()->where('user_id', $user->id)->where('id', '!=', $account->id)->exists();

        return $other ? 'reg.already_linked' : $user;
    }

    private function createUser(TelegramAccount $account, array $a, string $role): User
    {
        $first = trim((string) ($a['first_name'] ?? '')) ?: (string) ($account->first_name ?: ($role === 'guardian' ? 'Ota-ona' : "O'quvchi"));
        $last = trim((string) ($a['last_name'] ?? '')) ?: (string) ($account->last_name ?? '');

        $user = User::create([
            'first_name' => mb_substr($first, 0, 100),
            'last_name' => mb_substr($last, 0, 100),
            'phone' => $a['phone'],
            'password' => Hash::make(Str::random(40)),
            'role' => $role,
            'status' => 'active',
        ] + ($role === 'student' ? [
            'xp_total' => 0,
            'level' => 1,
            'coin_balance' => 0,
            'elo_rating' => 1000,
            'battles_won' => 0,
            'battles_total' => 0,
            'streak_current' => 0,
            'streak_best' => 0,
        ] : []));
        // Telegram verified the shared contact; a typed phone stays unverified
        if ($a['phone_verified']) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }

    private function link(TelegramAccount $account, User $user): void
    {
        $account->forceFill(['user_id' => $user->id, 'linked_at' => now()])->save();
    }

    private function finish(TelegramAccount $account, User|string $user, ?string $ref, string $kind): User|string
    {
        if (is_string($user)) {
            Log::info('telegram: registration refused', ['account' => $account->id, 'reason' => $user]);

            return $user;
        }

        $code = $ref ?: (string) $account->start_payload;
        if (str_starts_with(strtolower($code), 'ref_')) {
            try {
                $this->referrals->recordJoin($user, $code, 'telegram');
            } catch (\Throwable $e) {
                Log::warning('telegram: referral not recorded', ['account' => $account->id, 'error' => class_basename($e)]);
            }
        }
        Log::info('telegram: registration completed', ['account' => $account->id, 'user' => $user->id, 'kind' => $kind]);

        return $user;
    }

    /** The guardian's pending / approved children count (for the success screen). */
    public static function hasChildRequest(User $guardian): bool
    {
        return GuardianStudent::query()->where('guardian_id', $guardian->id)->exists();
    }
}
