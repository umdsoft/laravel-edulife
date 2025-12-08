<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishGrammarCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_grammar_categories';

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'parent_id',
        'icon',
        'color',
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
        return $this->belongsTo(EnglishGrammarCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EnglishGrammarCategory::class, 'parent_id')->orderBy('order_number');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EnglishGrammarRule::class, 'category_id')->orderBy('order_number');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
