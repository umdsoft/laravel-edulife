<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    public function __construct(
        protected ActivityFeedService $activityFeed,
        protected CertificatePdfRenderer $renderer
    ) {}
    
    /**
     * Generate certificate for completed course
     */
    public function generate(Enrollment $enrollment): Certificate
    {
        $user = $enrollment->user;
        $course = $enrollment->course;

        // One certificate per (user, course): unique index; make re-runs idempotent
        $existing = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            return $existing;
        }
        
        // Calculate completion hours
        $hours = (int) floor(($enrollment->total_watch_time ?? 0) / 3600);
        
        // Get final test score
        $finalScore = $enrollment->final_score ?? 0;
        
        $number = Certificate::generateCertificateNumber();
        $code = Certificate::generateVerificationCode();

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => $number,
            'credential_id' => $number, // NOT NULL unique legacy column
            'certificate_code' => $code, // NOT NULL unique, used by /verify/{code}
            'recipient_name' => $user->first_name . ' ' . $user->last_name,
            'course_title' => $course->title,
            'teacher_name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
            'final_score' => $finalScore,
            'completion_time' => $hours,
            'completion_date' => now()->toDateString(),
            'verification_code' => $code,
            'issued_at' => now(),
        ]);
        
        // Generate verification URL
        $certificate->update([
            'verification_url' => route('certificate.verify', $code),
        ]);
        
        // The PDF is made right away (there is no queue worker); if that fails the
        // download builds it on demand.
        try {
            $this->generatePdf($certificate);
        } catch (\Throwable $e) {
            report($e);
        }
        
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
     * Render the certificate PDF and store it on the public disk.
     */
    public function generatePdf(Certificate $certificate): string
    {
        $certificate->loadMissing('course');
        $issued = $certificate->issued_at ?? now();

        $details = array_values(array_filter([
            (float) $certificate->final_score > 0 ? 'Yakuniy natija: ' . rtrim(rtrim(number_format((float) $certificate->final_score, 2, '.', ''), '0'), '.') . '%' : null,
            (int) $certificate->completion_time > 0 ? "O'qish vaqti: {$certificate->completion_time} soat" : null,
        ]));

        $pdf = $this->renderer->render([
            'title' => 'Sertifikat',
            'kind' => 'Kursni muvaffaqiyatli tamomlagani haqida',
            'recipient' => (string) $certificate->recipient_name,
            'lead' => "quyidagi kursni to'liq o'zlashtirib, muvaffaqiyatli tamomladi:",
            'subject' => (string) ($certificate->course_title ?: $certificate->course?->title),
            'details' => $details,
            'number' => (string) $certificate->certificate_number,
            'verificationCode' => (string) $certificate->verification_code,
            'verifyUrl' => route('certificate.verify', $certificate->verification_code),
            'issuedAt' => $issued->format('d.m.Y'),
            'signatory' => $certificate->teacher_name,
            'signatoryRole' => "Kurs o'qituvchisi",
        ]);

        $path = "certificates/certificate-{$certificate->certificate_number}.pdf";
        Storage::disk('public')->put($path, $pdf);

        if ($certificate->pdf_path && $certificate->pdf_path !== $path) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }
        $certificate->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Path of a stored, real PDF for the certificate, (re)building it when the
     * file is missing or is an old placeholder.
     */
    public function ensurePdf(Certificate $certificate): string
    {
        $disk = Storage::disk('public');
        if ($certificate->pdf_path && $disk->exists($certificate->pdf_path)
            && str_starts_with((string) $disk->get($certificate->pdf_path), '%PDF')) {
            return $certificate->pdf_path;
        }

        return $this->generatePdf($certificate);
    }
}
