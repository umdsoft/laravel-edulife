<?php

namespace App\Http\Controllers\Telegram;

use App\Enums\Exam\GuardianRelation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Exam\OnboardingController;
use App\Models\Exam\Subject;
use App\Models\Exam\TelegramAccount;
use App\Models\User;
use App\Services\Exam\GuardianLinkService;
use App\Services\Telegram\BotText;
use App\Services\Telegram\InvalidInitData;
use App\Services\Telegram\TelegramInitData;
use App\Services\Telegram\TelegramRegistration;
use App\Services\Telegram\TelegramStartParam;
use App\Services\Telegram\WebviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TW (Telegram WebView-first): registration inside the Mini App.
 *
 * GET  /telegram/app/register   the registration screen (Inertia page Telegram/Register)
 * POST /telegram/app/contact    is the Mini App user's phone verified? (requestContact response or
 *                               the contact message the bot received) -> masked phone
 * POST /telegram/app/register   create / link the account (TelegramRegistration — the same rules as
 *                               the chat registration), log in, say where to go next
 *
 * The POST endpoints are authenticated by the initData HMAC on every call (no trust in the session),
 * accept JSON only and are CSRF-exempt for the same reason as /telegram/app/auth.
 *
 * Phone: Telegram.WebApp.requestContact() (Bot API 6.9) — its signed response is verified here
 * (TelegramInitData::verifyContact: same HMAC as initData, contact.user_id = the initData user);
 * Telegram also delivers the shared contact to the bot as a message, which BotUpdateHandler keeps
 * for 15 minutes (cache "telegram-contact:<id>"). A typed phone is accepted for a NEW account only
 * and stays unverified — it never links an existing account.
 */
class MiniAppRegistrationController extends Controller
{
    public const CONTACT_CACHE = 'telegram-contact:';

    public function show(Request $request): Response
    {
        $to = (string) $request->query('to', '');

        return Inertia::render('Telegram/Register', [
            'to' => TelegramStartParam::isValid($to) ? $to : null,
            'subjects' => Subject::query()->active()->whereIn('code', [Subject::MATH, Subject::PHYSICS])->orderBy('code')->get(['code', 'name_uz'])
                ->map(fn ($s) => ['code' => $s->code, 'name' => $s->name_uz])->values(),
            'relations' => collect(GuardianRelation::cases())->map(fn ($r) => ['value' => $r->value, 'label' => \App\Http\Controllers\Guardian\DashboardController::relationLabel($r)])->values(),
            'bot' => ltrim((string) config('exam.telegram.bot_username'), '@') ?: null,
            'siteLink' => rtrim((string) config('app.url'), '/').'/login',
        ]);
    }

    public function contact(Request $request, TelegramInitData $verifier): JsonResponse
    {
        $verified = $this->verify($request, $verifier);
        if ($verified instanceof JsonResponse) {
            return $verified;
        }
        $telegramId = (int) $verified['user']['id'];

        $phone = $this->verifiedPhone($request, $verifier, $telegramId);
        if ($phone instanceof JsonResponse) {
            return $phone;
        }

        return response()->json(['verified' => $phone !== null, 'phone' => $phone !== null ? self::mask($phone) : null]);
    }

    public function store(Request $request, TelegramInitData $verifier, TelegramRegistration $registration, TelegramStartParam $start): JsonResponse
    {
        $verified = $this->verify($request, $verifier);
        if ($verified instanceof JsonResponse) {
            return $verified;
        }
        $tg = $verified['user'];
        $account = $this->account($tg);

        // already registered (a second tap, or another device): just log in
        if ($account->user_id !== null) {
            $user = User::query()->find($account->user_id);
            if ($user === null || $user->status === 'blocked' || ! in_array($user->role, ['student', 'guardian'], true)) {
                return response()->json(['message' => BotText::get('app.staff')], 403);
            }

            return $this->loggedIn($request, $user, $start, $request->input('to'));
        }

        $student = fn () => in_array($request->input('kind'), ['student', 'parent_child'], true);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['student', 'parent_child', 'guardian'])],
            'to' => ['nullable', 'string', 'max:64'],
            'contact' => ['nullable', 'string', 'max:4096'],
            'phone' => ['nullable', 'string', 'max:20'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            // student / a parent registering the child (the same fields as the website onboarding)
            'grade' => [Rule::requiredIf($student), 'nullable', Rule::in(['9', '10', '11', 'graduate'])],
            'subject' => [Rule::requiredIf($student), 'nullable', Rule::in(['math', 'physics', 'both'])],
            'goal' => [Rule::requiredIf($student), 'nullable', Rule::in(OnboardingController::GOALS)],
            'school_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => [Rule::requiredIf(fn () => $request->input('kind') === 'student'), 'nullable', 'string', 'max:20', $this->phoneRule()],
            'parent_consent' => [Rule::requiredIf($student), Rule::when($student, ['accepted'])],
            'show_name_publicly' => ['nullable', 'boolean'],
            // a parent watching a child who has their own account
            'child_phone' => ['nullable', 'string', 'max:20', $this->phoneRule()],
            'relation' => ['nullable', Rule::in(GuardianRelation::values())],
            'consent' => [Rule::requiredIf(fn () => $request->input('kind') === 'guardian' && filled($request->input('child_phone'))), Rule::when(fn () => filled($request->input('child_phone')), ['accepted'])],
        ], [
            'kind.required' => 'Kim ro\'yxatdan o\'tayotganini tanlang.',
            'grade.required' => 'Sinfni tanlang.',
            'subject.required' => 'Fanni tanlang.',
            'goal.required' => 'Maqsadni tanlang.',
            'parent_phone.required' => 'Ota-onaning telefon raqamini kiriting.',
            'parent_consent.required' => 'Ota-onaning roziligisiz davom etib bo\'lmaydi.',
            'parent_consent.accepted' => 'Ota-onaning roziligisiz davom etib bo\'lmaydi.',
            'consent.required' => 'Farzandingiz ma\'lumotlarini qayta ishlashga rozilik bering.',
            'consent.accepted' => 'Farzandingiz ma\'lumotlarini qayta ishlashga rozilik bering.',
        ]);

        $phone = $this->verifiedPhone($request, $verifier, (int) $tg['id']);
        if ($phone instanceof JsonResponse) {
            return $phone;
        }
        $phoneVerified = $phone !== null;
        if ($phone === null) {
            $phone = GuardianLinkService::normalizePhone($data['phone'] ?? null);
            if ($phone === null) {
                throw ValidationException::withMessages(['phone' => blank($data['phone'] ?? null)
                    ? 'Telefon raqamingizni ulashing yoki yozing.'
                    : 'Telefon raqam +998XXXXXXXXX formatida bo\'lishi kerak.']);
            }
        }

        $ref = $this->refCode($data['to'] ?? null, $verified['start_param'] ?? null);
        $common = [
            'phone' => $phone,
            'phone_verified' => $phoneVerified,
            'first_name' => $data['first_name'] ?? ($tg['first_name'] ?? null),
            'last_name' => $data['last_name'] ?? ($tg['last_name'] ?? null),
            'ref' => $ref,
        ];

        try {
            $user = $data['kind'] === 'guardian'
                ? $registration->guardian($account, $common + [
                    'child_phone' => $data['child_phone'] ?? null,
                    'relation' => $data['relation'] ?? null,
                ])
                : $registration->student($account, $common + [
                    'filled_by' => $data['kind'] === 'parent_child' ? 'parent' : 'student',
                    'grade' => $data['grade'],
                    'subject' => $data['subject'],
                    'goal' => $data['goal'],
                    'parent_phone' => $data['parent_phone'] ?? null,
                    'school_name' => $data['school_name'] ?? null,
                    'show_name_publicly' => (bool) ($data['show_name_publicly'] ?? false),
                ]);
        } catch (ValidationException $e) {
            // GuardianLinkService reports the child's phone as "phone"
            throw ValidationException::withMessages(['child_phone' => $e->validator->errors()->first('phone') ?: $e->getMessage()]);
        }

        if (is_string($user)) {
            return response()->json([
                'message' => strip_tags(__('exam_bot.'.$user, ['url' => route('telegram.link')], 'uz')),
                'errors' => ['phone' => [strip_tags(__('exam_bot.'.$user, ['url' => route('telegram.link')], 'uz'))]],
                'reason' => $user,
            ], 422);
        }
        Cache::forget(self::CONTACT_CACHE.$tg['id']);

        $to = $data['to'] ?? null;
        if ($user->role === 'student' && (! TelegramStartParam::isValid($to) || in_array($to, ['exam'], true) || str_starts_with((string) $to, 'ref_'))) {
            // a new student goes straight to the free placement test of their first subject
            $subjects = (array) ($user->examProfile()->first()?->subjects ?? []);
            $to = isset($subjects[0]) ? 'placement_'.$subjects[0] : 'exam';
        }

        return $this->loggedIn($request, $user, $start, $to);
    }

    // ------------------------------------------------------------------

    /** @return array<string, mixed>|JsonResponse */
    private function verify(Request $request, TelegramInitData $verifier): array|JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => BotText::get('app.error')], 415);
        }
        $request->validate(['init_data' => ['required', 'string', 'max:8192']]);

        try {
            return $verifier->verify((string) $request->input('init_data'));
        } catch (InvalidInitData $e) {
            Log::info('telegram: registration initData rejected', ['reason' => $e->reason]);

            return response()->json(['message' => BotText::get('app.invalid'), 'reason' => $e->reason], 401);
        }
    }

    /**
     * The Telegram-verified phone of this Telegram user (+998…), null when there is none.
     * A requestContact response that does not verify is an error (no silent fallback).
     */
    private function verifiedPhone(Request $request, TelegramInitData $verifier, int $telegramId): string|null|JsonResponse
    {
        $contact = (string) $request->input('contact', '');
        if ($contact !== '') {
            try {
                $raw = $verifier->verifyContact($contact, $telegramId);
            } catch (InvalidInitData $e) {
                Log::info('telegram: shared contact rejected', ['reason' => $e->reason]);

                return response()->json([
                    'message' => BotText::get('app.contact_invalid'),
                    'errors' => ['phone' => [BotText::get('app.contact_invalid')]],
                ], 422);
            }
            $phone = GuardianLinkService::normalizePhone($raw);
            if ($phone === null) {
                return response()->json(['message' => BotText::get('reg.phone_uz_only'), 'errors' => ['phone' => [BotText::get('reg.phone_uz_only')]]], 422);
            }
            Cache::put(self::CONTACT_CACHE.$telegramId, $phone, 900);

            return $phone;
        }

        $cached = Cache::get(self::CONTACT_CACHE.$telegramId);

        return is_string($cached) && GuardianLinkService::normalizePhone($cached) !== null ? $cached : null;
    }

    private function account(array $tg): TelegramAccount
    {
        $account = TelegramAccount::query()->firstOrNew(['telegram_id' => (int) $tg['id']]);
        if (! $account->exists) {
            // opened from a direct link without writing to the bot first: the private chat id is the user id
            $account->chat_id = (int) $tg['id'];
        }
        $account->fill([
            'username' => isset($tg['username']) ? mb_substr((string) $tg['username'], 0, 64) : $account->username,
            'first_name' => isset($tg['first_name']) ? mb_substr((string) $tg['first_name'], 0, 128) : $account->first_name,
            'last_name' => isset($tg['last_name']) ? mb_substr((string) $tg['last_name'], 0, 128) : $account->last_name,
            'language_code' => isset($tg['language_code']) ? mb_substr((string) $tg['language_code'], 0, 10) : $account->language_code,
            'last_interaction_at' => now(),
        ])->save();

        return $account;
    }

    private function refCode(?string $to, ?string $startParam): ?string
    {
        foreach ([$to, $startParam] as $candidate) {
            if (is_string($candidate) && preg_match('/^ref_[A-Za-z0-9]{4,32}$/i', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function loggedIn(Request $request, User $user, TelegramStartParam $start, ?string $to): JsonResponse
    {
        if ((string) Auth::id() !== (string) $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }
        WebviewSession::mark($request);
        $target = $start->resolve($user, $to);

        return response()->json([
            'redirect' => $target['url'],
            'post' => $target['post'] ?? null,
            'csrf_token' => $request->session()->token(),
            'role' => $user->role,
        ]);
    }

    private function phoneRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (filled($value) && GuardianLinkService::normalizePhone((string) $value) === null) {
                $fail('Telefon raqam +998XXXXXXXXX formatida bo\'lishi kerak.');
            }
        };
    }

    /** +998 90 *** ** 67 */
    public static function mask(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone);

        return strlen($d) === 12 ? '+'.substr($d, 0, 3).' '.substr($d, 3, 2).' *** ** '.substr($d, 10, 2) : '***';
    }
}
