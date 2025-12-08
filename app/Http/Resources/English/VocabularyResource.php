<?php

namespace App\Http\Resources\English;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VocabularyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'word' => $this->word,
            'translation_uz' => $this->translation_uz,
            'pronunciation' => $this->pronunciation,
            'phonetic' => $this->phonetic,
            'part_of_speech' => $this->part_of_speech,
            'definition' => $this->definition,
            'example_sentence' => $this->example_sentence,
            'example_translation' => $this->example_translation,
            'audio_url' => $this->audio_url,
            'image_url' => $this->image_url,
            'difficulty' => $this->difficulty,
            'synonyms' => $this->synonyms,
            'antonyms' => $this->antonyms,
            'category' => $this->whenLoaded('category'),
            'user_progress' => $this->when(isset($this->user_progress), function () {
                if (!$this->user_progress)
                    return null;
                return [
                    'status' => $this->user_progress->status,
                    'mastery_level' => $this->user_progress->mastery_level,
                    'next_review_date' => $this->user_progress->next_review_date?->format('Y-m-d'),
                    'consecutive_correct' => $this->user_progress->consecutive_correct,
                ];
            }),
        ];
    }
}
