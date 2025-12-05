<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlympiadSeries extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'olympiad_type_id',
        'year',
        'season',
        'included_stages',
        'total_prize_pool',
        'total_participants',
        'banner_image',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'included_stages' => 'array',
            'total_prize_pool' => 'decimal:2',
            'total_participants' => 'integer',
            'year' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiadType(): BelongsTo
    {
        return $this->belongsTo(OlympiadType::class);
    }

    public function olympiads(): HasMany
    {
        return $this->hasMany(Olympiad::class, 'series_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    // ==================== ACCESSORS ====================

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getFullTitleAttribute(): string
    {
        $title = $this->name;
        if ($this->season) {
            $title .= ' - ' . ucfirst($this->season);
        }
        $title .= ' ' . $this->year;
        return $title;
    }

    // ==================== METHODS ====================

    /**
     * Update participant count from all olympiads in series
     */
    public function updateParticipantCount(): void
    {
        $this->total_participants = $this->olympiads()
            ->withCount('registrations')
            ->get()
            ->sum('registrations_count');
        $this->save();
    }

    /**
     * Get olympiad for a specific stage
     */
    public function getOlympiadForStage(string $stageId): ?Olympiad
    {
        return $this->olympiads()->where('stage_id', $stageId)->first();
    }
}
