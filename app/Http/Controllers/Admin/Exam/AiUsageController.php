<?php

namespace App\Http\Controllers\Admin\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StartQuestionDraftRunRequest;
use App\Models\Exam\QuestionDraftRun;
use App\Services\Exam\QuestionDraftDashboard;
use App\Services\Exam\QuestionDraftService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin "AI qoralamalar": run log with tokens and estimated cost, and a form
 * to start a drafting run (stage 4).
 */
class AiUsageController extends Controller
{
    public function index(QuestionDraftDashboard $dashboard): Response
    {
        return Inertia::render('Admin/Exam/AiDrafts/Index', [
            'runs' => $dashboard->runs(),
            'totals' => $dashboard->totals(),
            'settings' => $dashboard->settings(),
            'options' => $dashboard->formOptions(),
        ]);
    }

    public function store(StartQuestionDraftRunRequest $request, QuestionDraftService $drafts): RedirectResponse
    {
        $run = $drafts->start($request->validated(), $request->user());

        return back()->with(...self::flash($run));
    }

    /** @return array{0: string, 1: string} */
    public static function flash(QuestionDraftRun $run): array
    {
        $run = $run->fresh();

        return match ($run->status) {
            QuestionDraftRun::STATUS_SKIPPED => ['error', "AI qoralama ishga tushmadi: ".($run->error ?: 'sozlanmagan')],
            QuestionDraftRun::STATUS_FAILED => ['error', "AI qoralama xato bilan tugadi: ".($run->error ?: '')],
            QuestionDraftRun::STATUS_SUCCEEDED, QuestionDraftRun::STATUS_PARTIAL => ['success', "Tayyor: {$run->drafts_created} ta qoralama tekshiruv navbatiga tushdi."],
            default => ['success', "So'rov navbatga qo'yildi. Qoralamalar tayyor bo'lgach tekshiruv navbatida ko'rinadi."],
        };
    }
}
