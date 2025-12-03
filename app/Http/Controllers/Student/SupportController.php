<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Http\Requests\Student\CreateTicketRequest;
use App\Http\Requests\Student\ReplyTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $tickets = SupportTicket::where('user_id', $user->id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->paginate(10);
        
        $stats = [
            'open' => SupportTicket::where('user_id', $user->id)->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved' => SupportTicket::where('user_id', $user->id)->where('status', 'resolved')->count(),
            'total' => SupportTicket::where('user_id', $user->id)->count(),
        ];
        
        return Inertia::render('Student/Support/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
        ]);
    }
    
    public function faq()
    {
        $faqs = [
            [
                'category' => 'Hisob',
                'questions' => [
                    ['q' => 'Parolimni qanday o\'zgartiraman?', 'a' => 'Sozlamalar > Hisob bo\'limiga o\'ting va "Parolni o\'zgartirish" tugmasini bosing.'],
                    ['q' => 'Emailimni o\'zgartira olamanmi?', 'a' => 'Ha, Sozlamalar > Hisob bo\'limida email manzilni yangilashingiz mumkin.'],
                ],
            ],
            [
                'category' => 'Kurslar',
                'questions' => [
                    ['q' => 'Kursni qanday sotib olaman?', 'a' => 'Kurs sahifasida "Sotib olish" tugmasini bosing va to\'lov usulini tanlang.'],
                    ['q' => 'Kurs uchun qaytarish mumkinmi?', 'a' => 'Ha, sotib olganingizdan 14 kun ichida qaytarish so\'rashingiz mumkin.'],
                ],
            ],
            [
                'category' => 'Sertifikatlar',
                'questions' => [
                    ['q' => 'Sertifikatni qanday olaman?', 'a' => 'Kursni 100% tugatganingizda sertifikat avtomatik beriladi.'],
                    ['q' => 'Sertifikat haqiqiyligini tekshirish mumkinmi?', 'a' => 'Ha, sertifikat sahifasidagi verification link orqali tekshirish mumkin.'],
                ],
            ],
        ];
        
        return Inertia::render('Student/Support/Faq', [
            'faqs' => $faqs,
        ]);
    }
    
    public function create()
    {
        return Inertia::render('Student/Support/Create', [
            'categories' => [
                'technical' => 'Texnik muammo',
                'payment' => 'To\'lov',
                'course' => 'Kurs haqida',
                'account' => 'Hisob',
                'other' => 'Boshqa',
            ],
        ]);
    }
    
    public function store(CreateTicketRequest $request)
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $request->subject,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority ?? 'medium',
        ]);
        
        // Create first message
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $request->description,
        ]);
        
        return redirect()->route('student.support.show', $ticket)
            ->with('success', 'So\'rovingiz yuborildi. Tez orada javob beramiz.');
    }
    
    public function show(SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }
        
        $ticket->load(['messages.user']);
        
        // Mark messages as read
        $ticket->messages()
            ->where('is_from_support', true)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        return Inertia::render('Student/Support/Show', [
            'ticket' => $ticket,
        ]);
    }
    
    public function reply(ReplyTicketRequest $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }
        
        if (in_array($ticket->status, ['closed', 'resolved'])) {
            return back()->with('error', 'Bu so\'rov yopilgan');
        }
        
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);
        
        // Update ticket status
        if ($ticket->status === 'waiting_user') {
            $ticket->update(['status' => 'in_progress']);
        }
        
        $ticket->touch();
        
        return back()->with('success', 'Xabar yuborildi');
    }
    
    public function close(SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }
        
        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        
        return back()->with('success', 'So\'rov yopildi');
    }
    
    public function rate(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }
        
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:500'],
        ]);
        
        $ticket->update([
            'satisfaction_rating' => $request->rating,
            'feedback' => $request->feedback,
        ]);
        
        return back()->with('success', 'Bahoyingiz uchun rahmat!');
    }
}
