<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherScoreService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RatingController extends Controller
{
    public function __construct(
        protected TeacherScoreService $scoreService
    ) {}
    
    public function index()
    {
        $teacher = Auth::user();
        $profile = $teacher->teacherProfile;
        
        $scoreData = $this->scoreService->calculateScore($teacher);
        $recommendations = $this->scoreService->getRecommendations($teacher);
        
        // Oxirgi 30 kunlik tarix
        $history = $teacher->scoreHistory()
            ->orderBy('calculated_date', 'desc')
            ->limit(30)
            ->get();
        
        // Level o'zgarishlari
        $levelChanges = $teacher->levelChanges()
            ->with('changedByUser')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Keyingi level uchun kerakli balllar
        $nextLevel = $this->getNextLevelInfo($scoreData['level'], $scoreData['score']);
        
        return Inertia::render('Teacher/Rating/Index', [
            'score' => $scoreData['score'],
            'level' => $scoreData['level'],
            'breakdown' => $scoreData['breakdown'],
            'recommendations' => $recommendations,
            'history' => $history,
            'levelChanges' => $levelChanges,
            'nextLevel' => $nextLevel,
            'commissionRate' => $profile->commission_rate,
        ]);
    }
    
    private function getNextLevelInfo(string $currentLevel, float $currentScore): ?array
    {
        $levels = [
            'new' => ['next' => 'verified', 'min_score' => 50, 'name' => 'Tasdiqlangan'],
            'verified' => ['next' => 'featured', 'min_score' => 75, 'name' => 'Tavsiya etilgan'],
            'featured' => ['next' => 'top', 'min_score' => 90, 'name' => 'Top'],
            'top' => null,
        ];
        
        $nextData = $levels[$currentLevel] ?? null;
        
        if (!$nextData) {
            return null;
        }
        
        return [
            'level' => $nextData['next'],
            'name' => $nextData['name'],
            'required_score' => $nextData['min_score'],
            'points_needed' => max(0, $nextData['min_score'] - $currentScore),
        ];
    }
}
