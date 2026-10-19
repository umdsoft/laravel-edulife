<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'filter' => $request->query('filter') === 'pending' ? 'pending' : '',
            'rating' => in_array((int) $request->query('rating'), [1, 2, 3, 4, 5], true) ? (int) $request->query('rating') : '',
        ];

        $reviews = Review::with(['user', 'course'])
            ->whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
            ->when($filters['filter'] === 'pending', fn ($q) => $q->whereNull('teacher_reply'))
            ->when($filters['rating'] !== '', fn ($q) => $q->where('rating', $filters['rating']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Stats
        $stats = [
            'total_reviews' => Review::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))->count(),
            'avg_rating' => Review::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))->avg('rating'),
            'pending_replies' => Review::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
                ->whereNull('teacher_reply')
                ->count(),
        ];
        
        // Rating distribution
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingDistribution[$i] = Review::whereHas('course', fn($q) => $q->where('teacher_id', Auth::id()))
                ->where('rating', $i)
                ->count();
        }
        
        return Inertia::render('Teacher/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'ratingDistribution' => $ratingDistribution,
            'filters' => $filters,
        ]);
    }
    
    public function reply(Request $request, Review $review)
    {
        $this->authorize('reply', $review);
        
        $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);
        
        $review->update([
            'teacher_reply' => $request->reply,
            'teacher_replied_at' => now(),
        ]);
        
        return back()->with('success', 'Javob yuborildi');
    }
}
