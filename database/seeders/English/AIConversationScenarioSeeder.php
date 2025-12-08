<?php

namespace Database\Seeders\English;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishLevel;
use App\Models\English\EnglishAIScenario;
use Illuminate\Support\Str;

class AIConversationScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $levels = EnglishLevel::all()->keyBy('code');

        if ($levels->isEmpty()) {
            $this->command->error('Levels not found! Run LevelTopicSeeder first.');
            return;
        }

        $scenarios = [
            // A1 Scenarios
            [
                'level' => 'A1',
                'slug' => 'cafe-order',
                'code' => 'cafe_order',
                'title' => 'Ordering at a Cafe',
                'title_uz' => 'Kafeda buyurtma berish',
                'description' => 'Practice ordering food and drinks at a cafe.',
                'description_uz' => 'Kafeda ovqat va ichimlik buyurtma berishni mashq qiling.',
                'category' => 'restaurant',
                'scenario_config' => ['ai_role' => 'cafe waiter', 'user_role' => 'customer', 'objectives' => ['Greet the waiter', 'Order a drink', 'Order food', 'Ask for the bill']],
                'xp_reward' => 30,
                'coin_reward' => 15,
                'icon' => '☕',
                'order_number' => 1,
            ],
            [
                'level' => 'A1',
                'slug' => 'introduce-yourself',
                'code' => 'introduce_yourself',
                'title' => 'Meeting New People',
                'title_uz' => 'Yangi odamlar bilan tanishish',
                'description' => 'Practice introducing yourself to new people.',
                'description_uz' => 'Yangi odamlar bilan tanishuv mashq qiling.',
                'category' => 'social',
                'scenario_config' => ['ai_role' => 'new classmate', 'user_role' => 'student', 'objectives' => ['Say hello', 'Tell your name', 'Ask their name', 'Talk about where you are from']],
                'xp_reward' => 25,
                'coin_reward' => 10,
                'icon' => '👋',
                'order_number' => 2,
            ],
            [
                'level' => 'A1',
                'slug' => 'shopping-clothes',
                'code' => 'shopping_clothes',
                'title' => 'Buying Clothes',
                'title_uz' => 'Kiyim sotib olish',
                'description' => 'Practice shopping for clothes at a store.',
                'description_uz' => 'Do\'konda kiyim sotib olishni mashq qiling.',
                'category' => 'shopping',
                'scenario_config' => ['ai_role' => 'shop assistant', 'user_role' => 'customer', 'objectives' => ['Ask for help', 'Describe what you want', 'Ask about sizes', 'Ask about price']],
                'xp_reward' => 35,
                'coin_reward' => 15,
                'icon' => '👕',
                'order_number' => 3,
            ],
            // A2 Scenarios
            [
                'level' => 'A2',
                'slug' => 'hotel-booking',
                'code' => 'hotel_booking',
                'title' => 'Hotel Reservation',
                'title_uz' => 'Mehmonxona bron qilish',
                'description' => 'Practice booking a hotel room.',
                'description_uz' => 'Mehmonxonadan xona bron qilishni mashq qiling.',
                'category' => 'hotel',
                'scenario_config' => ['ai_role' => 'hotel receptionist', 'user_role' => 'guest', 'objectives' => ['Ask about room availability', 'Specify dates', 'Ask about amenities', 'Confirm booking']],
                'xp_reward' => 40,
                'coin_reward' => 20,
                'icon' => '🏨',
                'order_number' => 4,
            ],
            [
                'level' => 'A2',
                'slug' => 'doctor-visit',
                'code' => 'doctor_visit',
                'title' => 'At the Doctor\'s',
                'title_uz' => 'Shifokorga borish',
                'description' => 'Practice describing symptoms to a doctor.',
                'description_uz' => 'Shifokorga simptomlarni tasvirlashni mashq qiling.',
                'category' => 'hospital',
                'scenario_config' => ['ai_role' => 'doctor', 'user_role' => 'patient', 'objectives' => ['Describe symptoms', 'Answer health questions', 'Understand diagnosis', 'Ask about treatment']],
                'xp_reward' => 45,
                'coin_reward' => 20,
                'icon' => '🏥',
                'order_number' => 5,
            ],
            // B1 Scenarios
            [
                'level' => 'B1',
                'slug' => 'job-interview',
                'code' => 'job_interview',
                'title' => 'Job Interview',
                'title_uz' => 'Ish suhbati',
                'description' => 'Practice a job interview scenario.',
                'description_uz' => 'Ish suhbati stsenariyasini mashq qiling.',
                'category' => 'job_interview',
                'scenario_config' => ['ai_role' => 'HR manager', 'user_role' => 'job applicant', 'objectives' => ['Introduce yourself professionally', 'Talk about experience', 'Explain why you want the job', 'Ask questions about the role']],
                'xp_reward' => 55,
                'coin_reward' => 25,
                'icon' => '💼',
                'order_number' => 6,
            ],
            [
                'level' => 'B1',
                'slug' => 'travel-planning',
                'code' => 'travel_planning',
                'title' => 'Planning a Trip',
                'title_uz' => 'Sayohat rejalashtirish',
                'description' => 'Practice planning a trip at a travel agency.',
                'description_uz' => 'Sayohat agentligida sayohat rejalashtirishni mashq qiling.',
                'category' => 'travel',
                'scenario_config' => ['ai_role' => 'travel agent', 'user_role' => 'traveler', 'objectives' => ['Describe ideal destination', 'Discuss budget', 'Ask about packages', 'Make decisions']],
                'xp_reward' => 50,
                'coin_reward' => 25,
                'icon' => '✈️',
                'order_number' => 7,
            ],
        ];

        foreach ($scenarios as $s) {
            $levelCode = $s['level'];
            unset($s['level']);

            EnglishAIScenario::firstOrCreate(
                ['code' => $s['code']],
                [
                    'id' => Str::uuid(),
                    'level_id' => $levels[$levelCode]->id,
                    'slug' => $s['slug'],
                    'title' => $s['title'],
                    'title_uz' => $s['title_uz'],
                    'description' => $s['description'],
                    'description_uz' => $s['description_uz'],
                    'category' => $s['category'],
                    'scenario_config' => json_encode($s['scenario_config']),
                    'xp_reward' => $s['xp_reward'],
                    'coin_reward' => $s['coin_reward'],
                    'icon' => $s['icon'],
                    'order_number' => $s['order_number'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Created ' . count($scenarios) . ' AI conversation scenarios');
    }
}
