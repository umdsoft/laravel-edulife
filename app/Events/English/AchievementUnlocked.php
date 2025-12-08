<?php

namespace App\Events\English;

use App\Models\English\EnglishAchievement;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public EnglishAchievement $achievement
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'achievement.unlocked';
    }

    public function broadcastWith(): array
    {
        return [
            'achievement' => [
                'id' => $this->achievement->id,
                'name' => $this->achievement->name,
                'description' => $this->achievement->description,
                'icon' => $this->achievement->icon,
                'tier' => $this->achievement->tier,
                'xp_reward' => $this->achievement->xp_reward,
                'coin_reward' => $this->achievement->coin_reward,
                'special_reward' => $this->achievement->special_reward,
            ],
            'unlocked_at' => now()->toISOString(),
        ];
    }
}
