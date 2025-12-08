<?php

namespace Database\Seeders;

use App\Models\English\EnglishTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            ['slug' => 'personal', 'name' => 'Personal Information', 'name_uz' => 'Shaxsiy Ma\'lumot', 'icon' => 'user', 'color' => '#3B82F6'],
            ['slug' => 'family', 'name' => 'Family & Relationships', 'name_uz' => 'Oila va Munosabatlar', 'icon' => 'users', 'color' => '#EC4899'],
            ['slug' => 'home', 'name' => 'Home & Living', 'name_uz' => 'Uy va Yashash', 'icon' => 'home', 'color' => '#F59E0B'],
            ['slug' => 'work', 'name' => 'Work & Career', 'name_uz' => 'Ish va Karyera', 'icon' => 'briefcase', 'color' => '#6366F1'],
            ['slug' => 'education', 'name' => 'Education', 'name_uz' => 'Ta\'lim', 'icon' => 'academic-cap', 'color' => '#8B5CF6'],
            ['slug' => 'travel', 'name' => 'Travel & Transport', 'name_uz' => 'Sayohat va Transport', 'icon' => 'globe', 'color' => '#14B8A6'],
            ['slug' => 'food', 'name' => 'Food & Drink', 'name_uz' => 'Ovqat va Ichimlik', 'icon' => 'cake', 'color' => '#F97316'],
            ['slug' => 'shopping', 'name' => 'Shopping', 'name_uz' => 'Xarid Qilish', 'icon' => 'shopping-bag', 'color' => '#EF4444'],
            ['slug' => 'health', 'name' => 'Health & Body', 'name_uz' => 'Salomatlik va Tana', 'icon' => 'heart', 'color' => '#EF4444'],
            ['slug' => 'sports', 'name' => 'Sports & Fitness', 'name_uz' => 'Sport va Fitnes', 'icon' => 'fire', 'color' => '#22C55E'],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'name_uz' => 'Ko\'ngilochar', 'icon' => 'film', 'color' => '#A855F7'],
            ['slug' => 'technology', 'name' => 'Technology', 'name_uz' => 'Texnologiya', 'icon' => 'device-mobile', 'color' => '#06B6D4'],
            ['slug' => 'nature', 'name' => 'Nature & Environment', 'name_uz' => 'Tabiat va Atrof-muhit', 'icon' => 'sparkles', 'color' => '#22C55E'],
            ['slug' => 'weather', 'name' => 'Weather & Seasons', 'name_uz' => 'Ob-havo va Fasllar', 'icon' => 'sun', 'color' => '#FBBF24'],
            ['slug' => 'time', 'name' => 'Time & Calendar', 'name_uz' => 'Vaqt va Taqvim', 'icon' => 'clock', 'color' => '#64748B'],
            ['slug' => 'numbers', 'name' => 'Numbers & Math', 'name_uz' => 'Raqamlar va Matematika', 'icon' => 'calculator', 'color' => '#0EA5E9'],
            ['slug' => 'colors', 'name' => 'Colors & Shapes', 'name_uz' => 'Ranglar va Shakllar', 'icon' => 'color-swatch', 'color' => '#EC4899'],
            ['slug' => 'emotions', 'name' => 'Emotions & Feelings', 'name_uz' => 'His-tuyg\'ular', 'icon' => 'emoji-happy', 'color' => '#F43F5E'],
            ['slug' => 'culture', 'name' => 'Culture & Traditions', 'name_uz' => 'Madaniyat va An\'analar', 'icon' => 'library', 'color' => '#7C3AED'],
            ['slug' => 'news', 'name' => 'News & Media', 'name_uz' => 'Yangiliklar va Media', 'icon' => 'newspaper', 'color' => '#475569'],
            ['slug' => 'business', 'name' => 'Business', 'name_uz' => 'Biznes', 'icon' => 'chart-bar', 'color' => '#0D9488'],
            ['slug' => 'science', 'name' => 'Science', 'name_uz' => 'Fan', 'icon' => 'beaker', 'color' => '#6366F1'],
            ['slug' => 'art', 'name' => 'Art & Music', 'name_uz' => 'San\'at va Musiqa', 'icon' => 'music-note', 'color' => '#EC4899'],
            ['slug' => 'daily-routine', 'name' => 'Daily Routine', 'name_uz' => 'Kundalik Tartib', 'icon' => 'refresh', 'color' => '#8B5CF6'],
        ];

        foreach ($topics as $index => $topic) {
            EnglishTopic::create(array_merge($topic, [
                'id' => Str::uuid(),
                'order_number' => $index + 1,
                'is_active' => true,
            ]));
        }
    }
}
