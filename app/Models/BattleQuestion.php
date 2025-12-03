<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BattleQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'battle_id',
        'question_id',
        'sort_order',
        'time_limit',
    ];

    // Relationships
    public function battle()
    {
        return $this->belongsTo(Battle::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answers()
    {
        return $this->hasMany(BattleAnswer::class);
    }
}
