<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification as AppNotification;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * Support desk: students' tickets (Student → Yordam) were stored but no one
 * could read or answer them.
 */
class SupportController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'waiting_user', 'resolved', 'closed'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : '';
        $search = trim((string) $request->query('search', ''));

        $tickets = SupportTicket::with('user:id,first_name,last_name,avatar')
            ->withCount('messages')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('subject', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('ticket_number', 'like', '%'.addcslashes($search, '%_\\').'%')))
            // unanswered first, then the most recently updated
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'waiting_user' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $counts = SupportTicket::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n);

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'counts' => $counts,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'user:id,first_name,last_name,avatar,phone,email,role',
            'messages' => fn ($q) => $q->orderBy('created_at'),
            'messages.user:id,first_name,last_name,avatar,role',
        ]);
        $ticket->user?->makeVisible(['phone', 'email']);

        $ticket->messages()->where('is_from_support', false)->where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);

        return Inertia::render('Admin/Support/Show', [
            'ticket' => $ticket,
            'statuses' => collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => (new SupportTicket(['status' => $s]))->status_label]),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:5000'],
            'is_internal_note' => ['boolean'],
        ]);
        $internal = (bool) ($data['is_internal_note'] ?? false);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'is_from_support' => true,
            'is_internal_note' => $internal,
        ]);

        if (! $internal) {
            $ticket->update([
                'status' => 'waiting_user',
                'first_response_at' => $ticket->first_response_at ?? now(),
                'assigned_to' => $ticket->assigned_to ?? $request->user()->id,
            ]);

            if ($ticket->user) {
                AppNotification::forceCreate([
                    'user_id' => $ticket->user_id,
                    'type' => 'support_reply',
                    'title' => "So'rovingizga javob berildi",
                    'body' => $ticket->subject,
                    'action_url' => Route::has('student.support.show') ? route('student.support.show', $ticket, false) : null,
                    'priority' => 'normal',
                ]);
            }
        }

        $ticket->touch();

        return back()->with('success', $internal ? 'Ichki eslatma saqlandi' : 'Javob yuborildi');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', self::STATUSES)]]);

        $ticket->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : $ticket->resolved_at,
            'closed_at' => $data['status'] === 'closed' ? now() : $ticket->closed_at,
        ]);

        return back()->with('success', 'Holat yangilandi');
    }
}
