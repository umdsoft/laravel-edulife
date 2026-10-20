<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\Exam\GuardianRelation;
use App\Http\Controllers\Controller;
use App\Models\Exam\GuardianStudent;
use App\Services\Exam\GuardianLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A guardian asks to link a child by the child's phone (with consent);
 * the teacher/admin approves it (see GuardianLinkService).
 */
class LinkController extends Controller
{
    public function __construct(private readonly GuardianLinkService $links) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'relation' => ['required', Rule::in(GuardianRelation::values())],
            'consent' => ['accepted'],
        ], [
            'phone.required' => 'Farzandingizning telefon raqamini kiriting.',
            'consent.accepted' => 'Farzandingiz ma\'lumotlarini qayta ishlashga rozilik bering.',
        ]);

        $this->links->request($request->user(), $data['phone'], GuardianRelation::from($data['relation']));

        return back()->with('success', 'So\'rov yuborildi. Ustoz tasdiqlagach farzandingiz natijalari shu yerda ko\'rinadi.');
    }

    /** Cancel a pending request or unlink a child. */
    public function destroy(Request $request, GuardianStudent $link): RedirectResponse
    {
        abort_unless($link->guardian_id === $request->user()->id, 404);
        $link->delete();

        return back()->with('success', 'Bog\'lanish o\'chirildi.');
    }
}
