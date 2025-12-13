<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\DictationService;
use App\Services\English\DictationDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DictationController extends Controller
{
    protected DictationService $dictationService;
    protected DictationDataService $dataService;

    public function __construct(DictationService $dictationService, DictationDataService $dataService)
    {
        $this->dictationService = $dictationService;
        $this->dataService = $dataService;
    }

    /**
     * Display the game index page with levels
     */
    public function index()
    {
        $userId = Auth::id();
        $levels = $this->dataService->getLevels($userId);
        $config = $this->dataService->getConfig();
        $stats = $this->dataService->getUserStats($userId);

        return Inertia::render('English/Games/Dictation/Index', [
            'levels' => $levels,
            'config' => $config,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the game play page
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('english.games.dictation.index')
                ->with('error', 'Daraja topilmadi');
        }

        $config = $this->dataService->getConfig();

        return Inertia::render('English/Games/Dictation/Play', [
            'level' => $levelData,
            'config' => $config,
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $userId = Auth::id();
        $result = $this->dictationService->startSession($userId, $level);

        return response()->json($result);
    }

    /**
     * Check user's answer
     */
    public function checkAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required|string',
        ]);

        $result = $this->dictationService->checkAnswer(
            $request->session_id,
            $request->answer
        );

        return response()->json($result);
    }

    /**
     * Use hint
     */
    public function useHint(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->dictationService->useHint($request->session_id);

        return response()->json($result);
    }

    /**
     * Record replay
     */
    public function recordReplay(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->dictationService->recordReplay($request->session_id);

        return response()->json($result);
    }

    /**
     * Skip current item
     */
    public function skipItem(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $result = $this->dictationService->skipItem($request->session_id);

        return response()->json($result);
    }

    /**
     * Complete the session and get results
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $userId = Auth::id();
        $result = $this->dictationService->completeSession($request->session_id, $userId);

        return response()->json($result);
    }

    /**
     * Get current session state
     */
    public function getSessionState(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $session = $this->dictationService->getSession($request->session_id);

        if (!$session) {
            return response()->json(['success' => false, 'error' => 'Session not found']);
        }

        return response()->json([
            'success' => true,
            'current_index' => $session['current_index'],
            'total_items' => $session['total_items'],
            'score' => $session['score'],
            'streak' => $session['streak'],
            'correct_count' => $session['correct_count'],
            'completed' => $session['completed'],
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        $userId = Auth::id();
        $stats = $this->dataService->getUserStats($userId);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
