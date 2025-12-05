<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadRegistration;
use App\Services\OlympiadService;
use App\Services\OlympiadRegistrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OlympiadController extends Controller
{
    protected OlympiadService $olympiadService;
    protected OlympiadRegistrationService $registrationService;

    public function __construct(
        OlympiadService $olympiadService,
        OlympiadRegistrationService $registrationService
    ) {
        $this->olympiadService = $olympiadService;
        $this->registrationService = $registrationService;
    }

    /**
     * Display list of available olympiads
     */
    public function index(Request $request): Response
    {
        $typeId = $request->get('type');
        $olympiads = $this->olympiadService->getUpcomingForStudents($typeId);
        $types = $this->olympiadService->getOlympiadTypes();

        // Get user's registrations
        $userId = auth()->id();
        $registrations = OlympiadRegistration::where('user_id', $userId)
            ->whereIn('olympiad_id', $olympiads->pluck('id'))
            ->get()
            ->keyBy('olympiad_id');

        return Inertia::render('Student/Olympiad/Index', [
            'olympiads' => $olympiads->map(fn($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'slug' => $o->slug,
                'description' => $o->short_description,
                'type' => $o->olympiadType->display_name,
                'type_icon' => $o->olympiadType->icon,
                'stage' => $o->stage?->display_name,
                'cover_image' => $o->cover_image_url,
                'registration_fee' => $o->registration_fee,
                'registration_start_at' => $o->registration_start_at?->format('d.m.Y H:i'),
                'registration_end_at' => $o->registration_end_at?->format('d.m.Y H:i'),
                'olympiad_start_at' => $o->olympiad_start_at?->format('d.m.Y H:i'),
                'total_duration' => $o->total_duration_minutes,
                'max_participants' => $o->max_participants,
                'current_participants' => $o->registrations_count,
                'status' => $o->status,
                'is_registered' => isset($registrations[$o->id]),
                'registration_status' => $registrations[$o->id]?->status,
            ]),
            'types' => $types,
            'selectedType' => $typeId,
        ]);
    }

    /**
     * Display olympiad details
     */
    public function show(string $slug): Response
    {
        $olympiad = Olympiad::with([
            'olympiadType',
            'stage',
            'series',
            'sections' => fn($q) => $q->ordered(),
        ])->where('slug', $slug)->firstOrFail();

        $userId = auth()->id();
        $registration = $this->registrationService->getUserRegistration($userId, $olympiad->id);

        $canRegister = $this->registrationService->canRegister(auth()->user(), $olympiad);

        return Inertia::render('Student/Olympiad/Show', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'description' => $olympiad->description,
                'rules' => $olympiad->rules,
                'type' => $olympiad->olympiadType->display_name,
                'type_color' => $olympiad->olympiadType->color,
                'stage' => $olympiad->stage?->display_name,
                'series' => $olympiad->series?->name,
                'cover_image' => $olympiad->cover_image_url,
                'banner_image' => $olympiad->banner_image,
                'registration_fee' => $olympiad->registration_fee,
                'demo_price' => $olympiad->demo_config['price'] ?? 0,
                'demo_attempts' => $olympiad->demo_config['max_attempts'] ?? 3,
                'registration_start_at' => $olympiad->registration_start_at?->format('d.m.Y H:i'),
                'registration_end_at' => $olympiad->registration_end_at?->format('d.m.Y H:i'),
                'olympiad_start_at' => $olympiad->olympiad_start_at?->format('d.m.Y H:i'),
                'olympiad_end_at' => $olympiad->olympiad_end_at?->format('d.m.Y H:i'),
                'total_duration' => $olympiad->total_duration_minutes,
                'status' => $olympiad->status,
                'sections' => $olympiad->sections->map(fn($s) => [
                    'id' => $s->id,
                    'type' => $s->section_type,
                    'title' => $s->title,
                    'duration_minutes' => $s->duration_minutes,
                    'max_points' => $s->max_points,
                    'weight_percent' => $s->weight_percent,
                ]),
                'prize_pool' => $olympiad->prize_pool,
                'prize_distribution' => $olympiad->prize_distribution,
                'reward_config' => $olympiad->reward_config,
            ],
            'registration' => $registration ? [
                'id' => $registration->id,
                'status' => $registration->status,
                'final_price' => $registration->final_price,
                'demo_purchased' => $registration->demo_purchased,
                'demo_attempts_used' => $registration->demo_attempts_used,
                'demo_best_score' => $registration->demo_best_score,
                'confirmed_at' => $registration->confirmed_at?->format('d.m.Y H:i'),
            ] : null,
            'canRegister' => $canRegister,
        ]);
    }

    /**
     * Show registration form
     */
    public function register(string $slug): Response
    {
        $olympiad = Olympiad::with(['olympiadType', 'stage'])
            ->where('slug', $slug)
            ->firstOrFail();

        $canRegister = $this->registrationService->canRegister(auth()->user(), $olympiad);

        if (!$canRegister['can_register']) {
            return redirect()->route('student.olympiads.show', $slug)
                ->with('error', __("olympiad.registration.{$canRegister['reason']}"));
        }

        return Inertia::render('Student/Olympiad/Register', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
                'type' => $olympiad->olympiadType->display_name,
                'stage' => $olympiad->stage?->display_name,
                'registration_fee' => $olympiad->registration_fee,
                'demo_price' => $olympiad->demo_config['price'] ?? 0,
                'registration_end_at' => $olympiad->registration_end_at?->format('d.m.Y H:i'),
            ],
            'userCoins' => auth()->user()->coins,
        ]);
    }

    /**
     * Process registration
     */
    public function storeRegistration(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $request->validate([
            'coupon_code' => 'nullable|string|max:50',
            'school_id' => 'nullable|uuid',
            'grade_level' => 'nullable|integer|min:1|max:12',
        ]);

        try {
            $registration = $this->registrationService->register(
                auth()->user(),
                $olympiad,
                $request->only(['coupon_code', 'school_id', 'grade_level'])
            );

            if ($registration->status === OlympiadRegistration::STATUS_PENDING_PAYMENT) {
                return redirect()->route('student.olympiads.payment', [
                    'slug' => $slug,
                    'registration' => $registration->id,
                ]);
            }

            return redirect()->route('student.olympiads.show', $slug)
                ->with('success', __('olympiad.registration.success'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show payment page
     */
    public function payment(string $slug, string $registrationId): Response
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $registration = OlympiadRegistration::where('id', $registrationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($registration->status !== OlympiadRegistration::STATUS_PENDING_PAYMENT) {
            return redirect()->route('student.olympiads.show', $slug);
        }

        return Inertia::render('Student/Olympiad/Payment', [
            'olympiad' => [
                'id' => $olympiad->id,
                'title' => $olympiad->title,
                'slug' => $olympiad->slug,
            ],
            'registration' => [
                'id' => $registration->id,
                'original_price' => $registration->original_price,
                'discount_amount' => $registration->discount_amount,
                'final_price' => $registration->final_price,
            ],
            'userCoins' => auth()->user()->coins,
            'coinsExchangeRate' => config('olympiad.coins_exchange_rate', 100),
        ]);
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, string $slug, string $registrationId)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();
        $registration = OlympiadRegistration::where('id', $registrationId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'payment_method' => 'required|in:coins,card,payme,click',
            'coins_amount' => 'required_if:payment_method,coins|integer|min:0',
        ]);

        try {
            $this->registrationService->processPayment($registration, [
                'payment_method' => $request->payment_method,
                'amount' => $registration->final_price,
                'coins_amount' => $request->coins_amount ?? 0,
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('student.olympiads.show', $slug)
                ->with('success', __('olympiad.payment.success'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(Request $request, string $olympiadId)
    {
        $request->validate(['code' => 'required|string']);

        $coupon = $this->registrationService->validateCoupon(
            $request->code,
            $olympiadId,
            auth()->id()
        );

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => __('olympiad.coupon.invalid'),
            ]);
        }

        $olympiad = Olympiad::find($olympiadId);
        $discount = $coupon->calculateDiscount($olympiad->registration_fee);

        return response()->json([
            'valid' => true,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'discount_amount' => $discount,
        ]);
    }
}
