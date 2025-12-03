<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'question_id',
        'option_text',
        'option_image',
        'is_correct',
        'match_text',
        'correct_position',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    // Accessors
    public function getOptionImageUrlAttribute(): ?string
    {
        return $this->option_image ? asset('storage/' . $this->option_image) : null;
    }

    // Relationships
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
