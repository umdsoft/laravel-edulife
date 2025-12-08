<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'order_number' => $this->order_number,
            'difficulty' => $this->difficulty,
            'icon' => $this->icon,
            'color' => $this->color,
            'total_topics' => $this->whenLoaded('topics', fn() => $this->topics->count()),
            'is_active' => $this->is_active,
            'is_current' => $this->when(isset($this->is_current), $this->is_current),
            'is_unlocked' => $this->when(isset($this->is_unlocked), $this->is_unlocked),
            'progress' => $this->when(isset($this->progress), $this->progress),
            'stats' => $this->when(isset($this->stats), $this->stats),
        ];
    }
}
