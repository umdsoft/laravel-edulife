<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order_number' => $this->order_number,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'lessons_count' => $this->whenLoaded('lessons', fn() => $this->lessons->count()),
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
            'vocabulary' => VocabularyResource::collection($this->whenLoaded('vocabulary')),
            'is_unlocked' => $this->when(isset($this->is_unlocked), $this->is_unlocked),
            'progress' => $this->when(isset($this->progress), $this->progress),
        ];
    }
}
