<?php

namespace App\Services;

use App\Models\TestAttempt;
use App\Models\TestAnswer;
use App\Models\Question;

class TestEvaluationService
{
    /**
     * Evaluate all answers and calculate score
     */
    public function evaluate(TestAttempt $attempt): void
    {
        $answers = $attempt->answers()->with('question.options')->get();
        
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $skippedQuestions = 0;
        $totalPoints = 0;
        $earnedPoints = 0;
        
        foreach ($answers as $answer) {
            $question = $answer->question;
            $totalPoints += $answer->max_points;
            
            if (!$answer->is_answered) {
                $skippedQuestions++;
                $answer->update([
                    'is_correct' => false,
                    'points_earned' => 0,
                    'correct_answer' => $this->getCorrectAnswer($question),
                ]);
                continue;
            }
            
            // Evaluate based on question type
            $result = $this->evaluateAnswer($question, $answer->answer);
            
            $answer->update([
                'is_correct' => $result['is_correct'],
                'points_earned' => $result['points'],
                'correct_answer' => $result['correct_answer'],
            ]);
            
            $earnedPoints += $result['points'];
            
            if ($result['is_correct']) {
                $correctAnswers++;
            } else {
                $wrongAnswers++;
            }
        }
        
        // Calculate score percentage
        $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        
        // Check if passed
        $isPassed = $score >= $attempt->test->pass_rate;
        
        // Update attempt
        $attempt->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'skipped_questions' => $skippedQuestions,
            'score' => round($score, 2),
            'is_passed' => $isPassed,
        ]);
    }
    
    /**
     * Evaluate a single answer
     */
    private function evaluateAnswer(Question $question, $userAnswer): array
    {
        $correctAnswer = $this->getCorrectAnswer($question);
        
        return match($question->type) {
            'single_choice' => $this->evaluateSingleChoice($question, $userAnswer, $correctAnswer),
            'multiple_choice' => $this->evaluateMultipleChoice($question, $userAnswer, $correctAnswer),
            'true_false' => $this->evaluateTrueFalse($question, $userAnswer, $correctAnswer),
            'fill_blank' => $this->evaluateFillBlank($question, $userAnswer, $correctAnswer),
            'matching' => $this->evaluateMatching($question, $userAnswer, $correctAnswer),
            'ordering' => $this->evaluateOrdering($question, $userAnswer, $correctAnswer),
            default => ['is_correct' => false, 'points' => 0, 'correct_answer' => $correctAnswer],
        };
    }
    
    /**
     * Get correct answer for a question
     */
    private function getCorrectAnswer(Question $question): mixed
    {
        return match($question->type) {
            'single_choice' => $question->options->where('is_correct', true)->first()?->id,
            'multiple_choice' => $question->options->where('is_correct', true)->pluck('id')->toArray(),
            'true_false' => $question->correct_answer,
            'fill_blank' => $question->correct_answer,
            'matching' => $question->matching_pairs,
            'ordering' => $question->correct_order,
            default => null,
        };
    }
    
    /**
     * Single choice evaluation
     */
    private function evaluateSingleChoice(Question $question, $userAnswer, $correctAnswer): array
    {
        $isCorrect = $userAnswer === $correctAnswer;
        return [
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? ($question->points ?? 1) : 0,
            'correct_answer' => $correctAnswer,
        ];
    }
    
    /**
     * Multiple choice evaluation
     */
    private function evaluateMultipleChoice(Question $question, $userAnswer, $correctAnswer): array
    {
        $userAnswerArray = is_array($userAnswer) ? $userAnswer : [];
        $correctAnswerArray = is_array($correctAnswer) ? $correctAnswer : [];
        
        sort($userAnswerArray);
        sort($correctAnswerArray);
        
        $isCorrect = $userAnswerArray === $correctAnswerArray;
        
        // Partial credit
        if (!$isCorrect && !empty($userAnswerArray)) {
            $correctCount = count(array_intersect($userAnswerArray, $correctAnswerArray));
            $wrongCount = count(array_diff($userAnswerArray, $correctAnswerArray));
            $totalCorrect = count($correctAnswerArray);
            
            // Partial points: correct selections minus wrong selections
            $partialPoints = max(0, ($correctCount - $wrongCount) / $totalCorrect);
            $points = ($question->points ?? 1) * $partialPoints;
        } else {
            $points = $isCorrect ? ($question->points ?? 1) : 0;
        }
        
        return [
            'is_correct' => $isCorrect,
            'points' => round($points, 2),
            'correct_answer' => $correctAnswer,
        ];
    }
    
    /**
     * True/False evaluation
     */
    private function evaluateTrueFalse(Question $question, $userAnswer, $correctAnswer): array
    {
        $isCorrect = (bool) $userAnswer === (bool) $correctAnswer;
        return [
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? ($question->points ?? 1) : 0,
            'correct_answer' => $correctAnswer,
        ];
    }
    
    /**
     * Fill in the blank evaluation
     */
    private function evaluateFillBlank(Question $question, $userAnswer, $correctAnswer): array
    {
        // Case-insensitive comparison, trim whitespace
        $userAnswerClean = strtolower(trim($userAnswer ?? ''));
        
        // Correct answer can be array (multiple acceptable answers)
        $acceptableAnswers = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
        $acceptableAnswers = array_map(fn($a) => strtolower(trim($a)), $acceptableAnswers);
        
        $isCorrect = in_array($userAnswerClean, $acceptableAnswers);
        
        return [
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? ($question->points ?? 1) : 0,
            'correct_answer' => $correctAnswer,
        ];
    }
    
    /**
     * Matching evaluation
     */
    private function evaluateMatching(Question $question, $userAnswer, $correctAnswer): array
    {
        $userPairs = is_array($userAnswer) ? $userAnswer : [];
        $correctPairs = is_array($correctAnswer) ? $correctAnswer : [];
        
        $correctCount = 0;
        $totalPairs = count($correctPairs);
        
        foreach ($correctPairs as $leftId => $rightId) {
            if (isset($userPairs[$leftId]) && $userPairs[$leftId] === $rightId) {
                $correctCount++;
            }
        }
        
        $isCorrect = $correctCount === $totalPairs;
        $points = $totalPairs > 0 
            ? ($question->points ?? 1) * ($correctCount / $totalPairs) 
            : 0;
        
        return [
            'is_correct' => $isCorrect,
            'points' => round($points, 2),
            'correct_answer' => $correctAnswer,
        ];
    }
    
    /**
     * Ordering evaluation
     */
    private function evaluateOrdering(Question $question, $userAnswer, $correctAnswer): array
    {
        $userOrder = is_array($userAnswer) ? $userAnswer : [];
        $correctOrder = is_array($correctAnswer) ? $correctAnswer : [];
        
        $isCorrect = $userOrder === $correctOrder;
        
        // Partial credit based on correct positions
        if (!$isCorrect && !empty($userOrder)) {
            $correctPositions = 0;
            foreach ($userOrder as $index => $itemId) {
                if (isset($correctOrder[$index]) && $correctOrder[$index] === $itemId) {
                    $correctPositions++;
                }
            }
            $points = ($question->points ?? 1) * ($correctPositions / count($correctOrder));
        } else {
            $points = $isCorrect ? ($question->points ?? 1) : 0;
        }
        
        return [
            'is_correct' => $isCorrect,
            'points' => round($points, 2),
            'correct_answer' => $correctAnswer,
        ];
    }
}
