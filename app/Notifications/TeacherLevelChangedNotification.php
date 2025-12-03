<?php

namespace App\Notifications;

use App\Models\TeacherLevelChange;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeacherLevelChangedNotification extends Notification
{
    use Queueable;
    
    public function __construct(
        public TeacherLevelChange $change
    ) {}
    
    public function via($notifiable): array
    {
        return ['database'];
    }
    
    public function toArray($notifiable): array
    {
        $isUpgrade = $this->change->isUpgrade();
        $isAutomatic = $this->change->isAutomatic();
        
        $levelNames = [
            'new' => 'Yangi',
            'verified' => 'Tasdiqlangan',
            'featured' => 'Tavsiya etilgan',
            'top' => 'Top',
        ];
        
        return [
            'type' => 'level_changed',
            'old_level' => $this->change->old_level,
            'new_level' => $this->change->new_level,
            'old_level_name' => $levelNames[$this->change->old_level] ?? $this->change->old_level,
            'new_level_name' => $levelNames[$this->change->new_level] ?? $this->change->new_level,
            'score' => $this->change->new_score,
            'is_upgrade' => $isUpgrade,
            'is_automatic' => $isAutomatic,
            'reason' => $this->change->reason,
            'message' => $isUpgrade 
                ? 'Tabriklaymiz! Sizning darajangiz ' . ($levelNames[$this->change->new_level] ?? $this->change->new_level) . ' ga ko\'tarildi!'
                : 'Sizning darajangiz ' . ($levelNames[$this->change->new_level] ?? $this->change->new_level) . ' ga o\'zgardi.',
        ];
    }
}
