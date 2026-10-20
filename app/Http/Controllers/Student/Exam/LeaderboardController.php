<?php

namespace App\Http\Controllers\Student\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Subject;
use App\Services\Exam\LeaderboardService;
use App\Services\Exam\ReportBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Exam leaderboards (spec section 9): overall top, "eng ko'p o'sganlar",
 * schools; per subject and week; the viewer's own position.
 */
class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $boards,
        private readonly ReportBuilder $reports,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $subjects = Subject::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name_uz']);

        $mine = $this->reports->subjectsFor($user)->pluck('code')->all();
        $code = $request->query('subject');
        $subject = $subjects->firstWhere('code', $code)
            ?? $subjects->first(fn ($s) => in_array($s->code, $mine, true))
            ?? $subjects->first();

        $weeks = $subject ? $this->boards->weeks($subject) : [];
        $week = $request->query('week');
        $week = is_string($week) && in_array($week, $weeks, true) ? $week : ($weeks[0] ?? null);

        $board = $subject && $week ? $this->boards->board($subject, $week) : null;
        $position = $board ? $this->boards->positionOf($board, $user) : null;
        $tab = in_array($request->query('tab'), ['top', 'growth', 'schools'], true) ? $request->query('tab') : 'top';

        return Inertia::render('Student/Exam/Leaderboard', [
            'subjects' => $subjects->map(fn ($s) => ['code' => $s->code, 'name' => $s->name_uz])->values(),
            'subject' => $subject?->code,
            'weeks' => $weeks,
            'week' => $week,
            'tab' => $tab,
            'board' => $board ? [
                'participants' => $board['participants'],
                'top' => $board['top'],
                'growth' => $board['growth'],
                'schools' => $board['schools'],
                'school_min' => $board['school_min'],
            ] : null,
            'me' => [
                'uid' => LeaderboardService::uid($user->id),
                'top' => $position['top'] ?? null ? collect($position['top'])->except('user_id')->all() : null,
                'growth' => $position['growth'] ?? null ? collect($position['growth'])->except('user_id')->all() : null,
                'school' => $position['school'] ?? null,
            ],
        ]);
    }
}
