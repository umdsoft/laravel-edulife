<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\UserOlympiadHistory;
use App\Services\LeaderboardService;
use App\Services\OlympiadCertificateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OlympiadResultController extends Controller
{
    protected LeaderboardService $leaderboardService;
    protected OlympiadCertificateService $certificateService;

    public function __construct(
        LeaderboardService $leaderboardService,
        OlympiadCertificateService $certificateService
    ) {
        $this->leaderboardService = $leaderboardService;
        $this->certificateService = $certificateService;
    }

    /**
     * Show results page
     */
    public function show(string $slug): Response|RedirectResponse
    {
        $olympiad = Olympiad::with(['olympiadType', 'stage', 'sections'])
            ->where('slug', $slug)
            ->firstOrFail();

        $attempt = OlympiadAttempt::with(['sectionAttempts.section', 'answers'])
            ->where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!$attempt->is_completed) {
            return redirect()->route('student.olympiads.exam', $slug);
        }

        $userPosition = $this->leaderboardService->getUserPosition($olympiad->id, auth()->id());
        $nearbyCompetitors = $this->leaderboardService->getNearbyCompetitors($olympiad->id, auth()->id());
        $statistics = $this->leaderboardService->getStatistics($olympiad->id);

        return Inertia::render('Student/Olympiad/Results/Index', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'type' => $olympiad->olympiadType->display_name,
                'stage' => $olympiad->stage?->display_name,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at->format('d.m.Y H:i'),
                'completed_at' => $attempt->completed_at->format('d.m.Y H:i'),
                'duration' => $attempt->formatted_duration,
                'total_score' => $attempt->total_raw_score,
                'max_score' => $attempt->total_max_score,
                'score_percent' => round($attempt->score_percent, 1),
                'is_grading' => $attempt->requires_manual_grading && !$attempt->manual_grading_complete,
                'is_disqualified' => $attempt->is_disqualified,
                'disqualified_reason' => $attempt->disqualified_reason,
                'sections_results' => $attempt->sections_results,
            ],
            'sectionDetails' => $attempt->sectionAttempts->map(fn($sa) => [
                'section_type' => $sa->section->section_type,
                'section_title' => $sa->section->title,
                'raw_score' => $sa->raw_score,
                'weighted_score' => $sa->weighted_score,
                'max_score' => $sa->max_score,
                'score_percent' => round($sa->score_percent, 1),
                'questions_answered' => $sa->questions_answered,
                'questions_correct' => $sa->questions_correct,
                'requires_grading' => $sa->requires_manual_grading && !$sa->is_graded,
            ]),
            'ranking' => [
                'rank' => $userPosition['rank'] ?? null,
                'total_participants' => $userPosition['total_participants'] ?? 0,
                'percentile' => $userPosition['percentile'] ?? null,
                'top_percent' => $userPosition['top_percent'] ?? null,
                'rank_change' => $userPosition['rank_change'] ?? 0,
            ],
            'nearbyCompetitors' => [
                'above' => $nearbyCompetitors['above']->map(fn($e) => [
                    'rank' => $e->rank,
                    'name' => $e->user->name,
                    'score' => $e->score,
                ]),
                'below' => $nearbyCompetitors['below']->map(fn($e) => [
                    'rank' => $e->rank,
                    'name' => $e->user->name,
                    'score' => $e->score,
                ]),
            ],
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show detailed answer review
     */
    public function reviewAnswers(string $slug): Response|RedirectResponse
    {
        $olympiad = Olympiad::with(['sections'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Only allow review after results are published
        if ($olympiad->status !== Olympiad::STATUS_COMPLETED) {
            return redirect()->route('student.olympiads.results', $slug)
                ->with('error', __('olympiad.results.review_not_available'));
        }

        // the page walks section -> answers -> question, so load exactly that
        $attempt = OlympiadAttempt::with([
            'sectionAttempts.section',
            'sectionAttempts.answers.question',
            'sectionAttempts.answers.olympiadQuestion',
        ])
            ->where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // results_config (admin): may the participant see the correct answers / explanations?
        $config = array_merge(Olympiad::getDefaultResultsConfig(), $olympiad->results_config ?? []);
        $showCorrect = (bool) $config['show_correct_answers'];
        $showExplanations = (bool) $config['show_explanations'];

        $sections = $attempt->sectionAttempts->map(function ($sa) use ($showCorrect, $showExplanations) {
            return [
                'section_id' => $sa->section_id,
                'section_type' => $sa->section->section_type,
                'section_title' => $sa->section->title,
                'answers' => $sa->answers
                    ->sortBy(fn ($a) => $a->olympiadQuestion?->order_number ?? PHP_INT_MAX)
                    ->values()
                    ->map(fn ($a) => $this->presentReviewAnswer($a, $showCorrect, $showExplanations)),
            ];
        });

        return Inertia::render('Student/Olympiad/Results/Review', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'show_correct_answers' => $showCorrect,
            ],
            'sections' => $sections,
        ]);
    }

    /**
     * One reviewed answer: the question and option texts as authored (the page
     * renders their Markdown / LaTeX like the exam does), which options the
     * student chose and — when the olympiad allows it — which are correct.
     */
    private function presentReviewAnswer(\App\Models\OlympiadAnswer $answer, bool $showCorrect, bool $showExplanations): array
    {
        $question = $answer->question;
        $type = $question?->question_type;

        $options = collect(is_array($question?->options) ? $question->options : [])->values()
            ->map(fn ($opt, $i) => is_array($opt)
                ? ['id' => (string) ($opt['id'] ?? $i), 'text' => (string) ($opt['text'] ?? $opt['label'] ?? $opt['value'] ?? ''), 'is_correct' => (bool) ($opt['is_correct'] ?? false)]
                : ['id' => (string) $i, 'text' => (string) $opt, 'is_correct' => false]);
        if ($options->isEmpty() && $type === \App\Models\QuestionBank::TYPE_TRUE_FALSE) {
            $options = collect([['id' => 'true', 'text' => "To'g'ri", 'is_correct' => false], ['id' => 'false', 'text' => "Noto'g'ri", 'is_correct' => false]]);
        }

        $ids = fn ($value) => collect(is_array($value) ? $value : ($value === null || $value === '' ? [] : [$value]))
            ->map(fn ($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string) $v);
        $isChoice = in_array($type, [\App\Models\QuestionBank::TYPE_SINGLE_CHOICE, \App\Models\QuestionBank::TYPE_MULTIPLE_CHOICE, \App\Models\QuestionBank::TYPE_TRUE_FALSE], true)
            && $options->isNotEmpty();

        $chosen = $ids($answer->user_answer);
        $correct = $ids($question?->correct_answer)->merge($options->where('is_correct', true)->pluck('id'))->unique();

        $text = fn ($value) => is_array($value) ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value)) : (($value === null || $value === '') ? null : (string) $value);

        return [
            'order_number' => $answer->olympiadQuestion?->order_number,
            'question_text' => (string) ($question?->question_text ?? ''),
            'question_type' => $type,
            'is_choice' => $isChoice,
            'options' => $isChoice ? $options->map(fn ($o, $i) => [
                'id' => $o['id'],
                'letter' => chr(65 + $i),
                'text' => $o['text'],
                'selected' => $chosen->contains($o['id']),
                'correct' => $showCorrect ? $correct->contains($o['id']) : null,
            ])->values() : [],
            'answer_text' => $isChoice ? null : $text($answer->user_answer),
            'correct_text' => ($showCorrect && ! $isChoice) ? $text($question?->correct_answer) : null,
            'is_correct' => $answer->is_correct,
            'points_earned' => $answer->points_earned,
            'max_points' => $answer->max_points,
            'explanation' => $showExplanations ? $question?->explanation : null,
            'grader_feedback' => $answer->grader_feedback,
        ];
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!$attempt->is_completed || $attempt->is_disqualified) {
            return back()->with('error', __('olympiad.certificate.not_available'));
        }

        try {
            $certificate = $attempt->certificate ?? $this->certificateService->generate($attempt);
            $path = $this->certificateService->download($certificate);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Sertifikatni tayyorlab bo'lmadi. Birozdan so'ng qayta urinib ko'ring.");
        }

        return response()->download($path, "sertifikat-{$certificate->certificate_number}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Show full leaderboard
     */
    public function leaderboard(string $slug): Response
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $leaderboard = $this->leaderboardService->getLiveLeaderboard($olympiad->id, 100);
        $userPosition = $this->leaderboardService->getUserPosition($olympiad->id, auth()->id());
        $statistics = $this->leaderboardService->getStatistics($olympiad->id);
        $distribution = $this->leaderboardService->getScoreDistribution($olympiad->id);

        return Inertia::render('Student/Olympiad/Results/Leaderboard', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
            ],
            'leaderboard' => $leaderboard->map(fn($e) => [
                'rank' => $e->rank,
                'rank_change' => $e->rank_change,
                'user_name' => $e->user->name,
                'user_avatar' => $e->user->avatar,
                'score' => $e->score,
                'score_percent' => $e->score_percent,
                'time' => $e->formatted_time,
                'is_current_user' => $e->user_id === auth()->id(),
            ]),
            'userPosition' => $userPosition,
            'statistics' => $statistics,
            'distribution' => $distribution,
            // the results page exists only for someone who finished the olympiad (else 404)
            'hasResults' => OlympiadAttempt::where('olympiad_id', $olympiad->id)
                ->where('user_id', auth()->id())
                ->whereIn('status', [OlympiadAttempt::STATUS_SUBMITTED, OlympiadAttempt::STATUS_GRADING, OlympiadAttempt::STATUS_GRADED])
                ->exists(),
        ]);
    }

    /**
     * Show user's olympiad history
     */
    public function history(): Response
    {
        $history = UserOlympiadHistory::with([
            'olympiad.olympiadType',
            'olympiad.stage',
        ])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        $statistics = [
            'total_participated' => UserOlympiadHistory::where('user_id', auth()->id())->count(),
            'total_completed' => UserOlympiadHistory::where('user_id', auth()->id())
                ->where('status', UserOlympiadHistory::STATUS_COMPLETED)->count(),
            'best_rank' => UserOlympiadHistory::where('user_id', auth()->id())
                ->whereNotNull('rank')->min('rank'),
            'total_coins' => UserOlympiadHistory::where('user_id', auth()->id())
                ->sum('coins_earned'),
            'total_cash' => UserOlympiadHistory::where('user_id', auth()->id())
                ->sum('cash_prize'),
            'certificates' => UserOlympiadHistory::where('user_id', auth()->id())
                ->where('certificate_issued', true)->count(),
        ];

        return Inertia::render('Student/Olympiad/History', [
            'history' => $history->map(fn($h) => [
                'id' => $h->id,
                'olympiad_id' => $h->olympiad_id,
                // certificate / results routes are keyed by slug
                'olympiad_slug' => $h->olympiad->slug,
                'olympiad_title' => $h->olympiad->title,
                'olympiad_type' => $h->olympiad->olympiadType->display_name,
                'stage' => $h->olympiad->stage?->display_name,
                'status' => $h->status,
                'rank' => $h->rank,
                'rank_label' => $h->rank_label,
                'total_participants' => $h->total_participants,
                'score_percent' => $h->score_percent,
                'coins_earned' => $h->coins_earned,
                'cash_prize' => $h->cash_prize,
                'certificate_issued' => $h->certificate_issued,
                'advanced' => $h->advanced_to_next_stage,
                'created_at' => $h->created_at->format('d.m.Y'),
            ]),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
            ],
            'statistics' => $statistics,
        ]);
    }

    /**
     * Get user certificates
     */
    public function certificates(): Response
    {
        $certificates = $this->certificateService->getUserCertificates(auth()->id());

        return Inertia::render('Student/Olympiad/Certificates', [
            'certificates' => $certificates->map(fn($c) => [
                'id' => $c->id,
                'certificate_number' => $c->certificate_number,
                'certificate_type' => $c->certificate_type,
                'certificate_type_label' => $c->certificate_type_label,
                'olympiad_title' => $c->olympiad->title,
                'olympiad_type' => $c->olympiad->olympiadType->display_name,
                'stage' => $c->olympiad->stage?->display_name,
                'rank' => $c->rank,
                'score' => $c->score,
                'issued_at' => $c->issued_at->format('d.m.Y'),
                'download_url' => route('student.olympiads.certificate.download', [
                    'slug' => $c->olympiad->slug,
                ]),
                'verification_url' => $c->verification_url,
            ]),
        ]);
    }
}
