<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleMatchFound implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Battle $battle
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('battle.' . $this->battle->id),
            new Channel('user.' . $this->battle->player1_id),
            new Channel('user.' . $this->battle->player2_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.found';
    }

    public function broadcastWith(): array
    {
        return [
            'battle' => $this->battle->load(['player1', 'player2', 'rounds']),
        ];
    }
}
