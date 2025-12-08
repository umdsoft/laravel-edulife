<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->when(isset($this->rank), $this->rank),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
                'avatar' => $this->user?->avatar,
            ],
            'score' => $this->score,
            'rank_change' => $this->rank_change,
            'last_updated_at' => $this->last_updated_at?->toISOString(),
        ];
    }
}
