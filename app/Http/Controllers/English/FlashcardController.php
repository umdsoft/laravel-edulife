<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\FlashcardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FlashcardController extends Controller
{
    private FlashcardService $service;
    
    public function __construct(FlashcardService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Show levels page
     */
    public function index()
    {
        $userId = auth()->id();
        $levels = $this->service->getLevelsWithProgress($userId);
        $totalStars = $this->service->getTotalStars($userId);
        $maxStars = $this->service->getMaxStars();
        
        return Inertia::render('English/Games/Flashcard/Index', [
            'levels' => $levels,
            'totalStars' => $totalStars,
            'maxStars' => $maxStars,
        ]);
    }
    
    /**
     * Start learning session (play page)
     */
    public function play(Request $request, int $level)
    {
        $userId = auth()->id();
        $category = $request->query('category');
        
        try {
            $sessionData = $this->service->startSession($userId, $level, $category);
            
            return Inertia::render('English/Games/Flashcard/Play', [
                'session' => $sessionData,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Record card response
     */
    public function recordResponse(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'card_id' => 'required|string',
            'response' => 'required|in:again,hard,good,easy',
            'response_time_ms' => 'required|integer',
        ]);
        
        try {
            $result = $this->service->recordResponse(
                $validated['session_id'],
                $validated['card_id'],
                $validated['response'],
                $validated['response_time_ms']
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
     * API: Abandon session
     */
    public function abandon(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);
        
        $result = $this->service->abandonSession($validated['session_id']);
        return response()->json($result);
    }
}
