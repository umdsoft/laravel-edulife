<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $wishlistItems = Wishlist::with(['course.teacher', 'course.direction'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(12);
        
        // Eager load additional data
        $wishlistItems->getCollection()->transform(function ($item) {
            $item->course->loadCount('enrollments');
            $item->course->loadAvg('reviews', 'rating');
            return $item;
        });
        
        return Inertia::render('Student/Wishlist/Index', [
            'wishlistItems' => $wishlistItems,
        ]);
    }
    
    public function toggle(Course $course)
    {
        $user = Auth::user();
        
        $existing = Wishlist::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        
        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Kurs istaklar ro\'yxatidan olib tashlandi');
        }
        
        Wishlist::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'price_when_added' => $course->price,
        ]);
        
        return back()->with('success', 'Kurs istaklar ro\'yxatiga qo\'shildi');
    }
}
