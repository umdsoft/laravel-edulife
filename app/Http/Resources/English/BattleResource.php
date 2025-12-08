<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BattleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isPlayer1 = $this->player1_id === $userId;

        return [
            'id' => $this->id,
            'battle_type' => $this->battle_type,
            'status' => $this->status,
            'player1' => [
                'id' => $this->player1_id,
                'name' => $this->player1?->name,
                'avatar' => $this->player1?->avatar,
                'elo' => $this->player1_elo_before,
                'score' => $this->player1_score,
                'correct' => $this->player1_correct,
            ],
            'player2' => [
                'id' => $this->player2_id,
                'name' => $this->player2?->name,
                'avatar' => $this->player2?->avatar,
                'elo' => $this->player2_elo_before,
                'score' => $this->player2_score,
                'correct' => $this->player2_correct,
            ],
            'winner_id' => $this->winner_id,
            'result' => $this->result,
            'elo_change' => $this->elo_change,
            'my_elo_change' => $isPlayer1
                ? ($this->player1_elo_after - $this->player1_elo_before)
                : ($this->player2_elo_after - $this->player2_elo_before),
            'rounds' => $this->whenLoaded('rounds'),
            'settings' => $this->settings,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
