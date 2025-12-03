<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\TeacherScoreHistory;
use App\Models\TeacherLevelChange;
use App\Services\TeacherScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeacherRatingController extends Controller
{
    public function __construct(
        protected TeacherScoreService $scoreService
    ) {}
    
    public function index(Request $request)
    {
        $teachers = User::where('role', 'teacher')
            ->whereHas('teacherProfile')
            ->with('teacherProfile')
            ->when($request->level, fn($q, $level) => 
                $q->whereHas('teacherProfile', fn($q2) => $q2->where('level', $level))
            )
            ->when($request->search, fn($q, $search) => 
                $q->where(fn($q2) => 
                    $q2->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->orderByDesc(
                TeacherProfile::select('score')
                    ->whereColumn('teacher_profiles.user_id', 'users.id')
            )
            ->paginate(20);
        
        // Stats
        $stats = [
            'total' => User::where('role', 'teacher')->count(),
            'new' => TeacherProfile::where('level', 'new')->count(),
            'verified' => TeacherProfile::where('level', 'verified')->count(),
            'featured' => TeacherProfile::where('level', 'featured')->count(),
            'top' => TeacherProfile::where('level', 'top')->count(),
        ];
        
        return Inertia::render('Admin/TeacherRatings/Index', [
            'teachers' => $teachers,
            'stats' => $stats,
            'filters' => $request->only(['level', 'search']),
        ]);
    }
    
    public function show(User $teacher)
    {
        // $this->authorize('viewAny', User::class);
        
        $scoreData = $this->scoreService->calculateScore($teacher);
        
        $history = $teacher->scoreHistory()
            ->orderBy('calculated_date', 'desc')
            ->limit(90)
            ->get();
        
        $levelChanges = $teacher->levelChanges()
            ->with('changedByUser')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('Admin/TeacherRatings/Show', [
            'teacher' => $teacher->load('teacherProfile'),
            'score' => $scoreData['score'],
            'level' => $scoreData['level'],
            'breakdown' => $scoreData['breakdown'],
            'history' => $history,
            'levelChanges' => $levelChanges,
        ]);
    }
    
    public function overrideLevel(Request $request, User $teacher)
    {
        // $this->authorize('update', $teacher);
        
        $request->validate([
            'level' => ['required', 'in:new,verified,featured,top'],
            'reason' => ['required', 'string', 'min:10', 'max:255'],
        ]);
        
        $this->scoreService->adminOverrideLevel(
            $teacher,
            $request->level,
            $request->reason,
            Auth::user()
        );
        
        return back()->with('success', 'O\'qituvchi darajasi o\'zgartirildi');
    }
    
    public function recalculate(User $teacher)
    {
        // $this->authorize('update', $teacher);
        
        $this->scoreService->updateTeacherScore($teacher);
        
        return back()->with('success', 'Score qayta hisoblandi');
    }
    
    public function recalculateAll()
    {
        // $this->authorize('viewAny', User::class);
        
        dispatch(new \App\Jobs\CalculateAllTeacherScoresJob());
        
        return back()->with('success', 'Barcha o\'qituvchilar uchun score hisoblash boshlandi');
    }
}
