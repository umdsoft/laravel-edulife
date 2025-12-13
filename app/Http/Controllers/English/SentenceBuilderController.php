<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\SentenceBuilderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SentenceBuilderController extends Controller
{
    public function __construct(
        private SentenceBuilderService $service
    ) {}
    
    /**
     * Show levels page
     */
    public function index()
    {
        $levels = $this->service->getLevelsWithProgress(auth()->id());
        
        // Calculate totals for header
        $totalStars = collect($levels)->sum('stars_earned');
        $totalSentences = collect($levels)->sum('sentences_completed');
        
        return Inertia::render('English/Games/SentenceBuilder/Index', [
            'levels' => $levels,
            'totalStars' => $totalStars,
            'totalSentences' => $totalSentences,
        ]);
    }
    
    /**
     * Start game
     */
    public function play(int $levelNumber)
    {
        try {
            $sessionData = $this->service->startSession(
                auth()->id(),
                $levelNumber
            );
            
            return Inertia::render('English/Games/SentenceBuilder/Play', [
                'session' => $sessionData,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Check answer
     */
    public function checkAnswer(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'sentence_id' => 'required|string',
            'answer' => 'required|array',
            'hints_used' => 'required|integer|min:0',
        ]);
        
        try {
            $result = $this->service->checkAnswer(
                $validated['session_id'],
                $validated['sentence_id'],
                $validated['answer'],
                $validated['hints_used']
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * API: Complete session
     */
    public function complete(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);
        
        try {
            $result = $this->service->completeSession($validated['session_id']);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    /**
     * API: Get Hint
     */
    public function getHint(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'sentence_id' => 'required|string',
            'current_answer' => 'array',
        ]);
        
        try {
            $result = $this->service->getHint(
                $validated['session_id'],
                $validated['sentence_id'],
                $validated['current_answer'] ?? []
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

}
