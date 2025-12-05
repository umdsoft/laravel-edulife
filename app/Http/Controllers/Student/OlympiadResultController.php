<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\UserOlympiadHistory;
use App\Services\LeaderboardService;
use App\Services\OlympiadCertificateService;
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
    public function show(string $slug): Response
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
                'total_score' => $attempt->total_weighted_score,
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
                'rank_change' => $userPosition['rank_change'] ?? 0,
            ],
            'nearbyCompetitors' => [
                'above' => $nearbyCompetitors['above']->map(fn($e) => [
                    'rank' => $e->rank,
                    'name' => $e->user->name,
                    'score' => $e->weighted_score,
                ]),
                'below' => $nearbyCompetitors['below']->map(fn($e) => [
                    'rank' => $e->rank,
                    'name' => $e->user->name,
                    'score' => $e->weighted_score,
                ]),
            ],
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show detailed answer review
     */
    public function reviewAnswers(string $slug): Response
    {
        $olympiad = Olympiad::with(['sections'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Only allow review after results are published
        if ($olympiad->status !== Olympiad::STATUS_COMPLETED) {
            return redirect()->route('student.olympiads.results', $slug)
                ->with('error', __('olympiad.results.review_not_available'));
        }

        $attempt = OlympiadAttempt::with([
            'sectionAttempts.section',
            'answers' => fn($q) => $q->with(['question', 'olympiadQuestion']),
        ])
            ->where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $sections = $attempt->sectionAttempts->map(function ($sa) {
            return [
                'section_id' => $sa->section_id,
                'section_type' => $sa->section->section_type,
                'section_title' => $sa->section->title,
                'answers' => $sa->answers->map(fn($a) => [
                    'order_number' => $a->olympiadQuestion?->order_number,
                    'question_text' => $a->question->question_text,
                    'question_type' => $a->question->question_type,
                    'options' => $a->question->options,
                    'user_answer' => $a->user_answer,
                    'correct_answer' => $a->question->correct_answer,
                    'is_correct' => $a->is_correct,
                    'points_earned' => $a->points_earned,
                    'max_points' => $a->max_points,
                    'explanation' => $a->question->explanation,
                    'grader_feedback' => $a->grader_feedback,
                ]),
            ];
        });

        return Inertia::render('Student/Olympiad/Results/Review', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
            ],
            'sections' => $sections,
        ]);
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

        $certificate = $attempt->certificate;

        if (!$certificate) {
            $certificate = $this->certificateService->generate($attempt);
        }

        $path = $this->certificateService->download($certificate);

        return response()->download($path, "sertifikat-{$certificate->certificate_number}.pdf");
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
                'score' => $e->weighted_score,
                'score_percent' => $e->score_percent,
                'time' => $e->formatted_time,
                'is_current_user' => $e->user_id === auth()->id(),
            ]),
            'userPosition' => $userPosition,
            'statistics' => $statistics,
            'distribution' => $distribution,
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
