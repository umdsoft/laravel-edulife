<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishTopic;
use App\Models\English\EnglishUnit;
use App\Models\English\EnglishLesson;
use App\Models\English\EnglishWord;
use App\Models\English\EnglishGrammarRule;
use App\Models\English\EnglishQuestion;

class A1GreetingsSeeder extends Seeder
{
    public function run(): void
    {
        $topic = EnglishTopic::where('code', 'greetings')->first();

        if (!$topic) {
            $this->command->error('Topic "greetings" not found!');
            return;
        }

        // Unit 1: Basic Hello & Goodbye
        $unit1 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'basic_hello',
            'name' => 'Basic Greetings',
            'name_uz' => 'Asosiy salomlashish',
            'description' => 'Learn to say hello and goodbye',
            'description_uz' => 'Salom va xayr deyishni o\'rganing',
            'icon' => '👋',
            'order' => 1,
            'xp_reward' => 50,
        ]);

        // Lesson 1.1: Hello & Goodbye Vocabulary
        $lesson1 = EnglishLesson::create([
            'unit_id' => $unit1->id,
            'code' => 'hello_goodbye',
            'name' => 'Hello & Goodbye',
            'name_uz' => 'Salom va Xayr',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 10,
            'estimated_time' => 5,
        ]);

        $words1 = [
            ['word' => 'hello', 'phonetic' => '/həˈloʊ/', 'translation_uz' => 'salom', 'translation_ru' => 'привет', 'example' => 'Hello! How are you?', 'pos' => 'interjection'],
            ['word' => 'hi', 'phonetic' => '/haɪ/', 'translation_uz' => 'salom', 'translation_ru' => 'привет', 'example' => 'Hi there!', 'pos' => 'interjection'],
            ['word' => 'goodbye', 'phonetic' => '/ɡʊdˈbaɪ/', 'translation_uz' => 'xayr', 'translation_ru' => 'до свидания', 'example' => 'Goodbye! See you tomorrow.', 'pos' => 'interjection'],
            ['word' => 'bye', 'phonetic' => '/baɪ/', 'translation_uz' => 'xayr', 'translation_ru' => 'пока', 'example' => 'Bye! Take care.', 'pos' => 'interjection'],
            ['word' => 'good morning', 'phonetic' => '/ɡʊd ˈmɔːrnɪŋ/', 'translation_uz' => 'xayrli tong', 'translation_ru' => 'доброе утро', 'example' => 'Good morning! Did you sleep well?', 'pos' => 'phrase'],
            ['word' => 'good afternoon', 'phonetic' => '/ɡʊd ˌæftərˈnuːn/', 'translation_uz' => 'xayrli kun', 'translation_ru' => 'добрый день', 'example' => 'Good afternoon, everyone!', 'pos' => 'phrase'],
            ['word' => 'good evening', 'phonetic' => '/ɡʊd ˈiːvnɪŋ/', 'translation_uz' => 'xayrli oqshom', 'translation_ru' => 'добрый вечер', 'example' => 'Good evening! Welcome.', 'pos' => 'phrase'],
            ['word' => 'good night', 'phonetic' => '/ɡʊd naɪt/', 'translation_uz' => 'xayrli tun', 'translation_ru' => 'спокойной ночи', 'example' => 'Good night! Sleep tight.', 'pos' => 'phrase'],
        ];

        foreach ($words1 as $w) {
            EnglishWord::create([
                'lesson_id' => $lesson1->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $w['word'],
                'phonetic' => $w['phonetic'],
                'translation_uz' => $w['translation_uz'],
                'translation_ru' => $w['translation_ru'],
                'example_sentence' => $w['example'],
                'part_of_speech' => $w['pos'],
                'difficulty' => 1,
            ]);
        }

        // Questions for Lesson 1.1
        $questions1 = [
            ['type' => 'single_choice', 'question' => 'What do you say when you meet someone in the morning?', 'question_uz' => 'Ertalab kimnidir uchratganingizda nima deysiz?', 'options' => ['Good night', 'Good morning', 'Goodbye', 'Good evening'], 'correct' => 'Good morning', 'points' => 10],
            ['type' => 'single_choice', 'question' => 'Which word is informal for "goodbye"?', 'options' => ['Hello', 'Bye', 'Good evening', 'Good morning'], 'correct' => 'Bye', 'points' => 10],
            ['type' => 'fill_blank', 'question' => 'Complete: "___! How are you today?"', 'correct' => 'Hello', 'points' => 15],
            ['type' => 'true_false', 'question' => '"Hello" and "Hi" mean the same thing.', 'correct' => 'true', 'points' => 10],
            ['type' => 'single_choice', 'question' => 'What do you say before going to sleep?', 'options' => ['Good morning', 'Good afternoon', 'Good night', 'Hello'], 'correct' => 'Good night', 'points' => 10],
        ];

        foreach ($questions1 as $q) {
            EnglishQuestion::create([
                'lesson_id' => $lesson1->id,
                'level_id' => $topic->level_id,
                'type' => $q['type'],
                'question' => $q['question'],
                'question_uz' => $q['question_uz'] ?? null,
                'options' => isset($q['options']) ? json_encode($q['options']) : null,
                'correct_answer' => $q['correct'],
                'points' => $q['points'],
            ]);
        }

        // Lesson 1.2: How Are You?
        $lesson2 = EnglishLesson::create([
            'unit_id' => $unit1->id,
            'code' => 'how_are_you',
            'name' => 'How Are You?',
            'name_uz' => 'Qandaysiz?',
            'type' => 'conversation',
            'order' => 2,
            'xp_reward' => 15,
            'estimated_time' => 7,
        ]);

        $words2 = [
            ['word' => 'how', 'phonetic' => '/haʊ/', 'translation_uz' => 'qanday', 'translation_ru' => 'как', 'pos' => 'adverb'],
            ['word' => 'fine', 'phonetic' => '/faɪn/', 'translation_uz' => 'yaxshi', 'translation_ru' => 'хорошо', 'pos' => 'adjective'],
            ['word' => 'great', 'phonetic' => '/ɡreɪt/', 'translation_uz' => 'ajoyib', 'translation_ru' => 'отлично', 'pos' => 'adjective'],
            ['word' => 'okay', 'phonetic' => '/oʊˈkeɪ/', 'translation_uz' => 'yaxshi', 'translation_ru' => 'нормально', 'pos' => 'adjective'],
            ['word' => 'thank you', 'phonetic' => '/θæŋk juː/', 'translation_uz' => 'rahmat', 'translation_ru' => 'спасибо', 'pos' => 'phrase'],
            ['word' => 'thanks', 'phonetic' => '/θæŋks/', 'translation_uz' => 'rahmat', 'translation_ru' => 'спасибо', 'pos' => 'interjection'],
            ['word' => 'please', 'phonetic' => '/pliːz/', 'translation_uz' => 'iltimos', 'translation_ru' => 'пожалуйста', 'pos' => 'adverb'],
            ['word' => "you're welcome", 'phonetic' => '/jʊər ˈwelkəm/', 'translation_uz' => 'arzimaydi', 'translation_ru' => 'пожалуйста', 'pos' => 'phrase'],
        ];

        foreach ($words2 as $w) {
            EnglishWord::create([
                'lesson_id' => $lesson2->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $w['word'],
                'phonetic' => $w['phonetic'],
                'translation_uz' => $w['translation_uz'],
                'translation_ru' => $w['translation_ru'],
                'part_of_speech' => $w['pos'],
                'difficulty' => 1,
            ]);
        }

        // Grammar for Lesson 1.2
        EnglishGrammarRule::create([
            'lesson_id' => $lesson2->id,
            'level_id' => $topic->level_id,
            'title' => 'The verb "to be" - Present Simple',
            'title_uz' => '"To be" fe\'li - Oddiy hozirgi zamon',
            'explanation' => 'The verb "to be" changes based on the subject: I am, You are, He/She/It is, We/They are.',
            'explanation_uz' => '"To be" fe\'li egaga qarab o\'zgaradi: I am, You are, He/She/It is, We/They are.',
            'formula' => 'Subject + am/is/are + complement',
            'examples' => json_encode([
                ['sentence' => 'I am happy.', 'translation' => 'Men xursandman.'],
                ['sentence' => 'You are my friend.', 'translation' => 'Siz mening do\'stimsiz.'],
                ['sentence' => 'He is a teacher.', 'translation' => 'U o\'qituvchi.'],
                ['sentence' => 'We are students.', 'translation' => 'Biz talabamiz.'],
            ]),
            'tips' => json_encode([
                'Use "am" only with "I"',
                'Use "is" with he, she, it',
                'Use "are" with you, we, they',
            ]),
        ]);

        // Unit 2: Introducing Yourself
        $unit2 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'introducing_yourself',
            'name' => 'Introducing Yourself',
            'name_uz' => 'O\'zingizni tanishtirish',
            'description' => 'Learn to introduce yourself',
            'icon' => '🙋',
            'order' => 2,
            'xp_reward' => 60,
        ]);

        $lesson3 = EnglishLesson::create([
            'unit_id' => $unit2->id,
            'code' => 'my_name_is',
            'name' => 'My Name Is...',
            'name_uz' => 'Mening ismim...',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 15,
            'estimated_time' => 6,
        ]);

        $words3 = [
            ['word' => 'name', 'phonetic' => '/neɪm/', 'translation_uz' => 'ism', 'translation_ru' => 'имя', 'example' => 'My name is John.', 'pos' => 'noun'],
            ['word' => 'my', 'phonetic' => '/maɪ/', 'translation_uz' => 'mening', 'translation_ru' => 'мой', 'example' => 'This is my book.', 'pos' => 'pronoun'],
            ['word' => 'your', 'phonetic' => '/jʊr/', 'translation_uz' => 'sizning', 'translation_ru' => 'твой/ваш', 'example' => 'What is your name?', 'pos' => 'pronoun'],
            ['word' => 'I', 'phonetic' => '/aɪ/', 'translation_uz' => 'men', 'translation_ru' => 'я', 'example' => 'I am a student.', 'pos' => 'pronoun'],
            ['word' => 'you', 'phonetic' => '/juː/', 'translation_uz' => 'siz', 'translation_ru' => 'ты/вы', 'example' => 'You are nice.', 'pos' => 'pronoun'],
            ['word' => 'nice to meet you', 'phonetic' => '/naɪs tuː miːt juː/', 'translation_uz' => 'tanishganimdan xursandman', 'translation_ru' => 'приятно познакомиться', 'pos' => 'phrase'],
            ['word' => 'where', 'phonetic' => '/wer/', 'translation_uz' => 'qayerda', 'translation_ru' => 'где', 'example' => 'Where are you from?', 'pos' => 'adverb'],
            ['word' => 'from', 'phonetic' => '/frɒm/', 'translation_uz' => '...dan', 'translation_ru' => 'из', 'example' => 'I am from Uzbekistan.', 'pos' => 'preposition'],
        ];

        foreach ($words3 as $w) {
            EnglishWord::create([
                'lesson_id' => $lesson3->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $w['word'],
                'phonetic' => $w['phonetic'],
                'translation_uz' => $w['translation_uz'],
                'translation_ru' => $w['translation_ru'],
                'example_sentence' => $w['example'] ?? null,
                'part_of_speech' => $w['pos'],
                'difficulty' => 1,
            ]);
        }

        // Unit 3: Meeting People
        $unit3 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'meeting_people',
            'name' => 'Meeting People',
            'name_uz' => 'Odamlar bilan tanishish',
            'description' => 'Learn to meet new people',
            'icon' => '🤝',
            'order' => 3,
            'xp_reward' => 70,
        ]);

        // Unit 4: Formal Greetings
        $unit4 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'formal_greetings',
            'name' => 'Formal Greetings',
            'name_uz' => 'Rasmiy salomlashish',
            'description' => 'Learn formal greetings',
            'icon' => '🎩',
            'order' => 4,
            'xp_reward' => 80,
        ]);

        // Unit 5: Saying Goodbye
        $unit5 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'saying_goodbye',
            'name' => 'Saying Goodbye',
            'name_uz' => 'Xayrlashish',
            'description' => 'Different ways to say goodbye',
            'icon' => '👋',
            'order' => 5,
            'xp_reward' => 60,
        ]);

        // Unit 6: Topic Review
        $unit6 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'greetings_review',
            'name' => 'Topic Review & Test',
            'name_uz' => 'Mavzu sharhi va test',
            'description' => 'Review all greetings and take the final test',
            'icon' => '📝',
            'order' => 6,
            'xp_reward' => 100,
            'is_final_test' => true,
        ]);

        $this->command->info('✅ A1 Greetings topic seeded with 6 units and content');
    }
}
