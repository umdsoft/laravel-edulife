<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        // Change over the last 30 days vs the 30 days before (null when there is no baseline)
        $users = User::query();
        $teachers = User::where('role', 'teacher');
        $payments = Payment::where('status', 'completed');

        $stats = [
            'users' => [
                'total' => User::count(),
                'trend' => $this->trend(clone $users, 'count'),
            ],
            'teachers' => [
                'total' => User::where('role', 'teacher')->count(),
                'trend' => $this->trend(clone $teachers, 'count'),
            ],
            'courses' => [
                'total' => Course::count(),
                'trend' => $this->trend(Course::query(), 'count'),
            ],
            'revenue' => [
                'total' => Payment::where('status', 'completed')->sum('amount'),
                'trend' => $this->trend(clone $payments, 'sum'),
            ],
        ];

        // Get recent users (latest 5)
        $recentUsers = User::select('id', 'first_name', 'last_name', 'phone', 'email', 'role', 'created_at')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => null, // Will add avatar logic later
                    'created_at' => $user->created_at->format('d.m.Y H:i'),
                ];
            });

        // Get recent payments (latest 5)
        $recentPayments = Payment::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user_name' => $payment->user ? $payment->user->first_name . ' ' . $payment->user->last_name : 'N/A',
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at->format('d.m.Y H:i'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentPayments' => $recentPayments,
        ]);
    }

    /**
     * Percent change of new rows (or their amount) in the last 30 days against
     * the previous 30 days; null when the previous period had nothing.
     */
    private function trend($query, string $aggregate): ?float
    {
        $value = fn ($q, $from, $to) => $aggregate === 'sum'
            ? (float) (clone $q)->whereBetween('created_at', [$from, $to])->sum('amount')
            : (float) (clone $q)->whereBetween('created_at', [$from, $to])->count();

        $current = $value($query, now()->subDays(30), now());
        $previous = $value($query, now()->subDays(60), now()->subDays(30));

        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
