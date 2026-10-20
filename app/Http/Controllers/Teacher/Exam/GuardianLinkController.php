<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\StudentExamProfile;
use App\Services\Exam\GuardianLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Ota-ona so'rovlari": the teacher/admin confirms guardian <-> child links
 * (no SMS verification exists; see GuardianLinkService).
 */
class GuardianLinkController extends Controller
{
    public function __construct(private readonly GuardianLinkService $links) {}

    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), ['pending', 'verified', 'rejected'], true) ? $request->query('status') : 'pending';

        $query = GuardianStudent::query()
            ->with(['guardian:id,first_name,last_name,phone', 'student:id,first_name,last_name,phone'])
            ->when($status === 'pending', fn ($q) => $q->pending())
            ->when($status === 'verified', fn ($q) => $q->verified())
            ->when($status === 'rejected', fn ($q) => $q->whereNotNull('rejected_at'))
            ->latest('updated_at');

        $page = $query->paginate(20)->withQueryString();
        $parentPhones = StudentExamProfile::query()
            ->whereIn('user_id', collect($page->items())->pluck('student_id')->all() ?: ['__none__'])
            ->pluck('parent_phone', 'user_id');

        $page->through(fn (GuardianStudent $l) => [
            'id' => $l->id,
            'relation' => $l->relation?->value,
            'guardian' => $l->guardian ? ['name' => trim($l->guardian->first_name.' '.$l->guardian->last_name), 'phone' => $l->guardian->phone] : null,
            'student' => $l->student ? ['name' => trim($l->student->first_name.' '.$l->student->last_name), 'phone' => $l->student->phone] : null,
            'parent_phone' => $parentPhones[$l->student_id] ?? null,
            'phone_matches' => $l->guardian && GuardianLinkService::normalizePhone($l->guardian->phone) !== null
                && GuardianLinkService::normalizePhone($l->guardian->phone) === GuardianLinkService::normalizePhone($parentPhones[$l->student_id] ?? null),
            'consent_at' => $l->consent_at?->format('d.m.Y H:i'),
            'requested_at' => $l->updated_at?->format('d.m.Y H:i'),
            'verified_at' => $l->verified_at?->format('d.m.Y H:i'),
            'rejected_at' => $l->rejected_at?->format('d.m.Y H:i'),
            'reject_reason' => $l->reject_reason,
        ]);

        return Inertia::render('Teacher/Exam/GuardianLinks', [
            'links' => $page,
            'status' => $status,
            'counts' => [
                'pending' => GuardianStudent::query()->pending()->count(),
                'verified' => GuardianStudent::query()->verified()->count(),
                'rejected' => GuardianStudent::query()->whereNotNull('rejected_at')->count(),
            ],
        ]);
    }

    public function approve(Request $request, GuardianStudent $link): RedirectResponse
    {
        $this->links->approve($link, $request->user());

        return back()->with('success', 'Bog\'lanish tasdiqlandi.');
    }

    public function reject(Request $request, GuardianStudent $link): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $this->links->reject($link, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'So\'rov rad etildi.');
    }
}
