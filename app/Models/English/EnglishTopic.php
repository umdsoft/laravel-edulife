<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnglishTopic extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_topics';

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'parent_id',
        'icon',
        'color',
        'image',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(EnglishTopic::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EnglishTopic::class, 'parent_id')->orderBy('order_number');
    }

    public function vocabulary(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishVocabulary::class,
            'english_vocabulary_topics',
            'topic_id',
            'vocabulary_id'
        )->withPivot('is_primary');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParentTopics($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
