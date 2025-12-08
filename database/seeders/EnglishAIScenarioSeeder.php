<?php

namespace Database\Seeders;

use App\Models\English\EnglishAIScenario;
use App\Models\English\EnglishAIScenarioGoal;
use App\Models\English\EnglishLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnglishAIScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $a1Level = EnglishLevel::where('code', 'A1')->first();
        $a2Level = EnglishLevel::where('code', 'A2')->first();

        $scenarios = [
            [
                'level_id' => $a1Level?->id,
                'slug' => 'restaurant-ordering-basic',
                'code' => 'restaurant_order_basic',
                'title' => 'At the Restaurant',
                'title_uz' => 'Restoranda',
                'category' => 'restaurant',
                'scenario_config' => [
                    'setting' => 'A casual family restaurant',
                    'user_role' => 'Customer',
                    'ai_role' => 'Waiter',
                    'opening_message' => 'Hello! Welcome to our restaurant. How can I help you today?',
                ],
                'xp_reward' => 50,
                'goals' => [
                    ['goal_key' => 'greet', 'title' => 'Greet the waiter', 'title_uz' => 'Ofitsiantni kutib oling', 'points' => 10],
                    ['goal_key' => 'order_food', 'title' => 'Order your food', 'title_uz' => 'Ovqat buyurtma qiling', 'points' => 20],
                    ['goal_key' => 'order_drink', 'title' => 'Order a drink', 'title_uz' => 'Ichimlik buyurtma qiling', 'points' => 10],
                    ['goal_key' => 'ask_bill', 'title' => 'Ask for the bill', 'title_uz' => 'Hisob so\'rang', 'points' => 10],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'hotel-checkin-basic',
                'code' => 'hotel_checkin_basic',
                'title' => 'Hotel Check-in',
                'title_uz' => 'Mehmonxonaga Kirish',
                'category' => 'hotel',
                'scenario_config' => [
                    'setting' => 'A hotel reception',
                    'user_role' => 'Guest',
                    'ai_role' => 'Receptionist',
                    'opening_message' => 'Good evening! Welcome to Grand Hotel. Do you have a reservation?',
                ],
                'xp_reward' => 50,
                'goals' => [
                    ['goal_key' => 'confirm_reservation', 'title' => 'Confirm reservation', 'title_uz' => 'Bron tasdiqlang', 'points' => 15],
                    ['goal_key' => 'give_name', 'title' => 'Give your name', 'title_uz' => 'Ismingizni ayting', 'points' => 10],
                    ['goal_key' => 'ask_breakfast', 'title' => 'Ask about breakfast', 'title_uz' => 'Nonushta haqida so\'rang', 'points' => 15],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'shopping-clothes',
                'code' => 'shopping_clothes',
                'title' => 'Shopping for Clothes',
                'title_uz' => 'Kiyim Xaridi',
                'category' => 'shopping',
                'scenario_config' => [
                    'setting' => 'A clothing store',
                    'user_role' => 'Customer',
                    'ai_role' => 'Shop assistant',
                    'opening_message' => 'Hello! Can I help you find something?',
                ],
                'xp_reward' => 40,
                'goals' => [
                    ['goal_key' => 'say_looking', 'title' => 'Say what you\'re looking for', 'title_uz' => 'Nima qidirayotganingizni ayting', 'points' => 10],
                    ['goal_key' => 'ask_size', 'title' => 'Ask about size', 'title_uz' => 'O\'lcham so\'rang', 'points' => 15],
                    ['goal_key' => 'ask_price', 'title' => 'Ask the price', 'title_uz' => 'Narxini so\'rang', 'points' => 10],
                ],
            ],
            [
                'level_id' => $a2Level?->id,
                'slug' => 'doctor-visit',
                'code' => 'doctor_visit',
                'title' => 'At the Doctor',
                'title_uz' => 'Shifokorда',
                'category' => 'hospital',
                'scenario_config' => [
                    'setting' => 'A doctor\'s office',
                    'user_role' => 'Patient',
                    'ai_role' => 'Doctor',
                    'opening_message' => 'Hello, please sit down. What brings you here today?',
                ],
                'xp_reward' => 60,
                'goals' => [
                    ['goal_key' => 'describe_symptoms', 'title' => 'Describe symptoms', 'title_uz' => 'Alomatlarni tasvirlang', 'points' => 20],
                    ['goal_key' => 'say_duration', 'title' => 'Say how long', 'title_uz' => 'Qancha vaqtdan beri', 'points' => 15],
                    ['goal_key' => 'ask_advice', 'title' => 'Ask for advice', 'title_uz' => 'Maslahat so\'rang', 'points' => 15],
                ],
            ],
            [
                'level_id' => $a2Level?->id,
                'slug' => 'job-interview-basic',
                'code' => 'job_interview_basic',
                'title' => 'Job Interview',
                'title_uz' => 'Ish Suhbati',
                'category' => 'job_interview',
                'scenario_config' => [
                    'setting' => 'An office meeting room',
                    'user_role' => 'Job applicant',
                    'ai_role' => 'HR Manager',
                    'opening_message' => 'Hello, nice to meet you. Please have a seat. Tell me about yourself.',
                ],
                'xp_reward' => 70,
                'goals' => [
                    ['goal_key' => 'introduce_self', 'title' => 'Introduce yourself', 'title_uz' => 'O\'zingizni tanishtiring', 'points' => 15],
                    ['goal_key' => 'describe_experience', 'title' => 'Describe experience', 'title_uz' => 'Tajribangizni tasvirlang', 'points' => 20],
                    ['goal_key' => 'ask_about_job', 'title' => 'Ask about the job', 'title_uz' => 'Ish haqida so\'rang', 'points' => 15],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'asking-directions',
                'code' => 'asking_directions',
                'title' => 'Asking for Directions',
                'title_uz' => 'Yo\'l So\'rash',
                'category' => 'travel',
                'scenario_config' => [
                    'setting' => 'On the street',
                    'user_role' => 'Tourist',
                    'ai_role' => 'Local person',
                    'opening_message' => 'Hi there! You look a bit lost. Can I help you?',
                ],
                'xp_reward' => 40,
                'goals' => [
                    ['goal_key' => 'ask_location', 'title' => 'Ask for a location', 'title_uz' => 'Joy so\'rang', 'points' => 15],
                    ['goal_key' => 'confirm_directions', 'title' => 'Confirm directions', 'title_uz' => 'Yo\'lni tasdiqlang', 'points' => 15],
                    ['goal_key' => 'thank', 'title' => 'Thank the person', 'title_uz' => 'Rahmat ayting', 'points' => 10],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'cafe-ordering',
                'code' => 'cafe_ordering',
                'title' => 'Ordering at a Café',
                'title_uz' => 'Kaféda Buyurtma',
                'category' => 'restaurant',
                'scenario_config' => [
                    'setting' => 'A cozy coffee shop',
                    'user_role' => 'Customer',
                    'ai_role' => 'Barista',
                    'opening_message' => 'Hi! What can I get for you today?',
                ],
                'xp_reward' => 35,
                'goals' => [
                    ['goal_key' => 'order_drink', 'title' => 'Order a drink', 'title_uz' => 'Ichimlik buyurtma qiling', 'points' => 15],
                    ['goal_key' => 'customize_order', 'title' => 'Customize your order', 'title_uz' => 'Buyurtmani moslashtiring', 'points' => 15],
                    ['goal_key' => 'pay', 'title' => 'Pay for your order', 'title_uz' => 'To\'lang', 'points' => 10],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'airport-checkin',
                'code' => 'airport_checkin',
                'title' => 'Airport Check-in',
                'title_uz' => 'Aeroport Ro\'yxatdan O\'tish',
                'category' => 'airport',
                'scenario_config' => [
                    'setting' => 'Airport check-in counter',
                    'user_role' => 'Passenger',
                    'ai_role' => 'Check-in agent',
                    'opening_message' => 'Hello! May I see your passport and booking confirmation?',
                ],
                'xp_reward' => 50,
                'goals' => [
                    ['goal_key' => 'show_documents', 'title' => 'Show documents', 'title_uz' => 'Hujjatlarni ko\'rsating', 'points' => 10],
                    ['goal_key' => 'check_baggage', 'title' => 'Check baggage', 'title_uz' => 'Yukni topshiring', 'points' => 15],
                    ['goal_key' => 'seat_preference', 'title' => 'Request seat preference', 'title_uz' => 'O\'rindiq tanlang', 'points' => 15],
                ],
            ],
            [
                'level_id' => $a2Level?->id,
                'slug' => 'phone-call-appointment',
                'code' => 'phone_call_appointment',
                'title' => 'Making an Appointment',
                'title_uz' => 'Uchrashuv Belgilash',
                'category' => 'phone_call',
                'scenario_config' => [
                    'setting' => 'Phone call to a dentist clinic',
                    'user_role' => 'Caller',
                    'ai_role' => 'Receptionist',
                    'opening_message' => 'Hello, Bright Smile Dental Clinic. How may I help you?',
                ],
                'xp_reward' => 55,
                'goals' => [
                    ['goal_key' => 'request_appointment', 'title' => 'Request appointment', 'title_uz' => 'Uchrashuv so\'rang', 'points' => 15],
                    ['goal_key' => 'give_details', 'title' => 'Give your details', 'title_uz' => 'Ma\'lumotlaringizni bering', 'points' => 15],
                    ['goal_key' => 'confirm_time', 'title' => 'Confirm date and time', 'title_uz' => 'Sana va vaqtni tasdiqlang', 'points' => 15],
                ],
            ],
            [
                'level_id' => $a1Level?->id,
                'slug' => 'supermarket-shopping',
                'code' => 'supermarket_shopping',
                'title' => 'Supermarket Shopping',
                'title_uz' => 'Supermarketda Xarid',
                'category' => 'shopping',
                'scenario_config' => [
                    'setting' => 'A supermarket',
                    'user_role' => 'Shopper',
                    'ai_role' => 'Store clerk',
                    'opening_message' => 'Hi! Looking for something specific?',
                ],
                'xp_reward' => 40,
                'goals' => [
                    ['goal_key' => 'find_product', 'title' => 'Find a product', 'title_uz' => 'Mahsulot toping', 'points' => 15],
                    ['goal_key' => 'ask_location', 'title' => 'Ask product location', 'title_uz' => 'Mahsulot joyini so\'rang', 'points' => 15],
                    ['goal_key' => 'checkout', 'title' => 'Complete checkout', 'title_uz' => 'Xaridni yakunlang', 'points' => 10],
                ],
            ],
        ];

        foreach ($scenarios as $index => $scenarioData) {
            if (!$scenarioData['level_id'])
                continue;

            $goals = $scenarioData['goals'];
            unset($scenarioData['goals']);

            $scenario = EnglishAIScenario::create(array_merge($scenarioData, [
                'id' => Str::uuid(),
                'order_number' => $index + 1,
                'coin_reward' => 20,
                'is_active' => true,
            ]));

            foreach ($goals as $goalIndex => $goalData) {
                EnglishAIScenarioGoal::create(array_merge($goalData, [
                    'id' => Str::uuid(),
                    'scenario_id' => $scenario->id,
                    'order_number' => $goalIndex + 1,
                    'is_required' => true,
                ]));
            }
        }
    }
}
