<?php

namespace App\Events\English;

use App\Models\English\EnglishBattle;
use App\Models\English\EnglishBattleRound;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleAnswerSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EnglishBattle $battle,
        public EnglishBattleRound $round,
        public string $playerId,
        public bool $isCorrect,
        public int $points
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
        return 'battle.answer.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'battle_id' => $this->battle->id,
            'round_number' => $this->round->round_number,
            'player_id' => $this->playerId,
            'is_correct' => $this->isCorrect,
            'points' => $this->points,
            'both_answered' => $this->round->player1_answer !== null && $this->round->player2_answer !== null,
        ];
    }
}
