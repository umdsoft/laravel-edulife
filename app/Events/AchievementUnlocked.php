<?php

namespace App\Events;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Achievement $achievement
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'achievement.unlocked';
    }

    public function broadcastWith(): array
    {
        return [
            'achievement' => $this->achievement,
        ];
    }
}
