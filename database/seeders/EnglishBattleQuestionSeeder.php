<?php

namespace Database\Seeders;

use App\Models\English\EnglishBattleQuestion;
use App\Models\English\EnglishLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class EnglishBattleQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding battle questions...');

        // Load questions from JSON files
        $this->loadQuestionsFromJson('easy');
        $this->loadQuestionsFromJson('medium');
        $this->loadQuestionsFromJson('hard');

        // Also seed level-specific questions
        $levels = EnglishLevel::all()->keyBy('code');

        if ($levels->isNotEmpty()) {
            $this->createA1Questions($levels['A1']?->id);
            $this->createA2Questions($levels['A2']?->id);
            $this->createB1Questions($levels['B1']?->id);
            $this->createB2Questions($levels['B2']?->id);
        }

        $totalCount = EnglishBattleQuestion::count();
        $this->command->info("Battle questions seeded successfully! Total: {$totalCount} questions");
    }

    /**
     * Load questions from JSON file by difficulty
     */
    private function loadQuestionsFromJson(string $difficulty): void
    {
        $path = base_path("data/english/games/battle/{$difficulty}.json");

        if (!File::exists($path)) {
            $this->command->warn("File not found: {$path}");
            return;
        }

        $content = File::get($path);
        $questions = json_decode($content, true);

        if (!is_array($questions)) {
            $this->command->error("Invalid JSON in {$path}");
            return;
        }

        $count = 0;
        foreach ($questions as $q) {
            // Skip if question already exists
            if (EnglishBattleQuestion::where('question', $q['question'])->exists()) {
                continue;
            }

            EnglishBattleQuestion::create([
                'id' => Str::uuid(),
                'level_id' => null, // General questions not tied to specific level
                'question_type' => $q['question_type'] ?? 'vocabulary_meaning',
                'question' => $q['question'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'difficulty' => $q['difficulty'] ?? $difficulty,
                'base_points' => $this->getBasePoints($q['difficulty'] ?? $difficulty),
                'time_bonus_max' => 5,
                'is_active' => true,
            ]);
            $count++;
        }

        $this->command->info("Loaded {$count} {$difficulty} questions from JSON");
    }

    /**
     * Get base points for difficulty
     */
    private function getBasePoints(string $difficulty): int
    {
        return match ($difficulty) {
            'easy' => 8,
            'medium' => 10,
            'hard' => 12,
            default => 10,
        };
    }

    private function createA1Questions(?string $levelId): void
    {
        if (!$levelId) return;

        $questions = [
            // Vocabulary - Meaning
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "apple" mean?',
                'options' => ['A fruit', 'A vegetable', 'A drink', 'A meal'],
                'correct_answer' => 'A fruit',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "water" mean?',
                'options' => ['A liquid to drink', 'A food', 'A color', 'A number'],
                'correct_answer' => 'A liquid to drink',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "book" mean?',
                'options' => ['Something to read', 'Something to eat', 'Something to wear', 'Something to drink'],
                'correct_answer' => 'Something to read',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What is a "cat"?',
                'options' => ['An animal', 'A fruit', 'A vehicle', 'A building'],
                'correct_answer' => 'An animal',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What is "happy"?',
                'options' => ['A feeling of joy', 'A color', 'A size', 'A number'],
                'correct_answer' => 'A feeling of joy',
                'difficulty' => 'easy',
            ],

            // Grammar - Correct Form
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "She ___ a student."',
                'options' => ['is', 'am', 'are', 'be'],
                'correct_answer' => 'is',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "I ___ from Uzbekistan."',
                'options' => ['am', 'is', 'are', 'be'],
                'correct_answer' => 'am',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "They ___ my friends."',
                'options' => ['are', 'is', 'am', 'be'],
                'correct_answer' => 'are',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "This ___ my book."',
                'options' => ['is', 'are', 'am', 'be'],
                'correct_answer' => 'is',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "We ___ happy."',
                'options' => ['are', 'is', 'am', 'be'],
                'correct_answer' => 'are',
                'difficulty' => 'easy',
            ],

            // Vocabulary - Fill Gap
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'The opposite of "big" is ___.',
                'options' => ['small', 'tall', 'long', 'wide'],
                'correct_answer' => 'small',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'The color of grass is ___.',
                'options' => ['green', 'blue', 'red', 'yellow'],
                'correct_answer' => 'green',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'The number after five is ___.',
                'options' => ['six', 'four', 'seven', 'three'],
                'correct_answer' => 'six',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'The opposite of "hot" is ___.',
                'options' => ['cold', 'warm', 'cool', 'nice'],
                'correct_answer' => 'cold',
                'difficulty' => 'easy',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'We use ___ to write.',
                'options' => ['a pen', 'a cup', 'a plate', 'a phone'],
                'correct_answer' => 'a pen',
                'difficulty' => 'easy',
            ],

            // Grammar - Error Find
            [
                'question_type' => 'grammar_error_find',
                'question' => 'Find the error: "She am a teacher."',
                'options' => ['am', 'She', 'a', 'teacher'],
                'correct_answer' => 'am',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_error_find',
                'question' => 'Find the error: "They is students."',
                'options' => ['is', 'They', 'students', 'No error'],
                'correct_answer' => 'is',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_error_find',
                'question' => 'Find the error: "I has a book."',
                'options' => ['has', 'I', 'a', 'book'],
                'correct_answer' => 'has',
                'difficulty' => 'medium',
            ],

            // Vocabulary - Synonym
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'Which word means the same as "big"?',
                'options' => ['large', 'small', 'tiny', 'little'],
                'correct_answer' => 'large',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'Which word means the same as "happy"?',
                'options' => ['joyful', 'sad', 'angry', 'tired'],
                'correct_answer' => 'joyful',
                'difficulty' => 'medium',
            ],
        ];

        $this->insertQuestions($questions, $levelId);
    }

    private function createA2Questions(?string $levelId): void
    {
        if (!$levelId) return;

        $questions = [
            // Vocabulary - Meaning
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "colleague" mean?',
                'options' => ['A person you work with', 'A family member', 'A neighbor', 'A stranger'],
                'correct_answer' => 'A person you work with',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "appointment" mean?',
                'options' => ['A planned meeting', 'A gift', 'A place', 'A tool'],
                'correct_answer' => 'A planned meeting',
                'difficulty' => 'medium',
            ],

            // Grammar - Correct Form (Past Tense)
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "Yesterday, I ___ to school."',
                'options' => ['went', 'go', 'goes', 'going'],
                'correct_answer' => 'went',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "She ___ a letter last night."',
                'options' => ['wrote', 'write', 'writes', 'writing'],
                'correct_answer' => 'wrote',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "They ___ the movie yesterday."',
                'options' => ['watched', 'watch', 'watches', 'watching'],
                'correct_answer' => 'watched',
                'difficulty' => 'medium',
            ],

            // Grammar - Present Continuous
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "Look! She ___ now."',
                'options' => ['is dancing', 'dances', 'dance', 'danced'],
                'correct_answer' => 'is dancing',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "We ___ dinner at the moment."',
                'options' => ['are having', 'have', 'has', 'had'],
                'correct_answer' => 'are having',
                'difficulty' => 'medium',
            ],

            // Vocabulary - Antonym
            [
                'question_type' => 'vocabulary_antonym',
                'question' => 'What is the opposite of "expensive"?',
                'options' => ['cheap', 'costly', 'pricey', 'valuable'],
                'correct_answer' => 'cheap',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_antonym',
                'question' => 'What is the opposite of "remember"?',
                'options' => ['forget', 'recall', 'memorize', 'think'],
                'correct_answer' => 'forget',
                'difficulty' => 'medium',
            ],

            // Grammar - Fill Gap
            [
                'question_type' => 'grammar_fill_gap',
                'question' => 'I have ___ been to Paris.',
                'options' => ['never', 'ever', 'always', 'often'],
                'correct_answer' => 'never',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_fill_gap',
                'question' => 'She is ___ than her sister.',
                'options' => ['taller', 'tall', 'tallest', 'more tall'],
                'correct_answer' => 'taller',
                'difficulty' => 'medium',
            ],

            // Vocabulary - Fill Gap
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'I need to ___ my homework before dinner.',
                'options' => ['finish', 'start', 'begin', 'stop'],
                'correct_answer' => 'finish',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'Can you ___ me the salt, please?',
                'options' => ['pass', 'throw', 'drop', 'catch'],
                'correct_answer' => 'pass',
                'difficulty' => 'medium',
            ],

            // Grammar - Error Find
            [
                'question_type' => 'grammar_error_find',
                'question' => 'Find the error: "He don\'t like coffee."',
                'options' => ['don\'t', 'He', 'like', 'coffee'],
                'correct_answer' => 'don\'t',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_error_find',
                'question' => 'Find the error: "She have two cats."',
                'options' => ['have', 'She', 'two', 'cats'],
                'correct_answer' => 'have',
                'difficulty' => 'medium',
            ],

            // Vocabulary - Synonym
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'Which word means the same as "begin"?',
                'options' => ['start', 'end', 'finish', 'stop'],
                'correct_answer' => 'start',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'Which word means the same as "fast"?',
                'options' => ['quick', 'slow', 'late', 'early'],
                'correct_answer' => 'quick',
                'difficulty' => 'medium',
            ],

            // Grammar - Correct Form (Future)
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "I ___ call you tomorrow."',
                'options' => ['will', 'am', 'was', 'have'],
                'correct_answer' => 'will',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "They ___ going to travel next month."',
                'options' => ['are', 'is', 'be', 'been'],
                'correct_answer' => 'are',
                'difficulty' => 'medium',
            ],
        ];

        $this->insertQuestions($questions, $levelId);
    }

    private function createB1Questions(?string $levelId): void
    {
        if (!$levelId) return;

        $questions = [
            // Vocabulary - Advanced
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "accomplish" mean?',
                'options' => ['To achieve something', 'To fail', 'To try', 'To stop'],
                'correct_answer' => 'To achieve something',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "ambitious" mean?',
                'options' => ['Having strong desire to succeed', 'Being lazy', 'Being tired', 'Being sad'],
                'correct_answer' => 'Having strong desire to succeed',
                'difficulty' => 'medium',
            ],

            // Grammar - Present Perfect
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "I ___ this movie before."',
                'options' => ['have seen', 'saw', 'see', 'seeing'],
                'correct_answer' => 'have seen',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "She ___ in London for five years."',
                'options' => ['has lived', 'lived', 'lives', 'living'],
                'correct_answer' => 'has lived',
                'difficulty' => 'medium',
            ],

            // Grammar - Conditionals
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "If it rains, I ___ at home."',
                'options' => ['will stay', 'stayed', 'stay', 'staying'],
                'correct_answer' => 'will stay',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "If I ___ rich, I would travel the world."',
                'options' => ['were', 'am', 'was', 'be'],
                'correct_answer' => 'were',
                'difficulty' => 'hard',
            ],

            // Vocabulary - Collocations
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'You should ___ a decision soon.',
                'options' => ['make', 'do', 'take', 'have'],
                'correct_answer' => 'make',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'I need to ___ my homework.',
                'options' => ['do', 'make', 'take', 'get'],
                'correct_answer' => 'do',
                'difficulty' => 'medium',
            ],

            // Grammar - Passive Voice
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "The book ___ by millions of people."',
                'options' => ['was read', 'read', 'reads', 'reading'],
                'correct_answer' => 'was read',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Choose the correct form: "English ___ in many countries."',
                'options' => ['is spoken', 'speaks', 'spoke', 'speaking'],
                'correct_answer' => 'is spoken',
                'difficulty' => 'hard',
            ],

            // Vocabulary - Phrasal Verbs
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "give up" mean?',
                'options' => ['To stop trying', 'To start', 'To continue', 'To improve'],
                'correct_answer' => 'To stop trying',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "look after" mean?',
                'options' => ['To take care of', 'To search', 'To watch', 'To ignore'],
                'correct_answer' => 'To take care of',
                'difficulty' => 'medium',
            ],

            // Grammar - Reported Speech
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'He said he ___ tired.',
                'options' => ['was', 'is', 'be', 'being'],
                'correct_answer' => 'was',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'She told me she ___ help me.',
                'options' => ['would', 'will', 'can', 'may'],
                'correct_answer' => 'would',
                'difficulty' => 'hard',
            ],

            // Vocabulary - Idioms
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "break the ice" mean?',
                'options' => ['To start a conversation', 'To break something', 'To feel cold', 'To stop talking'],
                'correct_answer' => 'To start a conversation',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "piece of cake" mean?',
                'options' => ['Something very easy', 'A dessert', 'Something difficult', 'A celebration'],
                'correct_answer' => 'Something very easy',
                'difficulty' => 'hard',
            ],

            // Grammar - Modal Verbs
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'You ___ wear a seatbelt. It\'s the law.',
                'options' => ['must', 'might', 'could', 'would'],
                'correct_answer' => 'must',
                'difficulty' => 'medium',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'She ___ be at home. I saw her car.',
                'options' => ['might', 'must', 'can', 'will'],
                'correct_answer' => 'might',
                'difficulty' => 'hard',
            ],
        ];

        $this->insertQuestions($questions, $levelId);
    }

    private function createB2Questions(?string $levelId): void
    {
        if (!$levelId) return;

        $questions = [
            // Advanced Vocabulary
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "inevitable" mean?',
                'options' => ['Certain to happen', 'Unlikely', 'Possible', 'Rare'],
                'correct_answer' => 'Certain to happen',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "substantial" mean?',
                'options' => ['Large in amount', 'Small', 'Tiny', 'Minimal'],
                'correct_answer' => 'Large in amount',
                'difficulty' => 'hard',
            ],

            // Advanced Grammar - Mixed Conditionals
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'If I had studied harder, I ___ the exam.',
                'options' => ['would have passed', 'would pass', 'will pass', 'passed'],
                'correct_answer' => 'would have passed',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'If she ___ earlier, she wouldn\'t have missed the train.',
                'options' => ['had left', 'left', 'leaves', 'would leave'],
                'correct_answer' => 'had left',
                'difficulty' => 'hard',
            ],

            // Advanced Vocabulary - Academic
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "consequently" mean?',
                'options' => ['As a result', 'However', 'Although', 'Despite'],
                'correct_answer' => 'As a result',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "comprehensive" mean?',
                'options' => ['Complete and thorough', 'Partial', 'Brief', 'Simple'],
                'correct_answer' => 'Complete and thorough',
                'difficulty' => 'hard',
            ],

            // Grammar - Inversion
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Not only ___ intelligent, but she is also kind.',
                'options' => ['is she', 'she is', 'she was', 'was she'],
                'correct_answer' => 'is she',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'Rarely ___ such a beautiful sunset.',
                'options' => ['have I seen', 'I have seen', 'I saw', 'did I saw'],
                'correct_answer' => 'have I seen',
                'difficulty' => 'hard',
            ],

            // Vocabulary - Collocations Advanced
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'The company decided to ___ legal action.',
                'options' => ['take', 'make', 'do', 'have'],
                'correct_answer' => 'take',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_fill_gap',
                'question' => 'We need to ___ attention to detail.',
                'options' => ['pay', 'give', 'make', 'do'],
                'correct_answer' => 'pay',
                'difficulty' => 'hard',
            ],

            // Grammar - Subjunctive
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'I suggest that he ___ more careful.',
                'options' => ['be', 'is', 'was', 'being'],
                'correct_answer' => 'be',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'It is essential that every student ___ on time.',
                'options' => ['arrive', 'arrives', 'arrived', 'arriving'],
                'correct_answer' => 'arrive',
                'difficulty' => 'hard',
            ],

            // Advanced Idioms
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "turn a blind eye" mean?',
                'options' => ['To ignore something deliberately', 'To look carefully', 'To be blind', 'To close your eyes'],
                'correct_answer' => 'To ignore something deliberately',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_meaning',
                'question' => 'What does "the ball is in your court" mean?',
                'options' => ['It\'s your decision now', 'You play tennis', 'You won', 'You lost'],
                'correct_answer' => 'It\'s your decision now',
                'difficulty' => 'hard',
            ],

            // Grammar - Cleft Sentences
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'It was John ___ broke the window.',
                'options' => ['who', 'which', 'what', 'whom'],
                'correct_answer' => 'who',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'grammar_correct_form',
                'question' => 'What I need ___ a vacation.',
                'options' => ['is', 'are', 'was', 'were'],
                'correct_answer' => 'is',
                'difficulty' => 'hard',
            ],

            // Vocabulary - Formal vs Informal
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'What is the formal word for "get"?',
                'options' => ['obtain', 'grab', 'take', 'catch'],
                'correct_answer' => 'obtain',
                'difficulty' => 'hard',
            ],
            [
                'question_type' => 'vocabulary_synonym',
                'question' => 'What is the formal word for "help"?',
                'options' => ['assist', 'aid', 'support', 'All of these'],
                'correct_answer' => 'All of these',
                'difficulty' => 'hard',
            ],
        ];

        $this->insertQuestions($questions, $levelId);
    }

    private function insertQuestions(array $questions, string $levelId): void
    {
        foreach ($questions as $q) {
            // Skip if question already exists
            if (EnglishBattleQuestion::where('question', $q['question'])->exists()) {
                continue;
            }

            EnglishBattleQuestion::create([
                'id' => Str::uuid(),
                'level_id' => $levelId,
                'question_type' => $q['question_type'],
                'question' => $q['question'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'difficulty' => $q['difficulty'],
                'base_points' => $this->getBasePoints($q['difficulty']),
                'time_bonus_max' => 5,
                'is_active' => true,
            ]);
        }
    }
}
