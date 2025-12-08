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

class BattleRoundStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EnglishBattle $battle,
        public EnglishBattleRound $round
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
        return 'battle.round.started';
    }

    public function broadcastWith(): array
    {
        return [
            'battle_id' => $this->battle->id,
            'round_number' => $this->round->round_number,
            'question' => [
                'question' => $this->round->question_data['question'] ?? '',
                'question_type' => $this->round->question_data['question_type'] ?? 'multiple_choice',
                'options' => $this->round->question_data['options'] ?? [],
                'difficulty' => $this->round->question_data['difficulty'] ?? 'medium',
            ],
            'time_limit' => $this->battle->settings['time_per_question'] ?? 15000,
            'started_at' => $this->round->started_at?->toISOString(),
            'current_scores' => [
                'player1' => $this->battle->player1_score,
                'player2' => $this->battle->player2_score,
            ],
        ];
    }
}
