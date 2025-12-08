<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishTopic;

class LevelTopicSeeder extends Seeder
{
    public function run(): void
    {
        // Create 6 CEFR Levels
        $levels = [
            ['code' => 'A1', 'name' => 'Beginner', 'name_uz' => 'Boshlang\'ich', 'description' => 'Basic everyday expressions and simple phrases', 'description_uz' => 'Kundalik oddiy iboralar va sodda jumlalar', 'order_number' => 1, 'color' => '#22C55E', 'icon' => '🌱', 'xp_required' => 0, 'vocabulary_target' => 500, 'grammar_structures' => 15, 'estimated_hours' => 100, 'ielts_band_min' => 1.0, 'ielts_band_max' => 2.5],
            ['code' => 'A2', 'name' => 'Elementary', 'name_uz' => 'Elementar', 'description' => 'Handle routine tasks and describe experiences', 'description_uz' => 'Kundalik vazifalarni bajaring va tajribalarni tasvirlang', 'order_number' => 2, 'color' => '#3B82F6', 'icon' => '🌿', 'xp_required' => 5000, 'vocabulary_target' => 1000, 'grammar_structures' => 25, 'estimated_hours' => 200, 'ielts_band_min' => 3.0, 'ielts_band_max' => 3.5],
            ['code' => 'B1', 'name' => 'Intermediate', 'name_uz' => 'O\'rta daraja', 'description' => 'Deal with most travel situations and describe dreams', 'description_uz' => 'Ko\'p vaziyatlarni hal qiling va orzularni tasvirlang', 'order_number' => 3, 'color' => '#8B5CF6', 'icon' => '🌳', 'xp_required' => 15000, 'vocabulary_target' => 2000, 'grammar_structures' => 35, 'estimated_hours' => 400, 'ielts_band_min' => 4.0, 'ielts_band_max' => 5.0],
            ['code' => 'B2', 'name' => 'Upper-Intermediate', 'name_uz' => 'O\'rta-yuqori', 'description' => 'Interact with fluency on a wide range of topics', 'description_uz' => 'Turli mavzularda erkin muloqot qiling', 'order_number' => 4, 'color' => '#F59E0B', 'icon' => '🌲', 'xp_required' => 30000, 'vocabulary_target' => 4000, 'grammar_structures' => 50, 'estimated_hours' => 600, 'ielts_band_min' => 5.5, 'ielts_band_max' => 6.5],
            ['code' => 'C1', 'name' => 'Advanced', 'name_uz' => 'Yuqori daraja', 'description' => 'Express ideas fluently and spontaneously', 'description_uz' => 'Fikrlarni ravon va o\'z-o\'zidan ifodalang', 'order_number' => 5, 'color' => '#EF4444', 'icon' => '🏔️', 'xp_required' => 50000, 'vocabulary_target' => 8000, 'grammar_structures' => 70, 'estimated_hours' => 800, 'ielts_band_min' => 7.0, 'ielts_band_max' => 8.0],
            ['code' => 'C2', 'name' => 'Proficient', 'name_uz' => 'Mukammal', 'description' => 'Understand virtually everything with ease', 'description_uz' => 'Deyarli hamma narsani osonlik bilan tushuning', 'order_number' => 6, 'color' => '#06B6D4', 'icon' => '👑', 'xp_required' => 80000, 'vocabulary_target' => 16000, 'grammar_structures' => 100, 'estimated_hours' => 1200, 'ielts_band_min' => 8.5, 'ielts_band_max' => 9.0],
        ];

        foreach ($levels as $levelData) {
            EnglishLevel::updateOrCreate(
                ['code' => $levelData['code']],
                $levelData
            );
        }

        $this->command->info('✅ Created 6 CEFR levels');

        // Create 48 Topics (8 per level group)
        $topics = [
            // A1 Topics
            ['slug' => 'greetings', 'name' => 'Greetings & Introductions', 'name_uz' => 'Salomlashish va tanishish', 'icon' => '👋', 'order_number' => 1],
            ['slug' => 'numbers_time', 'name' => 'Numbers & Time', 'name_uz' => 'Raqamlar va vaqt', 'icon' => '🔢', 'order_number' => 2],
            ['slug' => 'family_friends', 'name' => 'Family & Friends', 'name_uz' => 'Oila va do\'stlar', 'icon' => '👨‍👩‍👧‍👦', 'order_number' => 3],
            ['slug' => 'daily_routine', 'name' => 'Daily Routine', 'name_uz' => 'Kunlik tartib', 'icon' => '☀️', 'order_number' => 4],
            ['slug' => 'food_drinks', 'name' => 'Food & Drinks', 'name_uz' => 'Ovqat va ichimliklar', 'icon' => '🍕', 'order_number' => 5],
            ['slug' => 'shopping', 'name' => 'Shopping', 'name_uz' => 'Xarid qilish', 'icon' => '🛒', 'order_number' => 6],
            ['slug' => 'places_directions', 'name' => 'Places & Directions', 'name_uz' => 'Joylar va yo\'nalishlar', 'icon' => '🗺️', 'order_number' => 7],
            ['slug' => 'hobbies_leisure', 'name' => 'Hobbies & Leisure', 'name_uz' => 'Sevimli mashg\'ulotlar', 'icon' => '🎮', 'order_number' => 8],

            // A2 Topics
            ['slug' => 'travel_transport', 'name' => 'Travel & Transport', 'name_uz' => 'Sayohat va transport', 'icon' => '✈️', 'order_number' => 9],
            ['slug' => 'health_body', 'name' => 'Health & Body', 'name_uz' => 'Sog\'liq va tana', 'icon' => '🏥', 'order_number' => 10],
            ['slug' => 'work_jobs', 'name' => 'Work & Jobs', 'name_uz' => 'Ish va kasblar', 'icon' => '💼', 'order_number' => 11],
            ['slug' => 'weather_seasons', 'name' => 'Weather & Seasons', 'name_uz' => 'Ob-havo va fasllar', 'icon' => '🌤️', 'order_number' => 12],
            ['slug' => 'home_housing', 'name' => 'Home & Housing', 'name_uz' => 'Uy-joy', 'icon' => '🏠', 'order_number' => 13],
            ['slug' => 'education', 'name' => 'Education & Learning', 'name_uz' => 'Ta\'lim va o\'rganish', 'icon' => '📚', 'order_number' => 14],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'name_uz' => 'Ko\'ngilochar', 'icon' => '🎬', 'order_number' => 15],
            ['slug' => 'past_experiences', 'name' => 'Past Experiences', 'name_uz' => 'O\'tgan tajribalar', 'icon' => '📖', 'order_number' => 16],

            // B1 Topics
            ['slug' => 'relationships', 'name' => 'Relationships', 'name_uz' => 'Munosabatlar', 'icon' => '❤️', 'order_number' => 17],
            ['slug' => 'technology', 'name' => 'Technology', 'name_uz' => 'Texnologiya', 'icon' => '💻', 'order_number' => 18],
            ['slug' => 'environment', 'name' => 'Environment', 'name_uz' => 'Atrof-muhit', 'icon' => '🌍', 'order_number' => 19],
            ['slug' => 'culture_traditions', 'name' => 'Culture & Traditions', 'name_uz' => 'Madaniyat va an\'analar', 'icon' => '🎭', 'order_number' => 20],
            ['slug' => 'news_media', 'name' => 'News & Media', 'name_uz' => 'Yangiliklar va media', 'icon' => '📰', 'order_number' => 21],
            ['slug' => 'business_basics', 'name' => 'Business Basics', 'name_uz' => 'Biznes asoslari', 'icon' => '📊', 'order_number' => 22],
            ['slug' => 'opinions_debates', 'name' => 'Opinions & Debates', 'name_uz' => 'Fikrlar va munozaralar', 'icon' => '💬', 'order_number' => 23],
            ['slug' => 'future_plans', 'name' => 'Future Plans', 'name_uz' => 'Kelajak rejalari', 'icon' => '🎯', 'order_number' => 24],

            // B2 Topics
            ['slug' => 'current_affairs', 'name' => 'Current Affairs', 'name_uz' => 'Joriy masalalar', 'icon' => '🗞️', 'order_number' => 25],
            ['slug' => 'society_issues', 'name' => 'Society Issues', 'name_uz' => 'Jamiyat muammolari', 'icon' => '🏛️', 'order_number' => 26],
            ['slug' => 'science_research', 'name' => 'Science & Research', 'name_uz' => 'Fan va tadqiqot', 'icon' => '🔬', 'order_number' => 27],
            ['slug' => 'arts_literature', 'name' => 'Arts & Literature', 'name_uz' => 'San\'at va adabiyot', 'icon' => '🎨', 'order_number' => 28],
            ['slug' => 'economics', 'name' => 'Economics', 'name_uz' => 'Iqtisodiyot', 'icon' => '💹', 'order_number' => 29],
            ['slug' => 'global_challenges', 'name' => 'Global Challenges', 'name_uz' => 'Global qiyinchiliklar', 'icon' => '🌐', 'order_number' => 30],
            ['slug' => 'career_growth', 'name' => 'Career Growth', 'name_uz' => 'Karyera o\'sishi', 'icon' => '📈', 'order_number' => 31],
            ['slug' => 'innovation', 'name' => 'Innovation', 'name_uz' => 'Innovatsiya', 'icon' => '💡', 'order_number' => 32],

            // C1 Topics
            ['slug' => 'philosophy', 'name' => 'Philosophy', 'name_uz' => 'Falsafa', 'icon' => '🧠', 'order_number' => 33],
            ['slug' => 'advanced_business', 'name' => 'Advanced Business', 'name_uz' => 'Ilg\'or biznes', 'icon' => '🏢', 'order_number' => 34],
            ['slug' => 'psychology', 'name' => 'Psychology', 'name_uz' => 'Psixologiya', 'icon' => '🔮', 'order_number' => 35],
            ['slug' => 'politics', 'name' => 'Politics', 'name_uz' => 'Siyosat', 'icon' => '⚖️', 'order_number' => 36],
            ['slug' => 'legal_matters', 'name' => 'Legal Matters', 'name_uz' => 'Huquqiy masalalar', 'icon' => '📜', 'order_number' => 37],
            ['slug' => 'academic_writing', 'name' => 'Academic Writing', 'name_uz' => 'Ilmiy yozuv', 'icon' => '🎓', 'order_number' => 38],
            ['slug' => 'diplomacy', 'name' => 'Diplomacy', 'name_uz' => 'Diplomatiya', 'icon' => '🤝', 'order_number' => 39],
            ['slug' => 'complex_debates', 'name' => 'Complex Debates', 'name_uz' => 'Murakkab munozaralar', 'icon' => '⚡', 'order_number' => 40],

            // C2 Topics
            ['slug' => 'nuanced_expression', 'name' => 'Nuanced Expression', 'name_uz' => 'Nozik ifoda', 'icon' => '🎭', 'order_number' => 41],
            ['slug' => 'literary_analysis', 'name' => 'Literary Analysis', 'name_uz' => 'Adabiy tahlil', 'icon' => '📚', 'order_number' => 42],
            ['slug' => 'rhetoric', 'name' => 'Rhetoric', 'name_uz' => 'Ritorika', 'icon' => '🗣️', 'order_number' => 43],
            ['slug' => 'professional_mastery', 'name' => 'Professional Mastery', 'name_uz' => 'Professional mahorat', 'icon' => '🏆', 'order_number' => 44],
            ['slug' => 'cultural_nuances', 'name' => 'Cultural Nuances', 'name_uz' => 'Madaniy nozikliklar', 'icon' => '🌸', 'order_number' => 45],
            ['slug' => 'humor_irony', 'name' => 'Humor & Irony', 'name_uz' => 'Hazil va kinoya', 'icon' => '😄', 'order_number' => 46],
            ['slug' => 'native_fluency', 'name' => 'Native-like Fluency', 'name_uz' => 'Ona tilidek ravonlik', 'icon' => '⭐', 'order_number' => 47],
            ['slug' => 'mastery_test', 'name' => 'Mastery Test', 'name_uz' => 'Mahorat testi', 'icon' => '🎯', 'order_number' => 48],
        ];

        foreach ($topics as $topicData) {
            EnglishTopic::updateOrCreate(
                ['slug' => $topicData['slug']],
                $topicData
            );
        }

        $this->command->info('✅ Created 48 topics');
    }
}
