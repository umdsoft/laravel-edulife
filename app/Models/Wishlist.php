<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'course_id',
        'price_when_added',
    ];

    protected function casts(): array
    {
        return [
            'price_when_added' => 'decimal:2',
        ];
    }

    // Accessors
    public function getDiscountAvailableAttribute(): bool
    {
        return $this->course->price < $this->price_when_added;
    }

    public function getDiscountAmountAttribute(): float
    {
        if (!$this->discount_available) return 0;
        return $this->price_when_added - $this->course->price;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
