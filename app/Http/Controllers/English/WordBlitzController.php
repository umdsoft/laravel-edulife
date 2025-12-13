<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\WordBlitzService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class WordBlitzController extends Controller
{
    public function __construct(
        private WordBlitzService $service
    ) {}
    
    /**
     * Show levels page
     */
    public function index(): Response
    {
        $userId = auth()->id();
        $levels = $this->service->getLevelsWithProgress($userId);
        $totalStars = $this->service->getTotalStars($userId);
        
        return Inertia::render('English/Games/WordBlitz/Index', [
            'levels' => $levels,
            'totalStars' => $totalStars,
        ]);
    }
    
    /**
     * Start game and show play page
     */
    public function play(int $level): Response|JsonResponse
    {
        try {
            $gameData = $this->service->startGame(auth()->id(), $level);
            
            return Inertia::render('English/Games/WordBlitz/Play', [
                'game' => $gameData,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Check answer
     */
    public function checkAnswer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'word_id' => 'required|integer',
            'answer' => 'required|string|max:50',
            'response_time_ms' => 'required|integer|min:0',
        ]);
        
        try {
            $result = $this->service->checkAnswer(
                $validated['session_id'],
                $validated['word_id'],
                $validated['answer'],
                $validated['response_time_ms']
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * API: Skip word
     */
    public function skipWord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'word_id' => 'required|integer',
        ]);
        
        try {
            $result = $this->service->skipWord(
                $validated['session_id'],
                $validated['word_id']
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * API: Complete game
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);
        
        try {
            $result = $this->service->completeGame(
                $validated['session_id'],
                $validated['time_spent']
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * API: Abandon game
     */
    public function abandon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);
        
        $result = $this->service->abandonGame($validated['session_id']);
        
        return response()->json($result);
    }
}
