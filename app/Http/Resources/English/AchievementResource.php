<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'tier' => $this->tier,
            'category' => $this->whenLoaded('category'),
            'xp_reward' => $this->xp_reward,
            'coin_reward' => $this->coin_reward,
            'gem_reward' => $this->gem_reward,
            'special_reward' => $this->special_reward,
            'target_value' => $this->target_value,
            'user_progress' => $this->when(isset($this->user_progress), function () {
                if (!$this->user_progress)
                    return null;
                return [
                    'current_progress' => $this->user_progress->current_progress,
                    'is_unlocked' => $this->user_progress->is_unlocked,
                    'unlocked_at' => $this->user_progress->unlocked_at?->toISOString(),
                ];
            }),
        ];
    }
}
