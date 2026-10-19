<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseView;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    /** Traffic-source buckets, in display order. */
    public const SOURCES = [
        'direct' => "To'g'ridan-to'g'ri",
        'internal' => 'Platforma ichida',
        'search' => 'Qidiruv tizimlari',
        'social' => 'Ijtimoiy tarmoqlar',
        'other' => 'Boshqa saytlar',
    ];

    private const COURSE_STATUSES = [
        'draft' => 'Qoralama',
        'pending' => 'Tekshiruvda',
        'approved' => 'Tasdiqlangan',
        'published' => 'Nashr qilingan',
        'rejected' => 'Rad etilgan',
    ];

    public function index()
    {
        $teacherId = Auth::id();
        $courseIds = Course::where('teacher_id', $teacherId)->pluck('id');

        $stats = [
            'total_views' => (int) Course::whereIn('id', $courseIds)->sum('views_count'),
            'total_enrollments' => Enrollment::whereIn('course_id', $courseIds)->count(),
            'completion_rate' => $this->completionRate(Enrollment::whereIn('course_id', $courseIds)),
            // seconds
            'total_watch_time' => (int) LessonProgress::whereIn('course_id', $courseIds)->sum('watch_time'),
        ];

        $coursePerformance = $this->coursePerformance($teacherId, onlyPublished: true);

        // Daily enrollments (last 30 days), one grouped query
        $from = now()->subDays(29)->startOfDay();
        $perDay = Enrollment::whereIn('course_id', $courseIds)
            ->where('created_at', '>=', $from)
            ->get(['created_at'])
            ->countBy(fn ($e) => $e->created_at->format('Y-m-d'));

        $dailyEnrollments = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyEnrollments[] = [
                'date' => $date->format('d.m'),
                'count' => (int) ($perDay[$date->format('Y-m-d')] ?? 0),
            ];
        }

        return Inertia::render('Teacher/Analytics/Index', [
            'stats' => $stats,
            'coursePerformance' => $coursePerformance,
            'dailyEnrollments' => $dailyEnrollments,
            'trafficSources' => $this->trafficSources($courseIds),
        ]);
    }

    /**
     * CSV (Excel-friendly: UTF-8 BOM, semicolon separated) of every course's numbers.
     */
    public function export(): StreamedResponse
    {
        $rows = $this->coursePerformance(Auth::id(), onlyPublished: false);
        $filename = 'statistika-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kurs', 'Holat', "O'quvchilar", 'Faol', 'Tugatganlar', 'Tugatish (%)', 'Reyting', 'Sharhlar', "Ko'rishlar"], ';');
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['title'],
                    self::COURSE_STATUSES[$row['status']] ?? $row['status'],
                    $row['enrollments'],
                    $row['active_students'],
                    $row['completed'],
                    $row['completion_rate'],
                    $row['rating'],
                    $row['reviews'],
                    $row['views'],
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Bucket a Referer header into one of SOURCES.
     */
    public static function classifyReferer(?string $referer, ?string $appHost = null): string
    {
        $host = $referer ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '';
        if ($host === '') {
            return 'direct';
        }

        $host = preg_replace('/^(www\.|m\.)/', '', $host);
        $appHost = $appHost ?? strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($host === $appHost || $host === request()->getHost() || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'internal';
        }

        $matches = fn (array $domains) => collect($domains)->contains(
            fn ($domain) => $host === $domain || str_ends_with($host, '.'.$domain) || str_starts_with($host, $domain.'.')
        );

        if ($matches(['google', 'yandex', 'bing.com', 'duckduckgo.com', 'yahoo.com', 'baidu.com', 'search.brave.com'])) {
            return 'search';
        }
        if ($matches(['t.me', 'telegram.org', 'web.telegram.org', 'instagram.com', 'facebook.com', 'fb.com', 'youtube.com', 'youtu.be', 'tiktok.com', 'vk.com', 'ok.ru', 'twitter.com', 'x.com', 'linkedin.com'])) {
            return 'social';
        }

        return 'other';
    }

    /**
     * Where course-page views came from in the last 30 days (course_views.referer).
     */
    private function trafficSources(Collection $courseIds): array
    {
        $counts = array_fill_keys(array_keys(self::SOURCES), 0);

        CourseView::whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->select('referer', DB::raw('COUNT(*) as views'))
            ->groupBy('referer')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[self::classifyReferer($row->referer)] += (int) $row->views;
            });

        $total = array_sum($counts);
        if ($total === 0) {
            return [];
        }

        return collect($counts)
            ->filter()
            ->map(fn ($count, $key) => [
                'key' => $key,
                'source' => self::SOURCES[$key],
                'count' => $count,
                'percent' => (int) round($count / $total * 100),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function coursePerformance(string $teacherId, bool $onlyPublished): Collection
    {
        return Course::where('teacher_id', $teacherId)
            ->when($onlyPublished, fn ($q) => $q->where('status', 'published'))
            ->withCount([
                'enrollments',
                'enrollments as active_count' => fn ($q) => $q->where('status', 'active'),
                'enrollments as completed_count' => fn ($q) => $q->where('status', 'completed'),
                'reviews',
            ])
            ->withRating()
            ->orderBy('title')
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'status' => $course->status,
                'enrollments' => $course->enrollments_count,
                'active_students' => $course->active_count,
                'completed' => $course->completed_count,
                'completion_rate' => $course->enrollments_count > 0
                    ? (int) round($course->completed_count / $course->enrollments_count * 100)
                    : 0,
                'rating' => round((float) ($course->reviews_avg_rating ?? 0), 1),
                'reviews' => $course->reviews_count,
                'views' => (int) $course->views_count,
            ]);
    }

    private function completionRate($query): int
    {
        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        return (int) round((clone $query)->where('status', 'completed')->count() / $total * 100);
    }
}
