<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityFeed;
use Illuminate\Database\Eloquent\Model;

class ActivityFeedService
{
    /**
     * Log an activity
     */
    public function log(User $user, string $type, array $data = []): ActivityFeed
    {
        return ActivityFeed::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $data['title'] ?? $this->getDefaultTitle($type),
            'description' => $data['description'] ?? null,
            'activityable_type' => isset($data['activityable']) ? get_class($data['activityable']) : null,
            'activityable_id' => isset($data['activityable']) ? $data['activityable']->id : null,
            'is_public' => $data['is_public'] ?? true,
            'occurred_at' => now(),
        ]);
    }
    
    private function getDefaultTitle(string $type): string
    {
        return match($type) {
            'course_enrolled' => 'Yangi kursga yozildi',
            'course_completed' => 'Kursni tugatdi',
            'lesson_completed' => 'Darsni tugatdi',
            'test_passed' => 'Testdan o\'tdi',
            'achievement_unlocked' => 'Yangi yutuq',
            'battle_won' => 'Jangda g\'olib bo\'ldi',
            'level_up' => 'Yangi darajaga chiqdi',
            'streak_milestone' => 'Streak rekordi',
            'certificate_earned' => 'Sertifikat oldi',
            default => 'Faoliyat',
        };
    }
}
