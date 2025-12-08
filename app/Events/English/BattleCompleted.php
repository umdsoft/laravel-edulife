<?php

namespace App\Events\English;

use App\Models\English\EnglishBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EnglishBattle $battle
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('battle.' . $this->battle->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'battle.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'battle_id' => $this->battle->id,
            'winner_id' => $this->battle->winner_id,
            'result' => $this->battle->result,
            'final_scores' => [
                'player1' => $this->battle->player1_score,
                'player2' => $this->battle->player2_score,
            ],
            'correct_answers' => [
                'player1' => $this->battle->player1_correct,
                'player2' => $this->battle->player2_correct,
            ],
            'elo_changes' => [
                'player1' => [
                    'before' => $this->battle->player1_elo_before,
                    'after' => $this->battle->player1_elo_after,
                    'change' => $this->battle->player1_elo_after - $this->battle->player1_elo_before,
                ],
                'player2' => [
                    'before' => $this->battle->player2_elo_before,
                    'after' => $this->battle->player2_elo_after,
                    'change' => $this->battle->player2_elo_after - $this->battle->player2_elo_before,
                ],
            ],
            'rewards' => [
                'winner_xp' => $this->battle->winner_xp,
                'loser_xp' => $this->battle->loser_xp,
                'winner_coins' => $this->battle->winner_coins,
                'loser_coins' => $this->battle->loser_coins,
            ],
            'completed_at' => $this->battle->completed_at?->toISOString(),
        ];
    }
}
