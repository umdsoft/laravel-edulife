<?php

namespace App\Http\Controllers\Student\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\StudentWeeklyResult;
use App\Services\Exam\ReferralService;
use App\Services\Exam\ReportBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "O'sishim": growth over weeks, topic map per subject, weakest topics and
 * 3 recommended topics for next week, result card download.
 */
class ProgressController extends Controller
{
    public function __construct(
        private readonly ReportBuilder $reports,
        private readonly ReferralService $referrals,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $subjects = $this->reports->subjectsFor($user);

        $latest = StudentWeeklyResult::query()
            ->where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('Student/Exam/Progress', [
            'subjects' => $subjects->map(fn ($s) => ['code' => $s->code, 'name' => $s->name_uz])->values(),
            'history' => $this->reports->history($user),
            'topicMap' => $this->reports->topicMap($user, $subjects),
            'weakest' => $this->reports->weakestTopics($user, 5),
            'recommendations' => $this->reports->recommendations($user),
            'latest' => $latest ? $this->reports->resultRow($latest, (string) $subjects->firstWhere('id', $latest->subject_id)?->code) : null,
            'referralLink' => $this->referrals->linkFor($user),
        ]);
    }
}
