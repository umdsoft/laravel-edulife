<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id', 'course_id', 'enrollment_id',
        'certificate_number', 'recipient_name', 'course_title', 'teacher_name',
        'final_score', 'completion_hours', 'completion_date',
        'pdf_path', 'image_path', 'qr_code_path',
        'verification_code', 'verification_url',
        'is_valid', 'revoked_at', 'revoke_reason',
        'is_public', 'views_count', 'downloads_count',
        'issued_at',
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
        return strtoupper(substr(md5(uniqid() . time()), 0, 12));
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
        return route('certificates.verify', $this->verification_code);
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
