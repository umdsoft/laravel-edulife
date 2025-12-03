<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Battle $battle
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('battle.' . $this->battle->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'battle.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'battle' => $this->battle->load(['player1', 'player2', 'winner']),
        ];
    }
}
