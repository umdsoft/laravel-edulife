<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request): Response
    {
        $period = $request->get('period', 'month'); // week, month, year

        return Inertia::render('Admin/Reports/Index', [
            'stats' => $this->getStats($period),
            'charts' => [
                'revenue' => $this->getRevenueChart($period),
                'users' => $this->getUsersChart($period),
                'courses' => $this->getCoursesChart(),
            ],
            'filters' => [
                'period' => $period,
            ],
        ]);
    }

    private function getStats(string $period): array
    {
        // Calculate date range based on period
        $startDate = match ($period) {
            'week' => now()->subWeek(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'period_revenue' => Payment::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'total_users' => User::count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'active_subscriptions' => DB::table('subscriptions')
                ->where('ends_at', '>', now())
                ->count(),
        ];
    }

    private function getRevenueChart(string $period): array
    {
        $query = Payment::where('status', 'completed');
        
        if ($period === 'year') {
            $data = $query->where('created_at', '>=', now()->subYear())
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $days = $period === 'week' ? 7 : 30;
            $data = $query->where('created_at', '>=', now()->subDays($days))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        return [
            'labels' => $data->pluck('date'),
            'data' => $data->pluck('total'),
        ];
    }

    private function getUsersChart(string $period): array
    {
        $query = User::query();
        
        if ($period === 'year') {
            $data = $query->where('created_at', '>=', now()->subYear())
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $days = $period === 'week' ? 7 : 30;
            $data = $query->where('created_at', '>=', now()->subDays($days))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        return [
            'labels' => $data->pluck('date'),
            'data' => $data->pluck('count'),
        ];
    }

    private function getCoursesChart(): array
    {
        $data = Course::select('direction_id', DB::raw('count(*) as total'))
            ->with('direction')
            ->groupBy('direction_id')
            ->get();

        return [
            'labels' => $data->map(fn($item) => $item->direction->name ?? 'Unknown'),
            'data' => $data->pluck('total'),
        ];
    }
}
