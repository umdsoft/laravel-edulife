<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\LabAttempt;
use App\Models\LabExperiment;
use App\Models\LabReport;
use App\Models\LabUserProgress;
use App\Models\LabRating;
use App\Models\LabSavedExperiment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\XPRewardService;

class LabApiController extends Controller
{
    public function __construct(
        protected XPRewardService $xpService
    ) {}

    // ═══════════════════════════════════════════════════════════════════════
    // ATTEMPT ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Save simulation state
     */
    public function saveState(Request $request, string $attemptId)
    {
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $attempt->saveState($request->input('state', []));
        $attempt->recordActivity();
        
        return response()->json([
            'success' => true,
            'time_spent_seconds' => $attempt->time_spent_seconds,
        ]);
    }

    /**
     * Add measurement
     */
    public function addMeasurement(Request $request, string $attemptId)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'value' => 'required|numeric',
            'unit' => 'required|string|max:20',
        ]);
        
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $attempt->addMeasurement([
            'name' => $request->name,
            'value' => $request->value,
            'unit' => $request->unit,
            'step' => $request->input('step', $attempt->current_task),
        ]);
        
        return response()->json([
            'success' => true,
            'measurements' => $attempt->measurements,
        ]);
    }

    /**
     * Submit calculation
     */
    public function submitCalculation(Request $request, string $attemptId)
    {
        $request->validate([
            'formula_id' => 'required|string',
            'inputs' => 'required|array',
            'result' => 'required|numeric',
        ]);
        
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $attempt->addCalculation([
            'formula_id' => $request->formula_id,
            'inputs' => $request->inputs,
            'result' => $request->result,
            'step' => $request->input('step', $attempt->current_task),
            'is_correct' => $request->input('is_correct', true),
        ]);
        
        return response()->json([
            'success' => true,
            'calculations' => $attempt->calculations,
        ]);
    }

    /**
     * Complete a task
     */
    public function completeTask(Request $request, string $attemptId)
    {
        $request->validate([
            'task_number' => 'required|integer|min:1',
            'score' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:1',
        ]);
        
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $attempt->completeTask(
            $request->task_number,
            $request->score,
            $request->max_score,
            $request->input('user_input')
        );
        
        return response()->json([
            'success' => true,
            'current_task' => $attempt->current_task,
            'completed_tasks' => $attempt->completed_tasks,
            'progress_percent' => $attempt->progress_percent,
        ]);
    }

    /**
     * Complete the experiment attempt
     */
    public function completeAttempt(Request $request, string $attemptId)
    {
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->with('experiment')
            ->firstOrFail();
        
        // Optional conclusion
        if ($request->has('conclusion')) {
            $attempt->conclusion_text = $request->conclusion;
        }
        
        if ($request->has('error_analysis')) {
            $attempt->error_analysis = $request->error_analysis;
        }
        
        $attempt->complete();
        
        // Award XP using service
        $xpEarned = 0;
        if ($attempt->passed) {
             $xpEarned = $this->xpService->awardLabXP($attempt->user, $attempt->experiment->difficulty);
             
             // Update attempt with earned XP
             $attempt->update(['xp_earned' => $xpEarned]);
        }
        
        // Check for new badges
        $progress = LabUserProgress::where('user_id', Auth::id())->first();
        $newBadges = $progress?->checkAndAwardBadges() ?? [];
        
        return response()->json([
            'success' => true,
            'result' => [
                'status' => $attempt->status,
                'percentage' => $attempt->percentage,
                'grade' => $attempt->grade,
                'grade_points' => $attempt->grade_points,
                'passed' => $attempt->passed,
                'xp_earned' => $xpEarned,
                'coins_earned' => $attempt->coins_earned,
                'badges_earned' => $attempt->badges_earned,
                'time_spent' => $attempt->time_spent_text,
            ],
            'new_badges' => array_map(fn($b) => [
                'id' => $b->id,
                'name' => $b->localized_name,
                'icon' => $b->icon,
                'rarity' => $b->rarity,
            ], $newBadges),
        ]);
    }

    /**
     * Pause attempt
     */
    public function pauseAttempt(string $attemptId)
    {
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $attempt->pause();
        
        return response()->json([
            'success' => true,
            'time_spent_seconds' => $attempt->time_spent_seconds,
        ]);
    }

    /**
     * Add screenshot
     */
    public function addScreenshot(Request $request, string $attemptId)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);
        
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $path = $request->file('image')->store('lab-screenshots', 'public');
        
        $attempt->addScreenshot(
            asset('storage/' . $path),
            $request->input('caption'),
            $request->input('step')
        );
        
        return response()->json([
            'success' => true,
            'screenshots' => $attempt->screenshots,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SAVED EXPERIMENTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Save experiment state
     */
    public function saveExperiment(Request $request)
    {
        $request->validate([
            'experiment_id' => 'required|uuid|exists:lab_experiments,id',
            'state' => 'required|array',
            'name' => 'nullable|string|max:255',
        ]);
        
        $saved = LabSavedExperiment::create([
            'user_id' => Auth::id(),
            'experiment_id' => $request->experiment_id,
            'saved_state' => $request->state,
            'name' => $request->name,
            'description' => $request->input('description'),
            'is_public' => $request->input('is_public', false),
        ]);
        
        return response()->json([
            'success' => true,
            'saved_experiment' => $saved->toDisplayData(),
        ]);
    }

    /**
     * Load saved experiment
     */
    public function loadSavedExperiment(string $id)
    {
        $saved = LabSavedExperiment::where('id', $id)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhere('is_public', true);
            })
            ->firstOrFail();
        
        $saved->recordView();
        
        return response()->json([
            'success' => true,
            'state' => $saved->toSimulationState(),
        ]);
    }

    /**
     * List user's saved experiments
     */
    public function mySavedExperiments(Request $request)
    {
        $saved = LabSavedExperiment::where('user_id', Auth::id())
            ->with('experiment')
            ->orderByDesc('created_at')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'saved_experiments' => $saved->getCollection()
                ->map(fn($s) => $s->toDisplayData()),
            'pagination' => [
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
                'total' => $saved->total(),
            ],
        ]);
    }

    /**
     * Delete saved experiment
     */
    public function deleteSavedExperiment(string $id)
    {
        $saved = LabSavedExperiment::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $saved->delete();
        
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RATINGS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Submit rating for experiment
     */
    public function submitRating(Request $request)
    {
        $request->validate([
            'experiment_id' => 'required|uuid|exists:lab_experiments,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
            'liked_aspects' => 'nullable|array',
            'disliked_aspects' => 'nullable|array',
            'difficulty_rating' => 'nullable|in:too_easy,just_right,too_hard',
        ]);
        
        // Check if user completed this experiment
        $hasCompleted = LabAttempt::where('user_id', Auth::id())
            ->where('experiment_id', $request->experiment_id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasCompleted) {
            return response()->json([
                'success' => false,
                'message' => 'Tajribani baholash uchun avval uni yakunlang',
            ], 403);
        }
        
        $rating = LabRating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'experiment_id' => $request->experiment_id,
            ],
            [
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'liked_aspects' => $request->liked_aspects,
                'disliked_aspects' => $request->disliked_aspects,
                'difficulty_rating' => $request->difficulty_rating,
                'would_recommend' => $request->input('would_recommend', true),
            ]
        );
        
        // Update experiment statistics
        $rating->experiment->updateStatistics();
        
        return response()->json([
            'success' => true,
            'rating' => $rating->toDisplayData(),
        ]);
    }

    /**
     * Mark rating as helpful
     */
    public function markRatingHelpful(Request $request, string $ratingId)
    {
        $rating = LabRating::findOrFail($ratingId);
        
        $helpful = $request->input('helpful', true);
        if ($helpful) {
            $rating->markHelpful();
        } else {
            $rating->markNotHelpful();
        }
        
        return response()->json([
            'success' => true,
            'helpful_count' => $rating->helpful_count,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PROGRESS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get user's lab progress summary
     */
    public function getProgress()
    {
        $progress = LabUserProgress::firstOrCreate(['user_id' => Auth::id()]);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->getDashboardStats(),
            'skills' => $progress->skills,
            'category_progress' => $progress->category_progress,
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'show_hints' => 'boolean',
            'show_formulas' => 'boolean',
            'auto_save' => 'boolean',
            'sound_enabled' => 'boolean',
            'preferred_language' => 'in:uz,ru,en',
        ]);
        
        $progress = LabUserProgress::firstOrCreate(['user_id' => Auth::id()]);
        
        $progress->update($request->only([
            'show_hints',
            'show_formulas',
            'auto_save',
            'sound_enabled',
            'preferred_language',
        ]));
        
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REPORTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Generate report from attempt
     */
    public function generateReport(string $attemptId)
    {
        $attempt = LabAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->firstOrFail();
        
        // Check if report already exists
        $report = LabReport::where('attempt_id', $attemptId)->first();
        
        if (!$report) {
            $report = LabReport::createFromAttempt($attempt);
        }
        
        return response()->json([
            'success' => true,
            'report' => $report->toExportData(),
        ]);
    }

    /**
     * Update report content
     */
    public function updateReport(Request $request, string $reportId)
    {
        $report = LabReport::where('id', $reportId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['draft', 'returned'])
            ->firstOrFail();
        
        $report->update($request->only([
            'introduction',
            'objectives_text',
            'theory_summary',
            'procedure_steps',
            'observations',
            'calculations_detail',
            'error_analysis',
            'conclusion',
            'questions_answers',
        ]));
        
        return response()->json([
            'success' => true,
            'report' => $report->toExportData(),
        ]);
    }

    /**
     * Submit report
     */
    public function submitReport(Request $request, string $reportId)
    {
        $report = LabReport::where('id', $reportId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['draft', 'returned'])
            ->firstOrFail();
        
        $report->submit($request->input('notes'));
        
        return response()->json([
            'success' => true,
            'status' => $report->status,
        ]);
    }

    /**
     * Download report as PDF
     */
    public function downloadReportPdf(string $reportId)
    {
        $report = LabReport::where('id', $reportId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Generate PDF if not exists
        if (!$report->pdf_generated) {
            $report->generatePdf();
        }
        
        // Return PDF download
        return response()->download(
            storage_path('app/' . $report->pdf_path),
            "lab_report_{$report->id}.pdf"
        );
    }
}
