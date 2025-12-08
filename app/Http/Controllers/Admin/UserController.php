<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Paginate
        $users = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'coin_balance' => $user->coin_balance,
                'created_at' => $user->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => 'active',
                'phone_verified_at' => now(),
                'xp_total' => 0,
                'level' => 1,
                'coin_balance' => 0,
                'elo_rating' => 1000,
                'battles_won' => 0,
                'battles_total' => 0,
                'streak_current' => 0,
                'streak_best' => 0,
            ]);

            // Create user profile
            UserProfile::create([
                'user_id' => $user->id,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli yaratildi!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Foydalanuvchi yaratishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load(['profile', 'enrollments.course']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'xp_total' => $user->xp_total,
                'level' => $user->level,
                'coin_balance' => $user->coin_balance,
                'created_at' => $user->created_at->format('d.m.Y H:i'),
                'enrollments_count' => $user->enrollments->count(),
                'completed_courses_count' => $user->enrollments()->where('progress', 100)->count(),
            ],
            'enrollments' => $user->enrollments->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'course_title' => $enrollment->course->title ?? 'N/A',
                'progress' => $enrollment->progress,
                'started_at' => $enrollment->created_at->format('d.m.Y'),
            ]),
        ]);
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'role' => $request->role,
                'status' => $request->status,
            ];

            // Update password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli yangilandi!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Yangilashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli o\'chirildi!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'O\'chirishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $newStatus = $user->status === 'active' ? 'blocked' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', 'Status muvaffaqiyatli o\'zgartirildi!');
    }
}
