<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use App\Models\Notification as AppNotification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Quick broadcast form (sidebar: Tizim -> Xabarnomalar).
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Notifications/Index', [
            'templates' => NotificationTemplate::where('is_active', true)->get(),
        ]);
    }

    /**
     * Display the send notification page.
     */
    public function showSend(): Response
    {
        return Inertia::render('Admin/Notifications/Send', [
            'templates' => NotificationTemplate::where('is_active', true)->get(),
        ]);
    }

    /**
     * Display the templates page.
     */
    public function templates(): Response
    {
        return Inertia::render('Admin/Notifications/Templates', [
            'templates' => NotificationTemplate::latest()->paginate(10),
        ]);
    }

    /**
     * Send a notification to users.
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:all,teachers,students,specific',
            'user_ids' => 'required_if:type,specific|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $recipients = User::query()
            ->where('status', 'active')
            ->when($request->type === 'teachers', fn ($q) => $q->where('role', 'teacher'))
            ->when($request->type === 'students', fn ($q) => $q->where('role', 'student'))
            ->when($request->type === 'specific', fn ($q) => $q->whereIn('id', $request->user_ids));

        // One row per recipient in the app's notifications table (the bell reads it),
        // inserted in batches so "all users" stays cheap
        $sent = 0;
        $recipients->select('id')->chunkById(500, function ($users) use ($request, &$sent) {
            $now = now();
            AppNotification::insert($users->map(fn ($user) => [
                'id' => (string) Str::uuid7(),
                'user_id' => $user->id,
                'type' => 'system',
                'title' => $request->title,
                'body' => $request->message,
                'icon' => 'bell',
                'priority' => 'normal',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
            $sent += $users->count();
        });

        if ($sent === 0) {
            return back()->with('warning', 'Tanlangan guruhda faol foydalanuvchi yo\'q — xabar yuborilmadi.');
        }

        return back()->with('success', $sent.' ta foydalanuvchiga xabar yuborildi!');
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:all,teachers,students',
        ]);

        NotificationTemplate::create($request->only(['name', 'title', 'message', 'type']));

        return back()->with('success', 'Shablon yaratildi');
    }

    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:all,teachers,students',
            'is_active' => 'boolean',
        ]);

        $template->update($request->only(['name', 'title', 'message', 'type', 'is_active']));

        return back()->with('success', 'Shablon yangilandi');
    }

    public function destroyTemplate(NotificationTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Shablon o\'chirildi');
    }
}
