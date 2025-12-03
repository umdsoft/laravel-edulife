<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'course_id',
        'test_id',
        'type',
        'difficulty',
        'question_text',
        'question_image',
        'explanation',
        'points',
        'partial_credit',
        'case_sensitive',
        'correct_answer',
        'matching_pairs',
        'ordering_items',
        'accepted_answers',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'partial_credit' => 'boolean',
            'case_sensitive' => 'boolean',
            'correct_answer' => 'array',
            'matching_pairs' => 'array',
            'ordering_items' => 'array',
            'accepted_answers' => 'array',
        ];
    }

    // Accessors
    public function getQuestionImageUrlAttribute(): ?string
    {
        return $this->question_image ? asset('storage/' . $this->question_image) : null;
    }

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    // Helper Methods
    public function checkAnswer($answer): bool
    {
        return match($this->type) {
            'single_choice', 'multiple_choice' => $this->checkChoiceAnswer($answer),
            'true_false' => $this->checkTrueFalseAnswer($answer),
            'fill_blank' => $this->checkFillBlankAnswer($answer),
            'matching' => $this->checkMatchingAnswer($answer),
            'ordering' => $this->checkOrderingAnswer($answer),
            default => false,
        };
    }

    protected function checkChoiceAnswer($answer): bool
    {
        return $answer == $this->correct_answer;
    }

    protected function checkTrueFalseAnswer($answer): bool
    {
        return $answer === $this->correct_answer;
    }

    protected function checkFillBlankAnswer($answer): bool
    {
        $userAnswer = $this->case_sensitive ? $answer : strtolower($answer);
        $acceptedAnswers = $this->case_sensitive 
            ? $this->accepted_answers 
            : array_map('strtolower', $this->accepted_answers);
            
        return in_array($userAnswer, $acceptedAnswers);
    }

    protected function checkMatchingAnswer($answer): bool
    {
        return $answer == $this->matching_pairs;
    }

    protected function checkOrderingAnswer($answer): bool
    {
        return $answer == $this->ordering_items;
    }
}
