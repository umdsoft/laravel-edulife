<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishAchievementCategory extends Model
{
    use HasUuids;

    protected $table = 'english_achievement_categories';

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'icon',
        'color',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function achievements(): HasMany
    {
        return $this->hasMany(EnglishAchievement::class, 'category_id')->orderBy('order_number');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
