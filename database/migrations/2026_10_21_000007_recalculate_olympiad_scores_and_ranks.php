<?php

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\UserOlympiadHistory;
use App\Services\LeaderboardService;
use App\Services\OlympiadGradingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Attempts submitted before the scoring fix carry the weighted percentage as
 * their score and rank 0, and completed olympiads never got their history rows
 * or coin rewards because finalizing failed. Re-score every submitted attempt
 * with the single formula, re-rank, and finish completed olympiads that have no
 * history yet. Idempotent: scoring is recomputed from the answers, and
 * finalizing skips participants that already have a history row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('olympiad_attempts')) {
            return;
        }

        $leaderboard = app(LeaderboardService::class);

        Olympiad::whereHas('attempts')->get()->each(function (Olympiad $olympiad) use ($leaderboard) {
            OlympiadAttempt::where('olympiad_id', $olympiad->id)
                ->completed()
                ->with(['sectionAttempts.section', 'sectionAttempts.answers'])
                ->get()
                ->each(function (OlympiadAttempt $attempt) use ($leaderboard) {
                    $attempt->calculateScores();
                    $attempt->save();
                    $leaderboard->updateEntry($attempt);
                });

            $finished = $olympiad->status === Olympiad::STATUS_COMPLETED
                && !UserOlympiadHistory::where('olympiad_id', $olympiad->id)->exists();

            if ($finished) {
                try {
                    app(OlympiadGradingService::class)->finalizeOlympiadGrading($olympiad);
                } catch (\DomainException) {
                    // e.g. answers still waiting for manual grading: the admin finalizes later
                }
            }
        });
    }

    public function down(): void
    {
        // Data correction only.
    }
};
