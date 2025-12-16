<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\VerbConjugatorDataService;
use App\Services\English\VerbConjugatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerbConjugatorController extends Controller
{
    protected VerbConjugatorDataService $dataService;
    protected VerbConjugatorService $gameService;

    public function __construct(VerbConjugatorDataService $dataService, VerbConjugatorService $gameService)
    {
        $this->dataService = $dataService;
        $this->gameService = $gameService;
    }

    /**
     * Display the game index page
     */
    public function index()
    {
        $levels = $this->dataService->getLevelsWithStatus();
        $stats = $this->dataService->getUserStats();
        $config = $this->dataService->getConfig();
        $achievements = $this->dataService->getUserAchievements();
        $gameModes = $this->dataService->getGameModes();
        $categories = $this->dataService->getCategories();
        $totalStars = $this->dataService->getTotalStars();
        $conjugationMasterRanks = $this->dataService->getConjugationMasterRanks();
        $tenses = $this->dataService->getTenses();

        return Inertia::render('English/Games/VerbConjugator/Index', [
            'levels' => $levels,
            'stats' => $stats,
            'config' => $config,
            'achievements' => $achievements,
            'gameModes' => $gameModes,
            'categories' => $categories,
            'totalStars' => $totalStars,
            'conjugationMasterRanks' => $conjugationMasterRanks,
            'tenses' => $tenses,
        ]);
    }

    /**
     * Display the play page for a specific level
     */
    public function play(int $level)
    {
        $levelData = $this->dataService->getLevel($level);

        if (!$levelData) {
            return redirect()->route('student.english.games.verb-conjugator.index')
                ->with('error', 'Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($level)) {
            return redirect()->route('student.english.games.verb-conjugator.index')
                ->with('error', 'Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $this->dataService->getGameModes();
        $powerups = $this->dataService->getPowerups();
        $tenses = $this->dataService->getTenses();
        $pronouns = $this->dataService->getPronouns();

        return Inertia::render('English/Games/VerbConjugator/Play', [
            'level' => $levelData,
            'config' => $config,
            'gameModes' => $gameModes,
            'powerups' => $powerups,
            'tenses' => $tenses,
            'pronouns' => $pronouns,
        ]);
    }

    /**
     * Start a new game session
     */
    public function startSession(Request $request, int $level)
    {
        $request->validate([
            'mode' => 'nullable|string',
        ]);

        try {
            $mode = $request->input('mode', 'classic');
            $session = $this->gameService->startSession($level, $mode);

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'question_index' => 'required|integer|min:0',
            'answer' => 'required|string',
            'time_spent' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->gameService->submitAnswer(
                $request->input('session_id'),
                $request->input('question_index'),
                $request->input('answer'),
                $request->input('time_spent')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Use a powerup
     */
    public function usePowerup(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'powerup_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->usePowerup(
                $request->input('session_id'),
                $request->input('powerup_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete a session
     */
    public function completeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $result = $this->gameService->completeSession($request->input('session_id'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current session state
     */
    public function getSessionState(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $state = $this->gameService->getSessionState($request->input('session_id'));

        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $state,
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        $stats = $this->dataService->getUserStats();
        $achievements = $this->dataService->getUserAchievements();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'achievements' => $achievements,
            ],
        ]);
    }

    /**
     * Get tense information
     */
    public function getTenseInfo(string $tenseId)
    {
        $tenses = $this->dataService->getTenses();
        $tense = null;

        foreach ($tenses as $t) {
            if ($t['id'] === $tenseId) {
                $tense = $t;
                break;
            }
        }

        if (!$tense) {
            return response()->json([
                'success' => false,
                'message' => 'Tense not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tense' => $tense,
            ],
        ]);
    }

    /**
     * Get verb conjugation table
     */
    public function getVerbConjugation(Request $request)
    {
        $request->validate([
            'verb_base' => 'required|string',
        ]);

        $verbs = $this->dataService->getVerbs();
        $verbBase = $request->input('verb_base');
        $verb = null;

        foreach ($verbs['all'] as $v) {
            if ($v['base'] === $verbBase) {
                $verb = $v;
                break;
            }
        }

        if (!$verb) {
            return response()->json([
                'success' => false,
                'message' => 'Verb not found',
            ], 404);
        }

        $tenses = $this->dataService->getTenses();
        $pronouns = $this->dataService->getPronouns();
        $conjugations = [];

        foreach ($tenses as $tense) {
            $conjugations[$tense['id']] = [
                'name' => $tense['name'],
                'forms' => [],
            ];

            foreach ($pronouns as $pronoun) {
                $conjugations[$tense['id']]['forms'][$pronoun['id']] = $this->dataService->conjugateVerb(
                    $verb,
                    $tense['id'],
                    $pronoun['id']
                );
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'verb' => $verb,
                'conjugations' => $conjugations,
            ],
        ]);
    }
}
