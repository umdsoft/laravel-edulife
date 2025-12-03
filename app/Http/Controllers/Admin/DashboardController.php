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
        // Get stats
        $stats = [
            'users' => [
                'total' => User::count(),
                'trend' => 12, // Placeholder - calculate actual trend
            ],
            'teachers' => [
                'total' => User::where('role', 'teacher')->count(),
                'trend' => 5,
            ],
            'courses' => [
                'total' => Course::count(),
                'trend' => 8,
            ],
            'revenue' => [
                'total' => Payment::where('status', 'completed')->sum('amount'),
                'trend' => 15,
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
}
