<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTournamentRequest;
use App\Models\Direction;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Services\CoinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tournaments are created here; students register / withdraw on
 * /student/tournaments while the status is "registration".
 */
class TournamentController extends Controller
{
    public function index(): Response
    {
        $local = fn ($date) => $date?->format('Y-m-d\TH:i') ?? '';

        $tournaments = Tournament::with('direction:id,name_uz')
            ->withCount('participants')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Tournament $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'description' => $t->description,
                'direction_id' => $t->direction_id,
                'direction' => $t->direction?->name_uz,
                'type' => $t->type,
                'status' => $t->status,
                'entry_fee_coins' => (int) $t->entry_fee_coins,
                'prize_pool_coins' => (int) $t->prize_pool_coins,
                'min_participants' => (int) $t->min_participants,
                'max_participants' => (int) $t->max_participants,
                'min_elo' => $t->min_elo,
                'max_elo' => $t->max_elo,
                'questions_per_match' => (int) $t->questions_per_match,
                'time_per_question' => (int) $t->time_per_question,
                // datetime-local values in the app timezone
                'registration_starts_at' => $local($t->registration_starts_at),
                'registration_ends_at' => $local($t->registration_ends_at),
                'starts_at' => $local($t->starts_at),
                'ends_at' => $local($t->ends_at),
                'participants_count' => $t->participants_count,
            ]);

        return Inertia::render('Admin/Tournaments/Index', [
            'tournaments' => $tournaments,
            'directions' => Direction::active()->ordered()->get(['id', 'name_uz'])
                ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name_uz]),
        ]);
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));

        Tournament::create($data);

        return back()->with('success', 'Turnir yaratildi!');
    }

    public function update(StoreTournamentRequest $request, Tournament $tournament, CoinService $coins): RedirectResponse
    {
        $data = $request->validated();
        $cancelling = $data['status'] === 'cancelled' && $tournament->status !== 'cancelled';

        DB::transaction(function () use ($tournament, $data, $cancelling, $coins) {
            $tournament->update($data);

            // a cancelled tournament gives every registered student the entry fee back
            if ($cancelling && $tournament->entry_fee > 0) {
                TournamentParticipant::with('user')
                    ->where('tournament_id', $tournament->id)
                    ->where('status', 'registered')
                    ->get()
                    ->each(fn ($p) => $coins->addCoins($p->user, $tournament->entry_fee, 'tournament_refund', "Turnir bekor qilindi: {$tournament->name}", $tournament));
            }
        });

        return back()->with('success', $cancelling ? "Turnir bekor qilindi, kirish to'lovlari qaytarildi." : 'Turnir yangilandi!');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        if ($tournament->participants()->exists()) {
            return back()->with('error', "Ishtirokchilari bor turnirni o'chirib bo'lmaydi. Uning holatini «Bekor qilingan» qiling.");
        }

        $tournament->delete();

        return back()->with('success', "Turnir o'chirildi!");
    }
}
