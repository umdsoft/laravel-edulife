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
        'bank_question_id', // copied from the exam bank ("Bankdan qo'shish")
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

    /** The exam bank question this one was copied from (null when written by hand). */
    public function bankQuestion()
    {
        return $this->belongsTo(QuestionBank::class, 'bank_question_id');
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

    /**
     * Opaque id of the right-hand item of a matching pair. The student picks
     * these ids; using the option id itself would reveal the pairing.
     */
    public static function matchingKey(string $optionId): string
    {
        return 'm'.substr(hash_hmac('sha256', $optionId, (string) config('app.key')), 0, 15);
    }

    /**
     * Teachers edit every type as option rows; grading (TestEvaluationService)
     * reads correct_answer / matching_pairs / ordering_items. Derive those from
     * the saved options.
     */
    public function syncAnswerKey(): void
    {
        $options = $this->options()->get();
        if ($options->isEmpty()) {
            return; // nothing to derive from (keep a key that was set directly)
        }

        $attributes = match ($this->type) {
            'single_choice' => ['correct_answer' => $options->firstWhere('is_correct', true)?->id],
            'multiple_choice' => ['correct_answer' => $options->where('is_correct', true)->pluck('id')->values()->all()],
            // rows are [Rost, Yolg'on]: the answer is true when "Rost" is the correct row
            'true_false' => ['correct_answer' => (bool) $options->first()?->is_correct],
            'fill_blank' => (function () use ($options) {
                $accepted = $options->pluck('option_text')->map(fn ($t) => trim((string) $t))->filter()->values()->all();

                return ['correct_answer' => $accepted, 'accepted_answers' => $accepted];
            })(),
            'matching' => ['matching_pairs' => $options->mapWithKeys(fn ($o) => [$o->id => self::matchingKey($o->id)])->all()],
            'ordering' => ['ordering_items' => $options->sortBy(fn ($o) => $o->correct_position ?? $o->sort_order)->pluck('id')->values()->all()],
            default => [],
        };

        if ($attributes) {
            $this->forceFill($attributes)->save();
        }
    }

    /**
     * Options as shown to a student taking the test: nothing that gives the answer away.
     */
    public function optionsForStudent(bool $shuffleChoices = true): array
    {
        $options = $this->relationLoaded('options') ? $this->options : $this->options()->get();

        return match ($this->type) {
            // the options are the accepted answers
            'fill_blank' => [],
            'true_false' => [],
            'matching' => [
                ...$options->map(fn ($o) => ['id' => $o->id, 'content' => $o->option_text, 'column' => 'left'])->all(),
                ...$options->shuffle()->map(fn ($o) => ['id' => self::matchingKey($o->id), 'content' => $o->match_text, 'column' => 'right'])->all(),
            ],
            // stored in the correct order — always shuffle
            'ordering' => $options->shuffle()->map(fn ($o) => ['id' => $o->id, 'content' => $o->option_text])->values()->all(),
            'single_choice', 'multiple_choice' => ($shuffleChoices ? $options->shuffle() : $options)
                ->map(fn ($o) => ['id' => $o->id, 'content' => $o->option_text])->values()->all(),
            default => $options->map(fn ($o) => ['id' => $o->id, 'content' => $o->option_text])->values()->all(),
        };
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
