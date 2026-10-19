<?php

namespace App\Notifications\Channels;

use App\Models\Notification as AppNotification;
use Illuminate\Notifications\Notification;

/**
 * Stores a Laravel notification in the app's own notifications table
 * (user_id, type, title, body, action_url, icon, priority, data), which the
 * bell, the notification pages and the unread counters read. Laravel's
 * built-in "database" channel expects notifiable_type/notifiable_id columns
 * this table does not have.
 *
 * The notification provides toApp($notifiable): array with type, title and
 * optionally body, action_url, icon, priority, data.
 */
class AppDatabaseChannel
{
    public function send(object $notifiable, Notification $notification): ?AppNotification
    {
        if (! method_exists($notification, 'toApp') || ! isset($notifiable->id)) {
            return null;
        }

        $payload = $notification->toApp($notifiable);

        return AppNotification::create([
            'user_id' => $notifiable->id,
            'type' => $payload['type'] ?? 'system',
            'title' => mb_substr((string) ($payload['title'] ?? 'Bildirishnoma'), 0, 255),
            'body' => $payload['body'] ?? null,
            'action_url' => $payload['action_url'] ?? null,
            'icon' => $payload['icon'] ?? null,
            'priority' => $payload['priority'] ?? 'normal',
            'data' => $payload['data'] ?? null,
        ]);
    }
}
