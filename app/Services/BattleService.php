<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\BattleQuestion;
use App\Models\BattleAnswer;
use App\Models\User;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use App\Events\BattleUpdate;

class BattleService
{
    const BATTLE_XP_WIN = 50;
    const BATTLE_XP_DRAW = 20;
    const BATTLE_XP_LOSS = 10;
    const QUESTIONS_PER_BATTLE = 5;

    public function findOpponent(User $user)
    {
        // Simple matchmaking: Find a pending battle or create one
        $pendingBattle = Battle::where('status', 'pending')
            ->where('player1_id', '!=', $user->id)
            ->first();

        if ($pendingBattle) {
            $pendingBattle->update([
                'player2_id' => $user->id,
                'status' => 'active',
                'started_at' => now(),
            ]);
            
            // Generate questions for this battle
            $this->generateBattleQuestions($pendingBattle);

            return $pendingBattle;
        }

        return Battle::create([
            'player1_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function generateBattleQuestions(Battle $battle)
    {
        // Get random questions (assuming we have a Question model linked to courses/tests)
        // For simplicity, fetching random questions from the database
        $questions = Question::inRandomOrder()->limit(self::QUESTIONS_PER_BATTLE)->get();

        foreach ($questions as $index => $question) {
            BattleQuestion::create([
                'battle_id' => $battle->id,
                'question_id' => $question->id,
                'order' => $index + 1,
            ]);
        }
    }

    public function submitAnswer(Battle $battle, User $user, int $questionId, int $optionId)
    {
        $question = Question::find($questionId);
        $isCorrect = $question->correct_option_id == $optionId;
        $points = $isCorrect ? 10 : 0; // Simple scoring

        BattleAnswer::create([
            'battle_id' => $battle->id,
            'user_id' => $user->id,
            'question_id' => $questionId,
            'option_id' => $optionId,
            'is_correct' => $isCorrect,
            'points' => $points,
        ]);

        // Update player score in battle
        if ($battle->player1_id == $user->id) {
            $battle->increment('player1_score', $points);
        } else {
            $battle->increment('player2_score', $points);
        }

        // Check if battle is finished
        $this->checkBattleCompletion($battle);
    }

    public function checkBattleCompletion(Battle $battle)
    {
        $totalQuestions = $battle->questions()->count();
        $p1Answers = $battle->answers()->where('user_id', $battle->player1_id)->count();
        $p2Answers = $battle->answers()->where('user_id', $battle->player2_id)->count();

        if ($p1Answers >= $totalQuestions && $p2Answers >= $totalQuestions) {
            $this->endBattle($battle);
        }
    }

    public function endBattle(Battle $battle)
    {
        $winnerId = null;
        if ($battle->player1_score > $battle->player2_score) {
            $winnerId = $battle->player1_id;
            $this->awardXP($battle->player1, self::BATTLE_XP_WIN);
            $this->awardXP($battle->player2, self::BATTLE_XP_LOSS);
        } elseif ($battle->player2_score > $battle->player1_score) {
            $winnerId = $battle->player2_id;
            $this->awardXP($battle->player2, self::BATTLE_XP_WIN);
            $this->awardXP($battle->player1, self::BATTLE_XP_LOSS);
        } else {
            // Draw
            $this->awardXP($battle->player1, self::BATTLE_XP_DRAW);
            $this->awardXP($battle->player2, self::BATTLE_XP_DRAW);
        }

        $battle->update([
            'status' => 'completed',
            'winner_id' => $winnerId,
            'ended_at' => now(),
        ]);
    }

    protected function awardXP(User $user, int $amount)
    {
        $user->studentProfile->addXp($amount);
    }
}
