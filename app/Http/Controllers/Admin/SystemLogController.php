<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class SystemLogController extends Controller
{
    /**
     * Display system logs.
     */
    public function index(Request $request): Response
    {
        $query = Activity::with('causer', 'subject')->latest();

        if ($search = $request->get('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($event = $request->get('event')) {
            $query->where('event', $event);
        }

        $logs = $query->paginate(20)
            ->withQueryString()
            ->through(fn ($log) => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'causer' => $log->causer ? $log->causer->name : 'System',
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'properties' => $log->properties,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Admin/SystemLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
