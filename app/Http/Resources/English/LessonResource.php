<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'lesson_type' => $this->lesson_type,
            'order_number' => $this->order_number,
            'estimated_minutes' => $this->estimated_minutes,
            'xp_reward' => $this->xp_reward,
            'is_active' => $this->is_active,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'vocabulary' => VocabularyResource::collection($this->whenLoaded('vocabulary')),
            'exercises' => $this->whenLoaded('exercises'),
            'grammar_points' => $this->whenLoaded('grammarPoints'),
            'user_progress' => $this->when(isset($this->user_progress), $this->user_progress),
        ];
    }
}
