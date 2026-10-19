<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification as AppNotification;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()->with('studentProfile:id,user_id,coins,xp,level');

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
                // students' balance lives on student_profiles (users.coin_balance is legacy)
                'coin_balance' => (int) ($user->studentProfile?->coins ?? $user->coin_balance),
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
                'xp_total' => 0,
                'level' => 1,
                'coin_balance' => 0,
                'elo_rating' => 1000,
                'battles_won' => 0,
                'battles_total' => 0,
                'streak_current' => 0,
                'streak_best' => 0,
            ]);

            // created by an admin with a known phone: treat it as verified (not mass assignable)
            $user->forceFill(['phone_verified_at' => now()])->save();

            UserProfile::firstOrCreate(['user_id' => $user->id]);

            AuditLog::log('created', $user, [], ['role' => $user->role], "Foydalanuvchi yaratildi: {$user->full_name}");

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli yaratildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', "Foydalanuvchini yaratib bo'lmadi. Qayta urinib ko'ring.");
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load(['profile', 'enrollments.course', 'studentProfile']);

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
                'xp_total' => (int) ($user->studentProfile?->xp ?? $user->xp_total),
                'level' => (int) ($user->studentProfile?->level ?? $user->level),
                'coin_balance' => (int) ($user->studentProfile?->coins ?? $user->coin_balance),
                'created_at' => $user->created_at->format('d.m.Y H:i'),
                'enrollments_count' => $user->enrollments->count(),
                'completed_courses_count' => $user->enrollments()->where('progress', 100)->count(),
            ],
            // paid courses are opened by an admin after an offline payment
            'availableCourses' => $user->role === 'student'
                ? Course::where('status', 'published')
                    ->whereNotIn('id', $user->enrollments->pluck('course_id'))
                    ->orderBy('title')
                    ->get(['id', 'title', 'is_free', 'price'])
                : [],
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
        $this->ensureCanManage($user);

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
        $this->ensureCanManage($user);

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

            $before = $user->only(['first_name', 'last_name', 'phone', 'email', 'role', 'status']);
            $user->update($data);
            $changed = array_diff_key($user->getChanges(), array_flip(['password', 'updated_at']));
            if ($changed) {
                AuditLog::log('updated', $user, array_intersect_key($before, $changed), $changed, "Foydalanuvchi tahrirlandi: {$user->full_name}");
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli yangilandi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', "Foydalanuvchini yangilab bo'lmadi. Qayta urinib ko'ring.");
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'O\'zingizni o\'chira olmaysiz');
        }

        try {
            $user->delete();
            AuditLog::log('deleted', $user, [], [], "Foydalanuvchi o'chirildi: {$user->full_name}");

            return redirect()->route('admin.users.index')
                ->with('success', 'Foydalanuvchi muvaffaqiyatli o\'chirildi!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', "Foydalanuvchini o'chirib bo'lmadi.");
        }
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureCanManage($user);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'O\'zingizni bloklay olmaysiz');
        }

        $newStatus = $user->status === 'active' ? 'blocked' : 'active';
        $oldStatus = $user->status;
        $user->update(['status' => $newStatus]);
        AuditLog::log('updated', $user, ['status' => $oldStatus], ['status' => $newStatus],
            ($newStatus === 'blocked' ? 'Foydalanuvchi bloklandi: ' : 'Foydalanuvchi faollashtirildi: ').$user->full_name);

        return back()->with('success', 'Status muvaffaqiyatli o\'zgartirildi!');
    }

    /**
     * Give a student access to a course (e.g. after an offline payment for a paid course).
     */
    public function enroll(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'student') {
            return back()->with('error', "Faqat o'quvchini kursga yozish mumkin.");
        }

        $data = $request->validate([
            'course_id' => ['required', 'uuid', Rule::exists('courses', 'id')->where('status', 'published')],
        ]);
        $course = Course::findOrFail($data['course_id']);

        if ($user->isEnrolled($course)) {
            return back()->with('warning', 'Foydalanuvchi bu kursga allaqachon yozilgan.');
        }

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'total_lessons' => $course->lessons()->count(),
            'access_type' => $course->is_free ? 'free' : 'gift',
            'payment_status' => $course->is_free ? 'free' : 'manual',
            'paid_amount' => 0,
            'status' => 'active',
        ]);

        AuditLog::log('created', $course, [], ['student' => $user->full_name], "Kursga qo'lda yozildi: {$user->full_name} → {$course->title}");
        AppNotification::send($user, 'course_access', 'Kursga kirish ochildi', "«{$course->title}» kursi kurslaringizga qo'shildi.");

        return back()->with('success', "{$user->full_name} «{$course->title}» kursiga yozildi.");
    }

    /**
     * Admin-level accounts can only be managed by a super_admin.
     */
    private function ensureCanManage(User $target): void
    {
        if (in_array($target->role, ['admin', 'super_admin'], true)) {
            abort_unless(auth()->user()->role === 'super_admin', 403);
        }
    }
}
