<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'title',
        'description',
        'icon',
        'category',
        'rarity',
        'xp_reward',
        'coin_reward',
        'requirements',
        'is_hidden',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'is_hidden' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Accessors
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset('storage/' . $this->icon) : null;
    }

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('xp_rewarded', 'coin_rewarded', 'earned_at')
            ->withTimestamps();
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper Methods
    public function awardTo(User $user): void
    {
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return; // Already earned
        }

        $this->users()->attach($user->id, [
            'xp_rewarded' => $this->xp_reward,
            'coin_rewarded' => $this->coin_reward,
            'earned_at' => now(),
        ]);

        // Award XP and COINs
        $user->addXp($this->xp_reward);
        $user->addCoins($this->coin_reward, 'achievement', "Achievement: {$this->title}");
    }
}
