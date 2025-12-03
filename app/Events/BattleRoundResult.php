<?php

namespace App\Events;

use App\Models\Battle;
use App\Models\BattleRound;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleRoundResult implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Battle $battle,
        public BattleRound $round
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('battle.' . $this->battle->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'round.result';
    }

    public function broadcastWith(): array
    {
        return [
            'round' => $this->round,
            'battle' => $this->battle->fresh(['player1', 'player2']),
        ];
    }
}
