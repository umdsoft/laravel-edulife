<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Jobs\GenerateCertificatePdf;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    public function __construct(
        protected ActivityFeedService $activityFeed
    ) {}
    
    /**
     * Generate certificate for completed course
     */
    public function generate(Enrollment $enrollment): Certificate
    {
        $user = $enrollment->user;
        $course = $enrollment->course;
        
        // Calculate completion hours
        $hours = (int) floor(($enrollment->total_watch_time ?? 0) / 3600);
        
        // Get final test score
        $finalScore = $enrollment->final_score ?? 0;
        
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'recipient_name' => $user->first_name . ' ' . $user->last_name,
            'course_title' => $course->title,
            'teacher_name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
            'final_score' => $finalScore,
            'completion_hours' => $hours,
            'completion_date' => now()->toDateString(),
            'verification_code' => Certificate::generateVerificationCode(),
            'issued_at' => now(),
        ]);
        
        // Generate verification URL
        $certificate->update([
            'verification_url' => route('certificates.verify', $certificate->verification_code),
        ]);
        
        // Dispatch PDF generation job
        GenerateCertificatePdf::dispatch($certificate);
        
        // Update enrollment
        $enrollment->update([
            'certificate_issued' => true,
            'certificate_id' => $certificate->id,
        ]);
        
        // Update student profile
        if ($user->studentProfile) {
            $user->studentProfile->increment('certificates_earned');
        }
        
        // Create activity
        $this->activityFeed->log($user, 'certificate_earned', [
            'title' => "Sertifikat olindi: {$course->title}",
            'activityable' => $certificate,
        ]);
        
        return $certificate;
    }
    
    /**
     * Generate PDF (called by job)
     */
    public function generatePdf(Certificate $certificate): void
    {
        // For now, just mark as generated
        // Real implementation would use DomPDF or similar
        $path = "certificates/{$certificate->id}.pdf";
        
        // Generate placeholder PDF content
        $content = "Certificate: {$certificate->certificate_number}\n";
        $content .= "Recipient: {$certificate->recipient_name}\n";
        $content .= "Course: {$certificate->course_title}\n";
        $content .= "Issued: {$certificate->issued_at->format('Y-m-d')}\n";
        
        Storage::disk('public')->put($path, $content);
        
        $certificate->update(['pdf_path' => $path]);
        
        // Also generate image preview
        $this->generateImage($certificate);
    }
    
    /**
     * Generate image preview
     */
    public function generateImage(Certificate $certificate): void
    {
        $path = "certificates/{$certificate->id}.png";
        
        // Placeholder - real implementation would use image library
        $content = "Certificate Preview: {$certificate->certificate_number}";
        Storage::disk('public')->put($path, $content);
        
        $certificate->update(['image_path' => $path]);
    }
}
