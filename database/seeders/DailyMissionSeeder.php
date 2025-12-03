<?php

namespace Database\Seeders;

use App\Models\DailyMission;
use Illuminate\Database\Seeder;

class DailyMissionSeeder extends Seeder
{
    public function run(): void
    {
        $missions = [
            ['title' => '1 ta dars tugating', 'description' => 'Bugun kamida 1 ta darsni yakunlang', 'type' => 'complete_lessons', 'target_count' => 1, 'xp_reward' => 30, 'coin_reward' => 5, 'is_active' => true, 'sort_order' => 1],
            ['title' => '3 ta dars tugating', 'description' => 'Bugun 3 ta darsni yakunlang', 'type' => 'complete_lessons', 'target_count' => 3, 'xp_reward' => 100, 'coin_reward' => 20, 'is_active' => true, 'sort_order' => 2],
            ['title' => '5 ta dars tugating', 'description' => 'Bugun 5 ta darsni yakunlang (qiyin)', 'type' => 'complete_lessons', 'target_count' => 5, 'xp_reward' => 200, 'coin_reward' => 40, 'is_active' => true, 'sort_order' => 3],
            ['title' => '1 ta testdan o\'ting', 'description' => 'Bugun kamida 1 ta testdan muvaffaqiyatli o\'ting', 'type' => 'pass_tests', 'target_count' => 1, 'xp_reward' => 50, 'coin_reward' => 10, 'is_active' => true, 'sort_order' => 4],
            ['title' => '3 ta testdan o\'ting', 'description' => 'Bugun 3 ta testdan o\'ting (qiyin)', 'type' => 'pass_tests', 'target_count' => 3, 'xp_reward' => 150, 'coin_reward' => 30, 'is_active' => true, 'sort_order' => 5],
            ['title' => '1 ta battle yutib oling', 'description' => 'Bugun kamida 1 ta battle da g\'alaba qozonish', 'type' => 'win_battles', 'target_count' => 1, 'xp_reward' => 100, 'coin_reward' => 25, 'is_active' => true, 'sort_order' => 6],
            ['title' => '3 ta battle yutib oling', 'description' => 'Bugun 3 ta battle da g\'alaba qozonish (qiyin)', 'type' => 'win_battles', 'target_count' => 3, 'xp_reward' => 250, 'coin_reward' => 50, 'is_active' => true, 'sort_order' => 7],
            ['title' => '5 ta battle o\'ynang', 'description' => 'Bugun 5 ta battle o\'ynang (natija muhim emas)', 'type' => 'play_battles', 'target_count' => 5, 'xp_reward' => 80, 'coin_reward' => 15, 'is_active' => true, 'sort_order' => 8],
            ['title' => '30 daqiqa video tomosha qiling', 'description' => 'Bugun kamida 30 daqiqa video dars tomosha qiling', 'type' => 'watch_time', 'target_count' => 1800, 'xp_reward' => 50, 'coin_reward' => 10, 'is_active' => true, 'sort_order' => 9],
            ['title' => 'Platformaga kiring', 'description' => 'Har kuni platformaga kirib, streak ni davom ettiring', 'type' => 'daily_login', 'target_count' => 1, 'xp_reward' => 20, 'coin_reward' => 5, 'is_active' => true, 'sort_order' => 10],
        ];

        foreach ($missions as $mission) {
            DailyMission::create($mission);
        }
    }
}
