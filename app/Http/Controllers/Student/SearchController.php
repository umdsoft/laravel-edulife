<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Direction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return Inertia::render('Student/Search/Index', [
                'query' => '',
                'courses' => [],
                'teachers' => [],
                'directions' => Direction::withCount(['courses' => fn($q) => $q->where('status', 'published')])->get(),
            ]);
        }
        
        // Search courses
        $courses = Course::with(['teacher', 'direction'])
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereHas('tags', fn($q2) => $q2->where('name', 'like', "%{$query}%"));
            })
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('enrollments_count')
            ->paginate(12);
        
        // Search teachers
        $teachers = User::where('role', 'teacher')
            ->whereHas('teacherProfile')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->with('teacherProfile')
            ->withCount(['courses' => fn($q) => $q->where('status', 'published')])
            ->limit(5)
            ->get();
        
        return Inertia::render('Student/Search/Index', [
            'query' => $query,
            'courses' => $courses,
            'teachers' => $teachers,
            'directions' => Direction::withCount(['courses' => fn($q) => $q->where('status', 'published')])->get(),
        ]);
    }
    
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $courses = Course::where('status', 'published')
            ->where('title', 'like', "%{$query}%")
            ->select('id', 'title', 'slug')
            ->limit(5)
            ->get();
        
        return response()->json($courses);
    }
}
