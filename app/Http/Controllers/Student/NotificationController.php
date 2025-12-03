<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications();
        
        // Filter by type
        if ($request->type) {
            $query->where('type', 'like', "%{$request->type}%");
        }
        
        // Filter by read status
        if ($request->status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->status === 'read') {
            $query->whereNotNull('read_at');
        }
        
        $notifications = $query->orderByDesc('created_at')->paginate(20);
        
        $unreadCount = $user->unreadNotifications()->count();
        
        return Inertia::render('Student/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['type', 'status']),
        ]);
    }
    
    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return back();
    }
    
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Barcha bildirishnomalar o\'qildi');
    }
    
    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        
        return back()->with('success', 'Bildirishnoma o\'chirildi');
    }
    
    public function destroyAll()
    {
        Auth::user()->notifications()->delete();
        
        return back()->with('success', 'Barcha bildirishnomalar o\'chirildi');
    }
}
