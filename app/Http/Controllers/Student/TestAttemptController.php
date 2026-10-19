<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestAnswer;
use App\Models\Question;
use App\Http\Requests\Student\StartTestRequest;
use App\Http\Requests\Student\SubmitAnswerRequest;
use App\Http\Requests\Student\SubmitTestRequest;
use App\Services\TestEvaluationService;
use App\Services\XPRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TestAttemptController extends Controller
{
    public function __construct(
        protected TestEvaluationService $evaluationService,
        protected XPRewardService $xpService
    ) {}
    
    /**
     * Begin a new test attempt
     */
    public function begin(StartTestRequest $request, Test $test)
    {
        $user = Auth::user();

        abort_unless($test->is_active, 404);

        // Verify enrollment
        $enrollment = $user->enrollments()
            ->where('course_id', $test->course_id)
            ->firstOrFail();
        
        // Check for active attempt
        $activeAttempt = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->where('status', 'in_progress')
            ->first();
        
        if ($activeAttempt) {
            return redirect()->route('student.tests.attempt', $activeAttempt);
        }
        
        // Check max attempts
        $previousAttempts = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->count();
        
        // max_attempts = 0 means unlimited
        if (!$test->allowsAnotherAttempt($previousAttempts)) {
            return back()->with('error', 'Maksimal urinishlar soniga yetdingiz');
        }

        if ($reason = $test->prerequisiteError($enrollment)) {
            return back()->with('error', $reason);
        }
        
        // Get questions (randomized if enabled)
        $questions = $test->questions()
            ->when($test->shuffle_questions, fn($q) => $q->inRandomOrder(), fn($q) => $q->orderBy('sort_order'))
            ->limit($test->questions_count)
            ->get();
        
        if ($questions->isEmpty()) {
            return back()->with('error', 'Testda savollar mavjud emas');
        }
        
        $attempt = null;
        
        DB::transaction(function () use ($user, $test, $enrollment, $questions, $previousAttempts, &$attempt) {
            // Create attempt
            $attempt = TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $test->id,
                'course_id' => $test->course_id,
                'enrollment_id' => $enrollment->id,
                'attempt_number' => $previousAttempts + 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => now()->addMinutes($test->time_limit),
                'total_questions' => $questions->count(),
            ]);
            
            // Create answer placeholders
            foreach ($questions as $question) {
                TestAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'max_points' => $question->points ?? 1,
                ]);
            }
            
            // Log event
            $attempt->logEvent('test_started', [
                'questions_count' => $questions->count(),
                'time_limit' => $test->time_limit,
            ]);
        });
        
        return redirect()->route('student.tests.attempt', $attempt);
    }
    
    /**
     * Show active attempt (test taking page)
     */
    public function show(TestAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        
        // Check if expired
        if ($attempt->is_expired && $attempt->status === 'in_progress') {
            $this->evaluationService->evaluate($attempt);
            $attempt->markAsExpired();
            return redirect()->route('student.tests.result', $attempt);
        }
        
        // Check if already submitted
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.tests.result', $attempt);
        }
        
        $attempt->load(['test', 'course']);
        
        // Get questions with user's answers
        $answers = $attempt->answers()
            ->with(['question.options'])
            ->get()
            ->keyBy('question_id');
        
        // Prepare questions (hide correct answers)
        $questions = $answers->map(function ($answer) use ($attempt) {
            $question = $answer->question;

            return [
                'id' => $question->id,
                'type' => $question->type,
                'content' => $question->question_text,
                // no answer key: fill-blank options are the accepted answers, ordering is stored in order, ...
                'options' => $question->optionsForStudent($attempt->test->shuffle_options),
                'points' => $question->points,
                'hint' => null,
                'user_answer' => $answer->answer,
                'is_answered' => $answer->is_answered,
                'is_flagged' => $answer->is_flagged_for_review,
            ];
        })->values();
        
        return Inertia::render('Student/Tests/Attempt', [
            'attempt' => $attempt,
            'test' => $attempt->test,
            'course' => $attempt->course,
            'questions' => $questions,
            'timeRemaining' => $attempt->time_remaining,
        ]);
    }
    
    /**
     * Save answer for a question
     */
    public function saveAnswer(SubmitAnswerRequest $request, TestAttempt $attempt)
    {
        $this->authorize('update', $attempt);
        
        // Check if expired or already finished
        if ($attempt->status !== 'in_progress' || $attempt->is_expired) {
            return response()->json(['error' => 'Vaqt tugadi'], 400);
        }
        
        $answer = $attempt->answers()
            ->where('question_id', $request->question_id)
            ->firstOrFail();
        
        $wasAnswered = $answer->is_answered;
        
        $answer->update([
            'answer' => $request->answer,
            // "false" (Noto'g'ri) and 0 are answers too
            'is_answered' => TestAnswer::hasValue($request->answer),
            'answered_at' => now(),
            'time_spent' => $answer->time_spent + ($request->time_on_question ?? 0),
        ]);
        
        // Update attempt counters
        if (!$wasAnswered && $answer->is_answered) {
            $attempt->increment('answered_questions');
        } elseif ($wasAnswered && !$answer->is_answered) {
            $attempt->decrement('answered_questions');
        }
        
        // Log event
        $attempt->logEvent('question_answered', [
            'question_id' => $request->question_id,
            'time_spent' => $request->time_on_question,
        ]);
        
        return response()->json([
            'success' => true,
            'answered_questions' => $attempt->fresh()->answered_questions,
        ]);
    }
    
    /**
     * Flag question for review
     */
    public function flagQuestion(TestAttempt $attempt, Question $question)
    {
        $this->authorize('update', $attempt);
        
        $answer = $attempt->answers()
            ->where('question_id', $question->id)
            ->firstOrFail();
        
        $answer->update([
            'is_flagged_for_review' => !$answer->is_flagged_for_review,
        ]);
        
        return response()->json([
            'success' => true,
            'is_flagged' => $answer->is_flagged_for_review,
        ]);
    }
    
    /**
     * Submit test
     */
    public function submit(SubmitTestRequest $request, TestAttempt $attempt)
    {
        $this->authorize('update', $attempt);
        
        // Calculate time spent
        $timeSpent = (int) $attempt->started_at->diffInSeconds(now());

        // Atomically move in_progress -> submitted so concurrent submits can't both pass
        $claimed = TestAttempt::whereKey($attempt->id)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'time_spent' => $timeSpent,
            ]);

        if ($claimed === 0) {
            return response()->json(['error' => 'Test allaqachon topshirilgan'], 400);
        }

        $attempt->refresh();

        // Evaluate answers
        $this->evaluationService->evaluate($attempt);

        // Log event
        $attempt->logEvent('test_submitted', [
            'time_spent' => $timeSpent,
            'answered_questions' => $attempt->answered_questions,
        ]);
        
        // Award XP if passed
        if ($attempt->is_passed && !$attempt->xp_awarded) {
            $xpAmount = $this->xpService->awardTestXP($attempt->user, $attempt->score);
            $attempt->user->studentProfile->increment('tests_passed');
            
            $attempt->update([
                'xp_awarded' => true,
                'xp_amount' => $xpAmount,
            ]);

            // daily "1 ta testdan o'ting" and "Imtihonchi" / "Mukammal"
            app(\App\Services\GamificationProgress::class)->record($attempt->user, 'test_pass', ['score' => (float) $attempt->score]);
        }

        // passing the test of a quiz-type lesson completes that lesson (and moves course progress)
        if ($attempt->is_passed) {
            $this->completeQuizLesson($attempt);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('student.tests.result', $attempt),
        ]);
    }
    
    /**
     * Log anti-cheat events
     */
    public function logEvent(Request $request, TestAttempt $attempt)
    {
        $this->authorize('update', $attempt);
        
        $request->validate([
            'event_type' => ['required', 'in:tab_switch,fullscreen_exit,copy_attempt,paste_attempt,right_click'],
        ]);
        
        $attempt->logEvent($request->event_type, $request->only('details'));
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Show result page
     */
    public function result(TestAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        
        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.tests.attempt', $attempt);
        }
        
        $attempt->load(['test', 'course']);
        
        // Get test settings for pass rate
        $passRate = $attempt->test->passing_score;
        
        return Inertia::render('Student/Tests/Result', [
            'attempt' => $attempt,
            'test' => $attempt->test,
            'course' => $attempt->course,
            'passRate' => $passRate,
        ]);
    }
    
    /**
     * Review answers
     */
    public function review(TestAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        
        // Check if review is allowed
        if (!$attempt->test->show_correct_answers) {
            // back() lost the message when the link was opened directly
            return redirect()->route('student.tests.result', $attempt)
                ->with('info', "Ustoz bu test uchun javoblar tahlilini yopgan.");
        }
        
        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.tests.attempt', $attempt);
        }
        
        $attempt->load(['test', 'course']);
        
        // Get answers with correct answers
        $answers = $attempt->answers()
            ->with(['question.options'])
            ->get()
            ->map(function ($answer) {
                $question = $answer->question;
                
                return [
                    'id' => $answer->id,
                    'question' => [
                        'id' => $question->id,
                        'type' => $question->type,
                        'content' => $question->question_text,
                        'options' => $question->options,
                        'explanation' => $question->explanation,
                    ],
                    'user_answer' => $answer->answer,
                    'correct_answer' => $answer->correct_answer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'max_points' => $answer->max_points,
                ];
            });
        
        return Inertia::render('Student/Tests/Review', [
            'attempt' => $attempt,
            'test' => $attempt->test,
            'course' => $attempt->course,
            'answers' => $answers,
        ]);
    }

    private function completeQuizLesson(TestAttempt $attempt): void
    {
        if ($attempt->enrollment) {
            app(\App\Services\ProgressTrackingService::class)->completePassedQuizLessons($attempt->enrollment);
        }
    }
}
