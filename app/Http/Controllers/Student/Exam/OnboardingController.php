<?php

namespace App\Http\Controllers\Student\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\Subject;
use App\Services\Exam\GuardianLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Exam onboarding (spec section 1, step 2): who fills the form, grade,
 * subject(s), goal, school, the parent's phone and consent (consent_at is
 * stored, section 11) and the consent to show the name on leaderboards.
 */
class OnboardingController extends Controller
{
    public const GOALS = ['otm_grant', 'milliy_sertifikat', 'sat'];

    public function show(Request $request): Response
    {
        $profile = StudentExamProfile::query()->where('user_id', $request->user()->id)->first();

        return Inertia::render('Student/Exam/Onboarding', [
            'profile' => $profile ? [
                'filled_by' => $profile->filled_by,
                'grade' => $profile->is_graduate ? 'graduate' : ($profile->grade ? (string) $profile->grade : null),
                'subject' => $this->subjectChoice($profile->subjects ?? []),
                'goal' => $profile->goal?->value,
                'school_name' => $profile->school_name,
                'parent_phone' => $profile->parent_phone,
                'parent_consent' => $profile->parent_consent_at !== null,
                'show_name_publicly' => (bool) $profile->show_name_publicly,
            ] : null,
            'isComplete' => (bool) $profile?->isComplete(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'filled_by' => ['required', Rule::in(['parent', 'student'])],
            'grade' => ['required', Rule::in(['9', '10', '11', 'graduate'])],
            'subject' => ['required', Rule::in(['math', 'physics', 'both'])],
            'goal' => ['required', Rule::in(self::GOALS)],
            'school_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (GuardianLinkService::normalizePhone($value) === null) {
                    $fail('Telefon raqam +998XXXXXXXXX formatida bo\'lishi kerak.');
                }
            }],
            'parent_consent' => ['accepted'],
            'show_name_publicly' => ['boolean'],
        ], [
            'filled_by.required' => 'Kim to\'ldirayotganini tanlang.',
            'grade.required' => 'Sinfni tanlang.',
            'subject.required' => 'Fanni tanlang.',
            'goal.required' => 'Maqsadni tanlang.',
            'parent_phone.required' => 'Ota-onaning telefon raqamini kiriting.',
            'parent_consent.accepted' => 'Ota-onaning roziligisiz davom etib bo\'lmaydi.',
        ]);

        $user = $request->user();
        $profile = StudentExamProfile::query()->firstOrNew(['user_id' => $user->id]);
        $showName = (bool) ($data['show_name_publicly'] ?? false);

        $profile->fill([
            'filled_by' => $data['filled_by'],
            'grade' => $data['grade'] === 'graduate' ? null : (int) $data['grade'],
            'is_graduate' => $data['grade'] === 'graduate',
            'subjects' => $data['subject'] === 'both' ? [Subject::MATH, Subject::PHYSICS] : [$data['subject']],
            'goal' => $data['goal'],
            'school_name' => isset($data['school_name']) ? trim($data['school_name']) ?: null : null,
            'parent_phone' => GuardianLinkService::normalizePhone($data['parent_phone']),
            // the first consent is kept; a re-saved form does not move it
            'parent_consent_at' => $profile->parent_consent_at ?? now(),
            'parent_consent_source' => $profile->parent_consent_source ?? ($data['filled_by'] === 'parent' ? 'web_parent' : 'web_student'),
            'show_name_publicly' => $showName,
            'public_name_consent_at' => $showName ? ($profile->public_name_consent_at ?? now()) : null,
            'onboarded_at' => $profile->onboarded_at ?? now(),
        ])->save();

        $target = Route::has('student.exam.index') ? route('student.exam.index') : route('student.exam.progress');

        return redirect($target)->with('success', 'Ma\'lumotlar saqlandi.');
    }

    private function subjectChoice(array $subjects): ?string
    {
        $math = in_array(Subject::MATH, $subjects, true);
        $physics = in_array(Subject::PHYSICS, $subjects, true);

        return match (true) {
            $math && $physics => 'both',
            $math => 'math',
            $physics => 'physics',
            default => null,
        };
    }
}
