<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\ActivityFeed;
use App\Services\ActivityFeedService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FriendController extends Controller
{
    public function __construct(
        protected ActivityFeedService $activityService
    ) {}
    
    public function index()
    {
        $user = Auth::user();
        
        // Following activity feed
        $followingIds = $user->following()->pluck('following_id');
        
        $activities = ActivityFeed::whereIn('user_id', $followingIds)
            ->where('is_public', true)
            ->with('user')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();
        
        $stats = [
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
        ];
        
        return Inertia::render('Student/Friends/Index', [
            'activities' => $activities,
            'stats' => $stats,
        ]);
    }
    
    public function followers()
    {
        $user = Auth::user();
        
        $followers = $user->followers()
            ->with('studentProfile')
            ->orderByDesc('user_follows.followed_at')
            ->paginate(20);
        
        // Add isFollowing flag
        $followingIds = $user->following()->pluck('following_id')->toArray();
        $followers->getCollection()->transform(function ($follower) use ($followingIds) {
            $follower->is_following = in_array($follower->id, $followingIds);
            return $follower;
        });
        
        return Inertia::render('Student/Friends/Followers', [
            'followers' => $followers,
        ]);
    }
    
    public function following()
    {
        $user = Auth::user();
        
        $following = $user->following()
            ->with('studentProfile')
            ->orderByDesc('user_follows.followed_at')
            ->paginate(20);
        
        return Inertia::render('Student/Friends/Following', [
            'following' => $following,
        ]);
    }
    
    public function follow(User $user)
    {
        $authUser = Auth::user();
        
        if ($authUser->id === $user->id) {
            return back()->with('error', 'O\'zingizni kuzatib bo\'lmaydi');
        }
        
        if ($authUser->isFollowing($user)) {
            return back()->with('error', 'Siz allaqachon kuzatmoqdasiz');
        }
        
        UserFollow::create([
            'follower_id' => $authUser->id,
            'following_id' => $user->id,
            'followed_at' => now(),
        ]);
        
        return back()->with('success', "{$user->first_name} ni kuzatishni boshladingiz");
    }
    
    public function unfollow(User $user)
    {
        $authUser = Auth::user();
        
        UserFollow::where('follower_id', $authUser->id)
            ->where('following_id', $user->id)
            ->delete();
        
        return back()->with('success', "Kuzatishni to'xtatdingiz");
    }
}
