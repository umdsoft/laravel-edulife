<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        // XP, level and coins live only on student_profiles; the users.xp_total /
        // level / coin_balance columns are legacy and are never read or written
        $profile = $user && $user->role === 'student' ? $user->studentProfile : null;
        // the header avatar shows the shop avatar the student equipped (QA3-S-04)
        if ($profile) {
            $user->load('equippedAvatar.item');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role,
                    'xp_total' => (int) ($profile?->xp ?? 0),
                    'level' => (int) ($profile?->level ?? 1),
                    'coin_balance' => (int) ($profile?->coins ?? 0),
                ] : null,
                'teacher_profile' => fn() => $request->user() && $request->user()->hasRole('teacher') 
                    ? $request->user()->teacherProfile 
                    : null,
                'studentProfile' => fn() => $profile,
                'unread_notifications_count' => fn() => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            // --- exam: review queue badge in the teacher nav (stage 3) ---
            'exam_review_pending' => fn () => $user && in_array($user->role, ['teacher', 'admin', 'super_admin'], true)
                ? \App\Models\QuestionBank::query()->where('review_status', 'in_review')->whereNotNull('subject_id')->count()
                : null,
            // --- end exam: stage 3 ---
        ];
    }
}