<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->latest()
            ->paginate(30);
        
        $unreadCount = $user->unreadNotifications()->count();
        
        return Inertia::render('Teacher/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
    
    public function markAsRead(string $notification)
    {
        $notification = Auth::user()->notifications()->findOrFail($notification);
        $notification->markAsRead();
        
        return back();
    }
    
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Barcha bildirishnomalar o\'qildi deb belgilandi');
    }
}
