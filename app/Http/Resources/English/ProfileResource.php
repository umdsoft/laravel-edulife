<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'current_level' => new LevelResource($this->whenLoaded('currentLevel')),
            'total_xp' => $this->total_xp,
            'coins' => $this->coins,
            'gems' => $this->gems,
            'elo_rating' => $this->elo_rating,
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'battles_played' => $this->battles_played,
            'battles_won' => $this->battles_won,
            'battle_win_streak' => $this->battle_win_streak,
            'best_battle_win_streak' => $this->best_battle_win_streak,
            'total_study_days' => $this->total_study_days,
            'streak_freezes' => $this->streak_freezes,
            'last_study_date' => $this->last_study_date?->format('Y-m-d'),
        ];
    }
}
