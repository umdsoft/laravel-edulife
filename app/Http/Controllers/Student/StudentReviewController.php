<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Http\Requests\Student\StoreReviewRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentReviewController extends Controller
{
    public function create(Course $course)
    {
        $user = Auth::user();
        
        // Must be enrolled and have some progress
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('progress', '>=', 20) // At least 20% completed
            ->firstOrFail();
        
        // Check if already reviewed
        $existingReview = Review::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        
        return Inertia::render('Student/Reviews/Create', [
            'course' => $course->load('teacher'),
            'enrollment' => $enrollment,
            'existingReview' => $existingReview,
        ]);
    }
    
    public function store(StoreReviewRequest $request, Course $course)
    {
        $user = Auth::user();
        
        // Must be enrolled
        $user->enrollments()->where('course_id', $course->id)->firstOrFail();
        
        // Check if already reviewed
        $existingReview = Review::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        
        if ($existingReview) {
            return back()->with('error', 'Siz allaqachon sharh qoldirgansiz');
        }
        
        $review = Review::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);
        
        // Update course and teacher average rating
        $this->updateAverageRating($course);
        
        // XP for review
        $user->studentProfile->addXp(25);
        
        return redirect()->route('student.learn.course', $course)
            ->with('success', 'Sharh qoldirildi. Rahmat!');
    }
    
    public function update(StoreReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);
        
        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);
        
        $this->updateAverageRating($review->course);
        
        return back()->with('success', 'Sharh yangilandi');
    }
    
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);
        
        $course = $review->course;
        $review->delete();
        
        $this->updateAverageRating($course);
        
        return back()->with('success', 'Sharh o\'chirildi');
    }
    
    private function updateAverageRating(Course $course): void
    {
        $avgRating = $course->reviews()->avg('rating') ?? 0;
        $course->update(['avg_rating' => round($avgRating, 1)]);
        
        // Update teacher profile
        $teacher = $course->teacher;
        $teacherAvgRating = Review::whereHas('course', fn($q) => $q->where('teacher_id', $teacher->id))
            ->avg('rating') ?? 0;
        $teacher->teacherProfile->update(['avg_rating' => round($teacherAvgRating, 1)]);
    }
}
