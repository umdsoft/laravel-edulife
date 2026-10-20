<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Exam\GuardianReport;
use App\Models\Exam\GuardianStudent;
use App\Models\Notification as AppNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sunday reports of the guardian's own (still approved) children.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $guardian = $request->user();

        $reports = GuardianReport::query()
            ->where('guardian_id', $guardian->id)
            ->whereIn('guardian_student_id', GuardianStudent::query()->verified()->where('guardian_id', $guardian->id)->select('id'))
            ->latest('week_start')
            ->paginate(20, ['id', 'week_start', 'payload', 'read_at'])
            ->through(fn (GuardianReport $r) => [
                'id' => $r->id,
                'week_label' => $r->payload['week_label'] ?? $r->week_start->format('d.m.Y'),
                'student' => $r->payload['student']['first_name'] ?? null,
                'participated' => (bool) ($r->payload['participated'] ?? false),
                'subjects' => array_map(fn ($s) => ['name' => $s['name'], 'percent' => $s['percent'], 'delta' => $s['delta']], $r->payload['subjects'] ?? []),
                'unread' => $r->read_at === null,
            ]);

        return Inertia::render('Guardian/Reports', ['reports' => $reports]);
    }

    public function show(Request $request, GuardianReport $report): Response
    {
        $guardian = $request->user();
        $allowed = $report->guardian_id === $guardian->id
            && GuardianStudent::query()->verified()->whereKey($report->guardian_student_id)->exists();
        abort_unless($allowed, 404);

        if ($report->read_at === null) {
            $report->forceFill(['read_at' => now()])->save();
            AppNotification::query()
                ->where('user_id', $guardian->id)
                ->where('type', 'exam_weekly_report')
                ->where('is_read', false)
                ->get()
                ->filter(fn (AppNotification $n) => ($n->data['report_id'] ?? null) === $report->id)
                ->each->markAsRead();
        }

        return Inertia::render('Guardian/Report', [
            'report' => ['id' => $report->id, 'student_id' => $report->student_id] + $report->payload,
        ]);
    }
}
