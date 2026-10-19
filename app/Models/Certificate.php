<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'course_id', 'enrollment_id',
        'certificate_number', 'credential_id', 'certificate_code',
        'recipient_name', 'course_title', 'teacher_name',
        'final_score', 'completion_time', 'completion_date', 'template_id',
        'pdf_path', 'image_path', 'qr_code_path',
        'verification_code', 'verification_url',
        'is_valid', 'revoked_at', 'revoke_reason',
        'is_public', 'views_count', 'downloads_count',
        'issued_at', 'verified_at',
    ];
    
    protected $casts = [
        'final_score' => 'decimal:2',
        'completion_hours' => 'integer',
        'completion_date' => 'date',
        'is_valid' => 'boolean',
        'revoked_at' => 'datetime',
        'is_public' => 'boolean',
        'views_count' => 'integer',
        'downloads_count' => 'integer',
        'issued_at' => 'datetime',
    ];
    
    protected $appends = ['pdf_url', 'image_url', 'public_url'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
    
    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        return "EDULIFE-{$year}-{$random}";
    }
    
    /**
     * Generate verification code
     */
    public static function generateVerificationCode(): string
    {
        // Cryptographically random: md5(uniqid()) was guessable from the timestamp
        return strtoupper(Str::random(12));
    }
    
    /**
     * PDF URL
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) return null;
        return Storage::disk('public')->url($this->pdf_path);
    }
    
    /**
     * Image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        return Storage::disk('public')->url($this->image_path);
    }
    
    /**
     * Public verification URL
     */
    public function getPublicUrlAttribute(): string
    {
        // older rows have only certificate_code (the verify page accepts both)
        return route('certificate.verify', $this->verification_code ?: $this->certificate_code);
    }
    
    /**
     * Increment view count
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }
    
    /**
     * Increment download count
     */
    public function recordDownload(): void
    {
        $this->increment('downloads_count');
    }
    
    /**
     * Revoke certificate
     */
    public function revoke(string $reason): void
    {
        $this->update([
            'is_valid' => false,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }
}
