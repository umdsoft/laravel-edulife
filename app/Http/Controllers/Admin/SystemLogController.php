<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System log viewer over audit_logs (logins, admin actions on users and
 * courses, settings changes). spatie/laravel-activitylog is not used.
 */
class SystemLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));
        $event = (string) $request->get('event', '');

        $logs = AuditLog::with('user:id,first_name,last_name')
            ->when($search !== '', fn ($q) => $q->where('description', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'causer' => $log->user?->full_name ?: 'System',
                'subject_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'subject_id' => $log->auditable_id,
                'properties' => array_filter([
                    'old' => $log->old_values ?: null,
                    'attributes' => $log->new_values ?: null,
                    'ip' => $log->ip_address,
                ]),
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Admin/SystemLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
