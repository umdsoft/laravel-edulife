<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'scenario' => $this->scenario,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'messages_count' => $this->messages_count,
            'total_user_words' => $this->total_user_words,
            'duration_seconds' => $this->duration_seconds,
            'messages' => $this->whenLoaded('messages'),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
        ];
    }
}
