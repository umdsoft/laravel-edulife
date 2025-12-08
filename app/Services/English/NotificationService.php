<?php

namespace App\Services\English;

use App\Models\English\UserNotification;
use App\Models\English\EnglishAchievement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Send general notification
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendPush = false
    ): UserNotification {
        $notification = UserNotification::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'is_push_sent' => false,
        ]);

        if ($sendPush) {
            $this->sendPushNotification($notification);
        }

        return $notification;
    }

    /**
     * Send achievement notification
     */
    public function sendAchievementNotification(User $user, EnglishAchievement $achievement): UserNotification
    {
        return $this->send(
            $user,
            'achievement',
            '🏆 ' . $achievement->name,
            $achievement->description,
            [
                'achievement_id' => $achievement->id,
                'tier' => $achievement->tier,
                'xp_reward' => $achievement->xp_reward,
                'coin_reward' => $achievement->coin_reward,
                'icon' => $achievement->icon,
            ],
            true
        );
    }

    /**
     * Send streak notification
     */
    public function sendStreakNotification(User $user, int $streakDays): UserNotification
    {
        $emoji = match (true) {
            $streakDays >= 365 => '🏆',
            $streakDays >= 100 => '💎',
            $streakDays >= 30 => '🔥',
            $streakDays >= 7 => '⭐',
            default => '✨',
        };

        return $this->send(
            $user,
            'streak',
            "{$emoji} {$streakDays}-kun streak!",
            "Ajoyib! Siz {$streakDays} kun ketma-ket o'qiyapsiz!",
            ['streak_days' => $streakDays],
            true
        );
    }

    /**
     * Send level up notification
     */
    public function sendLevelUpNotification(User $user, string $newLevel): UserNotification
    {
        return $this->send(
            $user,
            'level_up',
            '📈 Yangi daraja!',
            "Tabriklaymiz! Siz {$newLevel} darajaga ko'tarildingiz!",
            ['new_level' => $newLevel],
            true
        );
    }

    /**
     * Send battle result notification
     */
    public function sendBattleNotification(User $user, bool $won, string $opponentName, int $eloChange): UserNotification
    {
        $emoji = $won ? '🎉' : '💪';
        $title = $won ? "{$emoji} Jang g'alabasi!" : "{$emoji} Jang tugadi";
        $message = $won
            ? "Siz {$opponentName} ni yengdingiz! ELO: +{$eloChange}"
            : "{$opponentName} g'alaba qozondi. ELO: {$eloChange}";

        return $this->send(
            $user,
            'battle',
            $title,
            $message,
            [
                'won' => $won,
                'opponent' => $opponentName,
                'elo_change' => $eloChange,
            ],
            true
        );
    }

    /**
     * Send daily reminder notification
     */
    public function sendDailyReminder(User $user): UserNotification
    {
        $messages = [
            "🔔 Bugun hali o'qimadingiz! Streakni yo'qotmang!",
            "📚 Kunlik mashg'ulotingiz kutmoqda!",
            "⏰ Ingliz tilini o'rganish vaqti keldi!",
            "🎯 Kunlik maqsadingizga erishing!",
        ];

        return $this->send(
            $user,
            'reminder',
            '⏰ Kunlik eslatma',
            $messages[array_rand($messages)],
            [],
            true
        );
    }

    /**
     * Send reward notification
     */
    public function sendRewardNotification(User $user, string $rewardType, int $amount): UserNotification
    {
        $emoji = match ($rewardType) {
            'xp' => '⭐',
            'coins' => '🪙',
            'gems' => '💎',
            default => '🎁',
        };

        return $this->send(
            $user,
            'reward',
            "{$emoji} Mukofot!",
            "Siz {$amount} {$rewardType} oldingiz!",
            ['reward_type' => $rewardType, 'amount' => $amount],
            false
        );
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, bool $unreadOnly = false, int $limit = 50): Collection
    {
        $query = UserNotification::where('user_id', $user->id)->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(UserNotification $notification): void
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): int
    {
        return UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(User $user): int
    {
        return UserNotification::where('user_id', $user->id)->where('is_read', false)->count();
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return UserNotification::where('created_at', '<', now()->subDays($daysOld))
            ->where('is_read', true)
            ->delete();
    }

    /**
     * Send push notification (placeholder for FCM/OneSignal integration)
     */
    private function sendPushNotification(UserNotification $notification): void
    {
        $notification->is_push_sent = true;
        $notification->push_sent_at = now();
        $notification->save();
    }
}
