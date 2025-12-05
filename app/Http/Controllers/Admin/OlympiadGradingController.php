<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAnswer;
use App\Services\OlympiadGradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OlympiadGradingController extends Controller
{
    protected OlympiadGradingService $gradingService;

    public function __construct(OlympiadGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Show grading dashboard for olympiad
     */
    public function index(string $id): Response
    {
        $olympiad = Olympiad::with(['olympiadType', 'sections'])->findOrFail($id);
        $stats = $this->gradingService->getGradingStats($olympiad);

        $pendingQueue = $this->gradingService->getManualGradingQueue($id);

        // Group by section
        $sections = $olympiad->sections->filter(fn($s) => $s->requires_manual_grading);
        $queueBySection = $sections->mapWithKeys(function ($section) use ($pendingQueue) {
            return [
                $section->id => [
                    'section' => [
                        'id' => $section->id,
                        'title' => $section->title,
                        'type' => $section->section_type,
                    ],
                    'pending_count' => $pendingQueue
                        ->filter(fn($a) => $a->sectionAttempt->section_id === $section->id)
                        ->count(),
                ],
            ];
        });

        return Inertia::render('Admin/Olympiad/Grading/Index', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'status' => $olympiad->status,
            ],
            'statistics' => $stats,
            'sectionQueue' => $queueBySection->values(),
        ]);
    }

    /**
     * Show grading queue for specific section
     */
    public function sectionQueue(string $id, string $sectionId): Response
    {
        $olympiad = Olympiad::findOrFail($id);
        $section = $olympiad->sections()->findOrFail($sectionId);

        $queue = OlympiadAnswer::whereHas('sectionAttempt', fn($q) => $q->where('section_id', $sectionId))
            ->where('requires_manual_grading', true)
            ->where('is_graded', false)
            ->with(['attempt.user:id,name', 'question:id,question_text,grading_rubric'])
            ->orderBy('created_at')
            ->paginate(20);

        return Inertia::render('Admin/Olympiad/Grading/Queue', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
            ],
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'type' => $section->section_type,
            ],
            'queue' => $queue->map(fn($a) => [
                'id' => $a->id,
                'user_name' => $a->attempt->user->name,
                'question_text' => substr($a->question->question_text, 0, 100) . '...',
                'user_answer' => $a->user_answer,
                'max_points' => $a->max_points,
                'time_spent' => $a->time_spent_seconds,
                'created_at' => $a->created_at->diffForHumans(),
            ]),
            'pagination' => [
                'current_page' => $queue->currentPage(),
                'last_page' => $queue->lastPage(),
                'total' => $queue->total(),
            ],
        ]);
    }

    /**
     * Show single answer for grading
     */
    public function grade(string $id, string $answerId): Response
    {
        $olympiad = Olympiad::findOrFail($id);
        $answer = OlympiadAnswer::with([
            'attempt.user',
            'question',
            'sectionAttempt.section',
        ])->findOrFail($answerId);

        return Inertia::render('Admin/Olympiad/Grading/Grade', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
            ],
            'answer' => [
                'id' => $answer->id,
                'user_name' => $answer->attempt->user->name,
                'section_title' => $answer->sectionAttempt->section->title,
                'question' => [
                    'text' => $answer->question->question_text,
                    'html' => $answer->question->question_html,
                    'media' => $answer->question->question_media,
                    'rubric' => $answer->question->grading_rubric,
                ],
                'user_answer' => $answer->user_answer,
                'max_points' => $answer->max_points,
                'time_spent' => $answer->time_spent_seconds,
                'is_graded' => $answer->is_graded,
                'points_earned' => $answer->points_earned,
                'grading_details' => $answer->grading_details,
                'grader_feedback' => $answer->grader_feedback,
            ],
        ]);
    }

    /**
     * Submit grade for answer
     */
    public function submitGrade(Request $request, string $id, string $answerId)
    {
        $request->validate([
            'points' => 'required|numeric|min:0',
            'rubric_scores' => 'nullable|array',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $answer = OlympiadAnswer::findOrFail($answerId);

        $gradingDetails = [];
        if ($request->rubric_scores) {
            $gradingDetails['rubric_scores'] = $request->rubric_scores;
        }

        $this->gradingService->gradeManually(
            $answer,
            min($request->points, $answer->max_points),
            auth()->id(),
            $gradingDetails,
            $request->feedback
        );

        // Get next answer in queue
        $nextAnswer = OlympiadAnswer::whereHas('sectionAttempt', 
                fn($q) => $q->where('section_id', $answer->sectionAttempt->section_id))
            ->where('requires_manual_grading', true)
            ->where('is_graded', false)
            ->first();

        if ($nextAnswer) {
            return redirect()->route('admin.olympiads.grading.grade', [$id, $nextAnswer->id])
                ->with('success', 'Baho saqlandi');
        }

        return redirect()->route('admin.olympiads.grading.index', $id)
            ->with('success', 'Barcha javoblar baholandi');
    }

    /**
     * Bulk grade with rubric
     */
    public function bulkGrade(Request $request, string $id)
    {
        $request->validate([
            'answer_ids' => 'required|array',
            'rubric_scores' => 'required|array',
            'feedback' => 'nullable|string',
        ]);

        $graded = $this->gradingService->bulkGradeByRubric(
            $request->answer_ids,
            $request->rubric_scores,
            auth()->id(),
            $request->feedback
        );

        return response()->json([
            'success' => true,
            'graded_count' => $graded,
        ]);
    }

    /**
     * Finalize olympiad grading
     */
    public function finalize(string $id)
    {
        $olympiad = Olympiad::findOrFail($id);

        try {
            $this->gradingService->finalizeOlympiadGrading($olympiad);
            return redirect()->route('admin.olympiads.show', $id)
                ->with('success', 'Olimpiada natijalari yakunlandi');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get question analysis
     */
    public function questionAnalysis(string $id, string $questionId)
    {
        $analysis = $this->gradingService->getQuestionAnalysis($id, $questionId);
        return response()->json($analysis);
    }

    /**
     * Export grading data
     */
    public function export(Request $request, string $id)
    {
        $olympiad = Olympiad::with(['attempts.answers', 'attempts.user'])->findOrFail($id);

        $data = $olympiad->attempts->map(fn($a) => [
            'user_name' => $a->user->name,
            'email' => $a->user->email,
            'total_score' => $a->total_weighted_score,
            'max_score' => $a->total_max_score,
            'score_percent' => round($a->score_percent, 2),
            'rank' => $a->rank,
            'is_disqualified' => $a->is_disqualified ? 'Ha' : 'Yo\'q',
            'completed_at' => $a->completed_at?->format('d.m.Y H:i'),
        ]);

        $filename = "grading-{$olympiad->slug}-" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Ism', 'Email', 'Ball', 'Maks Ball', 'Foiz', "O'rin", 'Diskvalifikatsiya', 'Tugallangan']);
            
            foreach ($data as $row) {
                fputcsv($file, array_values($row));
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
