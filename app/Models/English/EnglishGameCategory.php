<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishGameCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_game_categories';

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'icon',
        'color',
        'image',
        'is_premium',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(EnglishGame::class, 'category_id')->orderBy('order_number');
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
