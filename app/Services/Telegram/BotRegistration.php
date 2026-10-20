<?php

namespace App\Services\Telegram;

use App\Enums\Exam\StudentGoal;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\Exam\TelegramBotState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The bot's 5-question registration (spec section 8):
 *   1 role (student / parent)  2 grade  3 subject (math / physics / both)
 *   4 goal  5 phone (Telegram contact) + the parent's phone (student only)
 *   + parent consent.
 * Completion creates (or links by phone) a student account and its
 * StudentExamProfile with the consent stored (parent_consent_at).
 */
class BotRegistration
{
    public const STEP_ROLE = 'role';
    public const STEP_GRADE = 'grade';
    public const STEP_SUBJECT = 'subject';
    public const STEP_GOAL = 'goal';
    public const STEP_CONTACT = 'contact';
    public const STEP_PARENT_PHONE = 'parent_phone';
    public const STEP_CONSENT = 'consent';
    public const STEP_DONE = 'done';

    private const CHOICES = [
        self::STEP_ROLE => ['student', 'parent'],
        self::STEP_GRADE => ['9', '10', '11', 'graduate'],
        self::STEP_SUBJECT => ['math', 'physics', 'both'],
        self::STEP_GOAL => ['otm_grant', 'milliy_sertifikat', 'sat'],
        self::STEP_CONSENT => ['yes', 'no'],
    ];

    public function __construct(private readonly TelegramMessenger $messenger) {}

    public function state(TelegramAccount $account): ?TelegramBotState
    {
        return TelegramBotState::query()->where('telegram_account_id', $account->id)->first();
    }

    public function inProgress(TelegramAccount $account): bool
    {
        $state = $this->state($account);

        return $state !== null && $state->completed_at === null && $state->step !== null;
    }

    public function begin(TelegramAccount $account): void
    {
        $state = TelegramBotState::query()->firstOrNew(['telegram_account_id' => $account->id]);
        $state->fill(['flow' => TelegramBotState::FLOW_REGISTRATION, 'step' => self::STEP_ROLE, 'data' => [], 'completed_at' => null])->save();
        $this->ask($account, $state);
    }

    /** A button press "reg:<step>:<value>". */
    public function answer(TelegramAccount $account, string $step, string $value): void
    {
        $state = $this->state($account);
        if ($state === null || $state->completed_at !== null) {
            $this->begin($account);

            return;
        }
        // A stale button from an earlier question: repeat the current one.
        if ($state->step !== $step || ! in_array($value, self::CHOICES[$step] ?? [], true)) {
            $this->ask($account, $state);

            return;
        }

        if ($step === self::STEP_CONSENT) {
            if ($value !== 'yes') {
                $this->messenger->send($account, BotText::get('reg.consent_required'), ['remove_keyboard' => true]);
                $state->fill(['step' => null])->save();

                return;
            }
            $state->remember('consent_at', now()->toIso8601String());
            $state->save();
            $this->complete($account, $state);

            return;
        }

        $state->remember($step, $value);
        $state->step = match ($step) {
            self::STEP_ROLE => self::STEP_GRADE,
            self::STEP_GRADE => self::STEP_SUBJECT,
            self::STEP_SUBJECT => self::STEP_GOAL,
            self::STEP_GOAL => self::STEP_CONTACT,
        };
        $state->save();
        $this->ask($account, $state);
    }

    /** message.contact at step 5. */
    public function contact(TelegramAccount $account, array $contact): void
    {
        $state = $this->state($account);
        if ($state === null || $state->step !== self::STEP_CONTACT) {
            $this->messenger->send($account, BotText::get('reg.use_buttons'));

            return;
        }
        // Only the user's own number: Telegram has verified it belongs to them.
        if ((int) ($contact['user_id'] ?? 0) !== (int) $account->telegram_id) {
            $this->messenger->send($account, BotText::get('reg.contact_not_own'));

            return;
        }

        $phone = self::normalizePhone((string) ($contact['phone_number'] ?? ''));
        if ($phone === null) {
            $this->messenger->send($account, BotText::get('reg.phone_uz_only'));

            return;
        }

        $state->remember('phone', $phone);
        if ($state->answer('role') === 'parent') {
            $state->remember('parent_phone', $phone);
            $state->step = self::STEP_CONSENT;
        } else {
            $state->step = self::STEP_PARENT_PHONE;
        }
        $state->save();
        $this->ask($account, $state);
    }

    /** Free text at the parent-phone step. Returns false when the text was not for us. */
    public function text(TelegramAccount $account, string $text): bool
    {
        $state = $this->state($account);
        if ($state === null || $state->completed_at !== null || $state->step === null) {
            return false;
        }
        if ($state->step !== self::STEP_PARENT_PHONE) {
            $this->ask($account, $state);

            return true;
        }

        $phone = self::normalizePhone($text);
        if ($phone === null) {
            $this->messenger->send($account, BotText::get('reg.phone_invalid'));

            return true;
        }
        $state->remember('parent_phone', $phone);
        $state->step = self::STEP_CONSENT;
        $state->save();
        $this->ask($account, $state);

        return true;
    }

    /** +998XXXXXXXXX or null. Accepts "998901234567", "+998 90 123-45-67", "901234567". */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        return preg_match('/^998\d{9}$/', $digits) ? '+'.$digits : null;
    }

    // ------------------------------------------------------------------

    private function ask(TelegramAccount $account, TelegramBotState $state): void
    {
        $btn = fn (string $label, string $value) => ['text' => BotText::button($label), 'callback_data' => 'reg:'.$state->step.':'.$value];
        $parent = $state->answer('role') === 'parent';

        [$text, $markup] = match ($state->step) {
            self::STEP_ROLE => [BotText::get('reg.q_role'), BotText::inline([[$btn('student', 'student'), $btn('parent', 'parent')]])],
            self::STEP_GRADE => [BotText::get('reg.q_grade'), BotText::inline([
                [$btn('grade_9', '9'), $btn('grade_10', '10'), $btn('grade_11', '11')],
                [$btn('graduate', 'graduate')],
            ])],
            self::STEP_SUBJECT => [BotText::get('reg.q_subject'), BotText::inline([[$btn('math', 'math'), $btn('physics', 'physics')], [$btn('both', 'both')]])],
            self::STEP_GOAL => [BotText::get('reg.q_goal'), BotText::inline([
                [$btn('goal_otm_grant', 'otm_grant'), $btn('goal_milliy_sertifikat', 'milliy_sertifikat')],
                [$btn('goal_sat', 'sat')],
            ])],
            self::STEP_CONTACT => [BotText::get($parent ? 'reg.q_contact_parent' : 'reg.q_contact_student'), [
                'keyboard' => [[['text' => BotText::button('share_phone'), 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]],
            self::STEP_PARENT_PHONE => [BotText::get('reg.q_parent_phone'), ['remove_keyboard' => true]],
            self::STEP_CONSENT => [BotText::get($parent ? 'reg.q_consent_parent' : 'reg.q_consent_student'), BotText::inline([[
                $btn('consent_yes', 'yes'), $btn('consent_no', 'no'),
            ]])],
            default => [BotText::get('help'), null],
        };

        $this->messenger->send($account, $text, $markup);
    }

    private function complete(TelegramAccount $account, TelegramBotState $state): void
    {
        $phone = (string) $state->answer('phone');
        $parent = $state->answer('role') === 'parent';

        try {
            $user = DB::transaction(function () use ($account, $state, $phone, $parent) {
                $user = User::query()->where('phone', $phone)->lockForUpdate()->first();
                if ($user !== null) {
                    if ($user->role !== 'student') {
                        return 'reg.role_conflict';
                    }
                    $other = TelegramAccount::query()->where('user_id', $user->id)->where('id', '!=', $account->id)->exists();
                    if ($other) {
                        return 'reg.already_linked';
                    }
                } else {
                    $user = User::create([
                        'first_name' => mb_substr((string) ($account->first_name ?: "O'quvchi"), 0, 100),
                        'last_name' => mb_substr((string) ($account->last_name ?? ''), 0, 100),
                        'phone' => $phone,
                        'password' => Hash::make(Str::random(40)),
                        'role' => 'student',
                        'status' => 'active',
                        'xp_total' => 0,
                        'level' => 1,
                        'coin_balance' => 0,
                        'elo_rating' => 1000,
                        'battles_won' => 0,
                        'battles_total' => 0,
                        'streak_current' => 0,
                        'streak_best' => 0,
                    ]);
                    // Telegram verified the contact; same as the web registration
                    $user->forceFill(['phone_verified_at' => now()])->save();
                }

                $grade = (string) $state->answer('grade');
                $subject = (string) $state->answer('subject');
                StudentExamProfile::query()->updateOrCreate(['user_id' => $user->id], [
                    'grade' => $grade === 'graduate' ? 11 : (int) $grade,
                    'is_graduate' => $grade === 'graduate',
                    'subjects' => $subject === 'both' ? [Subject::MATH, Subject::PHYSICS] : [$subject],
                    'goal' => StudentGoal::from((string) $state->answer('goal')),
                    'filled_by' => $parent ? 'parent' : 'student',
                    'parent_phone' => $state->answer('parent_phone'),
                    'parent_consent_at' => $state->answer('consent_at') ? now() : null,
                    'parent_consent_source' => $parent ? 'telegram_parent' : 'telegram_student',
                    'onboarded_at' => now(),
                ]);

                $account->forceFill(['user_id' => $user->id, 'linked_at' => now()])->save();
                $state->fill(['step' => self::STEP_DONE, 'completed_at' => now()])->save();

                return $user;
            });
        } catch (\Throwable $e) {
            Log::error('telegram: registration failed', ['account' => $account->id, 'error' => class_basename($e)]);
            $this->messenger->send($account, BotText::get('reg.failed'), ['remove_keyboard' => true]);

            return;
        }

        if (is_string($user)) {
            $state->fill(['step' => null])->save();
            $this->messenger->send($account, BotText::get($user), ['remove_keyboard' => true]);

            return;
        }

        $this->recordReferral($account, $user);
        Log::info('telegram: registration completed', ['account' => $account->id, 'user' => $user->id]);

        $this->messenger->send($account, BotText::get($parent ? 'reg.done_parent' : 'reg.done_student'), BotText::inline([
            [BotText::appButton('placement', 'exam')],
        ]));
    }

    /** /start ref_<code>: hand the stored code to the referral funnel (stage 7). */
    private function recordReferral(TelegramAccount $account, User $user): void
    {
        $payload = (string) $account->start_payload;
        $service = 'App\\Services\\Exam\\ReferralService';
        if (! str_starts_with(strtolower($payload), 'ref_') || ! class_exists($service) || ! method_exists($service, 'recordJoin')) {
            return;
        }

        try {
            app($service)->recordJoin($user, $payload, 'telegram');
        } catch (\Throwable $e) {
            Log::warning('telegram: referral not recorded', ['account' => $account->id, 'error' => class_basename($e)]);
        }
    }
}
