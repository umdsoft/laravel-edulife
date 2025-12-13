<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishTopic;
use App\Models\English\EnglishUnit;
use App\Models\English\EnglishLesson;
use App\Models\English\EnglishWord;
use App\Models\English\EnglishGrammarRule;
use App\Models\English\EnglishQuestion;

class A1NumbersTimeSeeder extends Seeder
{
    public function run(): void
    {
        $topic = EnglishTopic::where('code', 'numbers_time')->first();

        if (!$topic) {
            $this->command->error('Topic "numbers_time" not found!');
            return;
        }

        // Unit 1: Numbers 1-10
        $unit1 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'numbers_1_10',
            'name' => 'Numbers 1-10',
            'name_uz' => 'Raqamlar 1-10',
            'description' => 'Learn numbers from 1 to 10',
            'icon' => '1️⃣',
            'order' => 1,
            'xp_reward' => 50,
        ]);

        $lesson1 = EnglishLesson::create([
            'unit_id' => $unit1->id,
            'code' => 'numbers_1_10_vocab',
            'name' => 'Numbers 1-10',
            'name_uz' => 'Raqamlar 1-10',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 10,
            'estimated_time' => 5,
        ]);

        $numbers1_10 = [
            ['word' => 'one', 'phonetic' => '/wʌn/', 'translation_uz' => 'bir', 'translation_ru' => 'один', 'value' => '1'],
            ['word' => 'two', 'phonetic' => '/tuː/', 'translation_uz' => 'ikki', 'translation_ru' => 'два', 'value' => '2'],
            ['word' => 'three', 'phonetic' => '/θriː/', 'translation_uz' => 'uch', 'translation_ru' => 'три', 'value' => '3'],
            ['word' => 'four', 'phonetic' => '/fɔːr/', 'translation_uz' => 'to\'rt', 'translation_ru' => 'четыре', 'value' => '4'],
            ['word' => 'five', 'phonetic' => '/faɪv/', 'translation_uz' => 'besh', 'translation_ru' => 'пять', 'value' => '5'],
            ['word' => 'six', 'phonetic' => '/sɪks/', 'translation_uz' => 'olti', 'translation_ru' => 'шесть', 'value' => '6'],
            ['word' => 'seven', 'phonetic' => '/ˈsevn/', 'translation_uz' => 'yetti', 'translation_ru' => 'семь', 'value' => '7'],
            ['word' => 'eight', 'phonetic' => '/eɪt/', 'translation_uz' => 'sakkiz', 'translation_ru' => 'восемь', 'value' => '8'],
            ['word' => 'nine', 'phonetic' => '/naɪn/', 'translation_uz' => 'to\'qqiz', 'translation_ru' => 'девять', 'value' => '9'],
            ['word' => 'ten', 'phonetic' => '/ten/', 'translation_uz' => 'o\'n', 'translation_ru' => 'десять', 'value' => '10'],
        ];

        foreach ($numbers1_10 as $n) {
            EnglishWord::create([
                'lesson_id' => $lesson1->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $n['word'],
                'phonetic' => $n['phonetic'],
                'translation_uz' => $n['translation_uz'],
                'translation_ru' => $n['translation_ru'],
                'example_sentence' => "I have {$n['word']} apples. ({$n['value']})",
                'part_of_speech' => 'noun',
                'difficulty' => 1,
            ]);
        }

        // Unit 2: Numbers 11-20
        $unit2 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'numbers_11_20',
            'name' => 'Numbers 11-20',
            'name_uz' => 'Raqamlar 11-20',
            'description' => 'Learn numbers from 11 to 20',
            'icon' => '🔢',
            'order' => 2,
            'xp_reward' => 50,
        ]);

        $lesson2 = EnglishLesson::create([
            'unit_id' => $unit2->id,
            'code' => 'numbers_11_20_vocab',
            'name' => 'Numbers 11-20',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 10,
            'estimated_time' => 5,
        ]);

        $numbers11_20 = [
            ['word' => 'eleven', 'phonetic' => '/ɪˈlevn/', 'translation_uz' => 'o\'n bir', 'translation_ru' => 'одиннадцать'],
            ['word' => 'twelve', 'phonetic' => '/twelv/', 'translation_uz' => 'o\'n ikki', 'translation_ru' => 'двенадцать'],
            ['word' => 'thirteen', 'phonetic' => '/ˌθɜːrˈtiːn/', 'translation_uz' => 'o\'n uch', 'translation_ru' => 'тринадцать'],
            ['word' => 'fourteen', 'phonetic' => '/ˌfɔːrˈtiːn/', 'translation_uz' => 'o\'n to\'rt', 'translation_ru' => 'четырнадцать'],
            ['word' => 'fifteen', 'phonetic' => '/ˌfɪfˈtiːn/', 'translation_uz' => 'o\'n besh', 'translation_ru' => 'пятнадцать'],
            ['word' => 'sixteen', 'phonetic' => '/ˌsɪksˈtiːn/', 'translation_uz' => 'o\'n olti', 'translation_ru' => 'шестнадцать'],
            ['word' => 'seventeen', 'phonetic' => '/ˌsevnˈtiːn/', 'translation_uz' => 'o\'n yetti', 'translation_ru' => 'семнадцать'],
            ['word' => 'eighteen', 'phonetic' => '/ˌeɪˈtiːn/', 'translation_uz' => 'o\'n sakkiz', 'translation_ru' => 'восемнадцать'],
            ['word' => 'nineteen', 'phonetic' => '/ˌnaɪnˈtiːn/', 'translation_uz' => 'o\'n to\'qqiz', 'translation_ru' => 'девятнадцать'],
            ['word' => 'twenty', 'phonetic' => '/ˈtwenti/', 'translation_uz' => 'yigirma', 'translation_ru' => 'двадцать'],
        ];

        foreach ($numbers11_20 as $n) {
            EnglishWord::create([
                'lesson_id' => $lesson2->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $n['word'],
                'phonetic' => $n['phonetic'],
                'translation_uz' => $n['translation_uz'],
                'translation_ru' => $n['translation_ru'],
                'part_of_speech' => 'noun',
                'difficulty' => 1,
            ]);
        }

        // Unit 3: Tens (30-100)
        $unit3 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'tens_numbers',
            'name' => 'Tens (30-100)',
            'name_uz' => 'O\'nliklar (30-100)',
            'description' => 'Learn tens from 30 to 100',
            'icon' => '💯',
            'order' => 3,
            'xp_reward' => 60,
        ]);

        // Unit 4: Days of the Week
        $unit4 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'days_of_week',
            'name' => 'Days of the Week',
            'name_uz' => 'Hafta kunlari',
            'description' => 'Learn all 7 days of the week',
            'icon' => '📅',
            'order' => 4,
            'xp_reward' => 50,
        ]);

        $daysLesson = EnglishLesson::create([
            'unit_id' => $unit4->id,
            'code' => 'days_vocab',
            'name' => 'Days of the Week',
            'name_uz' => 'Hafta kunlari',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 10,
            'estimated_time' => 5,
        ]);

        $days = [
            ['word' => 'Monday', 'phonetic' => '/ˈmʌndeɪ/', 'translation_uz' => 'Dushanba', 'translation_ru' => 'Понедельник'],
            ['word' => 'Tuesday', 'phonetic' => '/ˈtuːzdeɪ/', 'translation_uz' => 'Seshanba', 'translation_ru' => 'Вторник'],
            ['word' => 'Wednesday', 'phonetic' => '/ˈwenzdeɪ/', 'translation_uz' => 'Chorshanba', 'translation_ru' => 'Среда'],
            ['word' => 'Thursday', 'phonetic' => '/ˈθɜːrzdeɪ/', 'translation_uz' => 'Payshanba', 'translation_ru' => 'Четверг'],
            ['word' => 'Friday', 'phonetic' => '/ˈfraɪdeɪ/', 'translation_uz' => 'Juma', 'translation_ru' => 'Пятница'],
            ['word' => 'Saturday', 'phonetic' => '/ˈsætərdeɪ/', 'translation_uz' => 'Shanba', 'translation_ru' => 'Суббота'],
            ['word' => 'Sunday', 'phonetic' => '/ˈsʌndeɪ/', 'translation_uz' => 'Yakshanba', 'translation_ru' => 'Воскресенье'],
        ];

        foreach ($days as $d) {
            EnglishWord::create([
                'lesson_id' => $daysLesson->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $d['word'],
                'phonetic' => $d['phonetic'],
                'translation_uz' => $d['translation_uz'],
                'translation_ru' => $d['translation_ru'],
                'example_sentence' => "Today is {$d['word']}.",
                'part_of_speech' => 'noun',
                'difficulty' => 1,
            ]);
        }

        // Unit 5: Months
        $unit5 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'months',
            'name' => 'Months of the Year',
            'name_uz' => 'Yil oylari',
            'description' => 'Learn all 12 months',
            'icon' => '🗓️',
            'order' => 5,
            'xp_reward' => 60,
        ]);

        $monthsLesson = EnglishLesson::create([
            'unit_id' => $unit5->id,
            'code' => 'months_vocab',
            'name' => 'Months of the Year',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 15,
            'estimated_time' => 7,
        ]);

        $months = [
            ['word' => 'January', 'translation_uz' => 'Yanvar', 'translation_ru' => 'Январь'],
            ['word' => 'February', 'translation_uz' => 'Fevral', 'translation_ru' => 'Февраль'],
            ['word' => 'March', 'translation_uz' => 'Mart', 'translation_ru' => 'Март'],
            ['word' => 'April', 'translation_uz' => 'Aprel', 'translation_ru' => 'Апрель'],
            ['word' => 'May', 'translation_uz' => 'May', 'translation_ru' => 'Май'],
            ['word' => 'June', 'translation_uz' => 'Iyun', 'translation_ru' => 'Июнь'],
            ['word' => 'July', 'translation_uz' => 'Iyul', 'translation_ru' => 'Июль'],
            ['word' => 'August', 'translation_uz' => 'Avgust', 'translation_ru' => 'Август'],
            ['word' => 'September', 'translation_uz' => 'Sentabr', 'translation_ru' => 'Сентябрь'],
            ['word' => 'October', 'translation_uz' => 'Oktabr', 'translation_ru' => 'Октябрь'],
            ['word' => 'November', 'translation_uz' => 'Noyabr', 'translation_ru' => 'Ноябрь'],
            ['word' => 'December', 'translation_uz' => 'Dekabr', 'translation_ru' => 'Декабрь'],
        ];

        foreach ($months as $m) {
            EnglishWord::create([
                'lesson_id' => $monthsLesson->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $m['word'],
                'translation_uz' => $m['translation_uz'],
                'translation_ru' => $m['translation_ru'],
                'part_of_speech' => 'noun',
                'difficulty' => 1,
            ]);
        }

        // Unit 6: Telling Time
        $unit6 = EnglishUnit::create([
            'topic_id' => $topic->id,
            'code' => 'telling_time',
            'name' => 'Telling Time',
            'name_uz' => 'Vaqtni aytish',
            'description' => 'Learn to tell time',
            'icon' => '🕐',
            'order' => 6,
            'xp_reward' => 80,
        ]);

        $timeLesson = EnglishLesson::create([
            'unit_id' => $unit6->id,
            'code' => 'time_vocab',
            'name' => 'Time Vocabulary',
            'type' => 'vocabulary',
            'order' => 1,
            'xp_reward' => 15,
            'estimated_time' => 8,
        ]);

        $timeWords = [
            ['word' => 'clock', 'phonetic' => '/klɒk/', 'translation_uz' => 'soat', 'translation_ru' => 'часы', 'example' => 'Look at the clock.'],
            ['word' => 'hour', 'phonetic' => '/ˈaʊər/', 'translation_uz' => 'soat', 'translation_ru' => 'час', 'example' => 'One hour is 60 minutes.'],
            ['word' => 'minute', 'phonetic' => '/ˈmɪnɪt/', 'translation_uz' => 'daqiqa', 'translation_ru' => 'минута', 'example' => 'Wait a minute.'],
            ['word' => 'second', 'phonetic' => '/ˈsekənd/', 'translation_uz' => 'soniya', 'translation_ru' => 'секунда', 'example' => 'Just a second!'],
            ['word' => "o'clock", 'phonetic' => '/əˈklɒk/', 'translation_uz' => 'soat', 'translation_ru' => 'ровно', 'example' => "It's five o'clock."],
            ['word' => 'half past', 'phonetic' => '/hɑːf pɑːst/', 'translation_uz' => 'yarim', 'translation_ru' => 'половина', 'example' => "It's half past six."],
            ['word' => 'quarter', 'phonetic' => '/ˈkwɔːrtər/', 'translation_uz' => 'chorak', 'translation_ru' => 'четверть', 'example' => "It's quarter past three."],
        ];

        foreach ($timeWords as $t) {
            EnglishWord::create([
                'lesson_id' => $timeLesson->id,
                'level_id' => $topic->level_id,
                'topic_id' => $topic->id,
                'word' => $t['word'],
                'phonetic' => $t['phonetic'] ?? null,
                'translation_uz' => $t['translation_uz'],
                'translation_ru' => $t['translation_ru'],
                'example_sentence' => $t['example'] ?? null,
                'part_of_speech' => 'noun',
                'difficulty' => 1,
            ]);
        }

        // Grammar for time
        EnglishGrammarRule::create([
            'lesson_id' => $timeLesson->id,
            'level_id' => $topic->level_id,
            'title' => 'Asking and Telling Time',
            'title_uz' => 'Vaqtni so\'rash va aytish',
            'explanation' => 'To ask for the time, we say "What time is it?" or "What\'s the time?" To answer, we use "It\'s + time".',
            'explanation_uz' => 'Vaqtni so\'rash uchun "What time is it?" yoki "What\'s the time?" deymiz. Javob berish uchun "It\'s + vaqt" ishlatamiz.',
            'formula' => 'What time is it? → It\'s + [number] + o\'clock',
            'examples' => json_encode([
                ['sentence' => "It's three o'clock.", 'translation' => 'Soat uchda.'],
                ['sentence' => "It's half past seven.", 'translation' => 'Soat yettiyu yarim.'],
                ['sentence' => "It's quarter to nine.", 'translation' => 'Soat to\'qqizga chorak qoldi.'],
            ]),
        ]);

        $this->command->info('✅ A1 Numbers & Time topic seeded with 6 units');
    }
}
