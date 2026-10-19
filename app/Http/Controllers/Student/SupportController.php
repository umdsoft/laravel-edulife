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
        // Answers describe what the platform actually does today
        $faqs = [
            [
                'category' => 'Hisob',
                'questions' => [
                    ['q' => "Parolimni qanday o'zgartiraman?", 'a' => "Sozlamalar sahifasidagi «Parol» bo'limida joriy va yangi parolni kiriting."],
                    ['q' => "Ism yoki emailimni o'zgartira olamanmi?", 'a' => "Ha, Profil → «Tahrirlash» sahifasida ism, familiya, email va bio'ni yangilashingiz mumkin."],
                ],
            ],
            [
                'category' => 'Kurslar',
                'questions' => [
                    ['q' => 'Bepul kursga qanday yozilaman?', 'a' => "Kurs sahifasida «Kursga yozilish» tugmasini bosing — kurs «Mening kurslarim» bo'limida paydo bo'ladi."],
                    ['q' => 'Pullik kursni qanday sotib olaman?', 'a' => "Onlayn to'lov hozircha ulanmagan. To'lov va kursga kirish uchun administrator bilan bog'laning yoki shu yerda so'rov yuboring — to'lovdan so'ng kurs sizga ochiladi."],
                    ['q' => 'Dars yoki test ochilmayapti, nima qilay?', 'a' => "Test darsini yakunlagach yoki kerakli darslarni tugatgach ochiladi (yakuniy test — kursning 80% qismidan keyin). Muammo davom etsa, «Texnik muammo» bo'limi bilan so'rov yuboring."],
                ],
            ],
            [
                'category' => 'Sertifikatlar',
                'questions' => [
                    ['q' => 'Sertifikatni qanday olaman?', 'a' => "Kursning barcha darslarini yakunlaganingizda sertifikat avtomatik beriladi va «Sertifikatlar» bo'limida ko'rinadi."],
                    ['q' => 'Sertifikat haqiqiyligini tekshirish mumkinmi?', 'a' => "Ha, sertifikatdagi tekshirish havolasi yoki kodi orqali istalgan kishi uning haqiqiyligini tekshira oladi."],
                ],
            ],
            [
                'category' => 'Coin va XP',
                'questions' => [
                    ['q' => "Coin va XP qanday to'planadi?", 'a' => "Darslarni tugatish, testlardan o'tish, kunlik vazifalar va olimpiadalar uchun beriladi."],
                    ['q' => 'Coinni nimaga sarflash mumkin?', 'a' => "Coin do'konidan avatar, nishon va foydali buyumlar olish hamda coin bilan to'lanadigan olimpiada va turnirlarda qatnashish uchun."],
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
        
        // Internal staff notes are for support only
        $ticket->load([
            'messages' => fn ($q) => $q->where('is_internal_note', false),
            'messages.user:id,first_name,last_name,avatar,role',
        ]);
        
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
