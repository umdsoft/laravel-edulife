<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OlympiadCertificate extends Model
{
    use HasFactory, HasUuids;

    // Certificate type constants
    public const TYPE_PARTICIPATION = 'participation';
    public const TYPE_WINNER_1 = 'winner_1';
    public const TYPE_WINNER_2 = 'winner_2';
    public const TYPE_WINNER_3 = 'winner_3';
    public const TYPE_TOP_10 = 'top_10';
    public const TYPE_ADVANCEMENT = 'advancement';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'attempt_id',
        'template_id',
        'certificate_type',
        'certificate_number',
        'rank',
        'score',
        'pdf_path',
        'qr_code',
        'verification_code',
        'certificate_data',
        'is_verified',
        'issued_at',
        'downloads_count',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'score' => 'decimal:2',
            'certificate_data' => 'array',
            'is_verified' => 'boolean',
            'issued_at' => 'datetime',
            'downloads_count' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = self::generateCertificateNumber();
            }
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = self::generateVerificationCode();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    // ==================== SCOPES ====================

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('certificate_type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getCertificateTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_PARTICIPATION => 'Ishtirok sertifikati',
            self::TYPE_WINNER_1 => '1-o\'rin sertifikati',
            self::TYPE_WINNER_2 => '2-o\'rin sertifikati',
            self::TYPE_WINNER_3 => '3-o\'rin sertifikati',
            self::TYPE_TOP_10 => 'Top-10 sertifikati',
            self::TYPE_ADVANCEMENT => 'Rivojlanish sertifikati',
        ];
        return $labels[$this->certificate_type] ?? $this->certificate_type;
    }

    public function getIsWinnerCertificateAttribute(): bool
    {
        return in_array($this->certificate_type, [
            self::TYPE_WINNER_1,
            self::TYPE_WINNER_2,
            self::TYPE_WINNER_3,
        ]);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        return $this->pdf_path ? asset('storage/' . $this->pdf_path) : null;
    }

    public function getVerificationUrlAttribute(): string
    {
        return route('certificates.verify', $this->verification_code);
    }

    // ==================== METHODS ====================

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(): string
    {
        $prefix = 'OLY';
        $year = date('Y');
        $random = strtoupper(Str::random(8));
        $number = "{$prefix}-{$year}-{$random}";
        
        while (self::where('certificate_number', $number)->exists()) {
            $random = strtoupper(Str::random(8));
            $number = "{$prefix}-{$year}-{$random}";
        }
        
        return $number;
    }

    /**
     * Generate verification code
     */
    public static function generateVerificationCode(): string
    {
        $code = strtoupper(Str::random(12));
        
        while (self::where('verification_code', $code)->exists()) {
            $code = strtoupper(Str::random(12));
        }
        
        return $code;
    }

    /**
     * Determine certificate type from rank
     */
    public static function getCertificateTypeFromRank(?int $rank): string
    {
        if ($rank === null) {
            return self::TYPE_PARTICIPATION;
        }
        
        return match ($rank) {
            1 => self::TYPE_WINNER_1,
            2 => self::TYPE_WINNER_2,
            3 => self::TYPE_WINNER_3,
            4, 5, 6, 7, 8, 9, 10 => self::TYPE_TOP_10,
            default => self::TYPE_PARTICIPATION,
        };
    }

    /**
     * Increment download count
     */
    public function recordDownload(): void
    {
        $this->increment('downloads_count');
    }

    /**
     * Prepare certificate data for PDF generation
     */
    public function prepareCertificateData(): array
    {
        $user = $this->user;
        $olympiad = $this->olympiad;

        return [
            'certificate_number' => $this->certificate_number,
            'verification_code' => $this->verification_code,
            'user_name' => $user->name,
            'olympiad_title' => $olympiad->title,
            'olympiad_type' => $olympiad->olympiadType->display_name,
            'stage_name' => $olympiad->stage?->display_name,
            'rank' => $this->rank,
            'score' => $this->score,
            'score_percent' => $this->attempt?->score_percent,
            'certificate_type' => $this->certificate_type_label,
            'issued_at' => $this->issued_at->format('d.m.Y'),
            'qr_code_url' => $this->verification_url,
        ];
    }
}
