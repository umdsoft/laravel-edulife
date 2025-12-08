<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishBattleQuestion;
use Illuminate\Support\Str;

class BattleQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $levels = EnglishLevel::all()->keyBy('code');

        if ($levels->isEmpty()) {
            $this->command->error('Levels not found! Run LevelTopicSeeder first.');
            return;
        }

        // A1 Level Questions
        $a1Questions = [
            ['question_type' => 'vocabulary_meaning', 'question' => 'What does "hello" mean?', 'question_uz' => '"Hello" so\'zining ma\'nosi nima?', 'options' => ['A greeting', 'Goodbye', 'Thank you', 'Sorry'], 'correct_answer' => 'A greeting', 'difficulty' => 'easy'],
            ['question_type' => 'vocabulary_meaning', 'question' => 'What is a "book"?', 'options' => ['Something to read', 'Something to eat', 'Something to wear', 'Something to drive'], 'correct_answer' => 'Something to read', 'difficulty' => 'easy'],
            ['question_type' => 'vocabulary_meaning', 'question' => 'What does "happy" mean?', 'options' => ['Feeling joy', 'Feeling sad', 'Feeling angry', 'Feeling tired'], 'correct_answer' => 'Feeling joy', 'difficulty' => 'easy'],

            ['question_type' => 'vocabulary_translation', 'question' => 'What is "apple" in Uzbek?', 'options' => ['Olma', 'Anor', 'Banan', 'Uzum'], 'correct_answer' => 'Olma', 'difficulty' => 'easy'],
            ['question_type' => 'vocabulary_translation', 'question' => 'What is "water" in Uzbek?', 'options' => ['Suv', 'Sut', 'Choy', 'Sharbat'], 'correct_answer' => 'Suv', 'difficulty' => 'easy'],

            ['question_type' => 'grammar_fill_gap', 'question' => 'I ___ a student.', 'options' => ['am', 'is', 'are', 'be'], 'correct_answer' => 'am', 'explanation' => 'We use "am" with "I" in present simple.', 'difficulty' => 'easy'],
            ['question_type' => 'grammar_fill_gap', 'question' => 'She ___ a teacher.', 'options' => ['is', 'am', 'are', 'be'], 'correct_answer' => 'is', 'explanation' => 'We use "is" with he/she/it.', 'difficulty' => 'easy'],
            ['question_type' => 'grammar_fill_gap', 'question' => 'They ___ happy.', 'options' => ['are', 'is', 'am', 'be'], 'correct_answer' => 'are', 'explanation' => 'We use "are" with they/we/you.', 'difficulty' => 'easy'],

            ['question_type' => 'grammar_correct_form', 'question' => 'Which sentence is correct?', 'options' => ['She is my sister.', 'She am my sister.', 'She are my sister.', 'She be my sister.'], 'correct_answer' => 'She is my sister.', 'difficulty' => 'easy'],
            ['question_type' => 'grammar_correct_form', 'question' => 'Choose the correct sentence.', 'options' => ['I have a cat.', 'I has a cat.', 'I having a cat.', 'I haves a cat.'], 'correct_answer' => 'I have a cat.', 'difficulty' => 'easy'],

            ['question_type' => 'grammar_sentence_order', 'question' => 'Arrange: name / is / My / Anna', 'options' => ['My name is Anna.', 'Is my name Anna.', 'Name my is Anna.', 'Anna is name my.'], 'correct_answer' => 'My name is Anna.', 'difficulty' => 'medium'],
        ];

        // A2 Level Questions
        $a2Questions = [
            ['question_type' => 'grammar_correct_form', 'question' => 'I ___ to the cinema last night.', 'options' => ['went', 'go', 'going', 'gone'], 'correct_answer' => 'went', 'explanation' => 'We use past simple for completed actions.', 'difficulty' => 'medium'],
            ['question_type' => 'grammar_correct_form', 'question' => 'She ___ TV when I called her.', 'options' => ['was watching', 'watched', 'watches', 'is watching'], 'correct_answer' => 'was watching', 'explanation' => 'Past continuous for actions in progress.', 'difficulty' => 'medium'],
            ['question_type' => 'grammar_fill_gap', 'question' => 'Have you ever ___ to London?', 'options' => ['been', 'be', 'being', 'was'], 'correct_answer' => 'been', 'explanation' => 'Present perfect with "have/has + past participle".', 'difficulty' => 'medium'],

            ['question_type' => 'vocabulary_synonym', 'question' => 'What is a synonym for "happy"?', 'options' => ['Joyful', 'Sad', 'Angry', 'Tired'], 'correct_answer' => 'Joyful', 'difficulty' => 'medium'],
            ['question_type' => 'vocabulary_synonym', 'question' => 'What is a synonym for "big"?', 'options' => ['Large', 'Small', 'Tiny', 'Little'], 'correct_answer' => 'Large', 'difficulty' => 'medium'],

            ['question_type' => 'vocabulary_antonym', 'question' => 'What is the opposite of "hot"?', 'options' => ['Cold', 'Warm', 'Cool', 'Mild'], 'correct_answer' => 'Cold', 'difficulty' => 'medium'],
            ['question_type' => 'vocabulary_antonym', 'question' => 'What is the opposite of "fast"?', 'options' => ['Slow', 'Quick', 'Rapid', 'Swift'], 'correct_answer' => 'Slow', 'difficulty' => 'medium'],
        ];

        // B1 Level Questions
        $b1Questions = [
            ['question_type' => 'grammar_correct_form', 'question' => 'If I ___ you, I would accept the job.', 'options' => ['were', 'was', 'am', 'be'], 'correct_answer' => 'were', 'explanation' => 'Second conditional uses "were" for all subjects.', 'difficulty' => 'hard'],
            ['question_type' => 'grammar_correct_form', 'question' => 'By this time next year, I ___ graduated.', 'options' => ['will have', 'will be', 'have', 'had'], 'correct_answer' => 'will have', 'explanation' => 'Future perfect: will have + past participle.', 'difficulty' => 'hard'],

            ['question_type' => 'vocabulary_meaning', 'question' => 'What does "break a leg" mean?', 'options' => ['Good luck', 'Be careful', 'Run fast', 'Get injured'], 'correct_answer' => 'Good luck', 'explanation' => '"Break a leg" means good luck, especially before performances.', 'difficulty' => 'hard'],
            ['question_type' => 'vocabulary_meaning', 'question' => 'What does "piece of cake" mean?', 'options' => ['Very easy', 'Delicious food', 'Sweet reward', 'Birthday party'], 'correct_answer' => 'Very easy', 'explanation' => '"Piece of cake" means something is very easy to do.', 'difficulty' => 'hard'],

            ['question_type' => 'grammar_fill_gap', 'question' => 'I wish I ___ speak more languages.', 'options' => ['could', 'can', 'would', 'should'], 'correct_answer' => 'could', 'explanation' => 'We use "could" with wish for abilities we want.', 'difficulty' => 'hard'],
        ];

        // Insert all questions
        $this->insertQuestions($a1Questions, $levels['A1']->id);
        $this->insertQuestions($a2Questions, $levels['A2']->id);
        $this->insertQuestions($b1Questions, $levels['B1']->id);

        $count = count($a1Questions) + count($a2Questions) + count($b1Questions);
        $this->command->info("✅ Created {$count} battle questions");
    }

    private function insertQuestions(array $questions, string $levelId): void
    {
        foreach ($questions as $q) {
            EnglishBattleQuestion::create([
                'id' => Str::uuid(),
                'level_id' => $levelId,
                'question_type' => $q['question_type'],
                'question' => $q['question'],
                'question_uz' => $q['question_uz'] ?? null,
                'options' => json_encode($q['options']),
                'correct_answer' => $q['correct_answer'],
                'explanation' => $q['explanation'] ?? null,
                'difficulty' => $q['difficulty'],
                'base_points' => $q['difficulty'] === 'easy' ? 10 : ($q['difficulty'] === 'medium' ? 15 : 20),
                'is_active' => true,
            ]);
        }
    }
}
