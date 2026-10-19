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
        // XP, level and coins are kept on student_profiles (CoinService / XP rewards);
        // the users.xp_total / level / coin_balance columns are legacy and never updated
        $profile = $user && $user->role === 'student' ? $user->studentProfile : null;

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
                    'role' => $user->role,
                    'xp_total' => $profile ? (int) $profile->xp : (int) $user->xp_total,
                    'level' => $profile ? (int) $profile->level : (int) $user->level,
                    'coin_balance' => $profile ? (int) $profile->coins : (int) $user->coin_balance,
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
        ];
    }
}