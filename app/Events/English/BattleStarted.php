<?php

namespace App\Events\English;

use App\Models\English\EnglishBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleStarted implements ShouldBroadcast
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
        return 'battle.started';
    }

    public function broadcastWith(): array
    {
        return [
            'battle_id' => $this->battle->id,
            'player1' => [
                'id' => $this->battle->player1_id,
                'name' => $this->battle->player1?->name,
                'elo' => $this->battle->player1_elo_before,
            ],
            'player2' => [
                'id' => $this->battle->player2_id,
                'name' => $this->battle->player2?->name,
                'elo' => $this->battle->player2_elo_before,
            ],
            'battle_type' => $this->battle->battle_type,
            'settings' => $this->battle->settings,
            'status' => $this->battle->status,
            'matched_at' => $this->battle->matched_at?->toISOString(),
        ];
    }
}
