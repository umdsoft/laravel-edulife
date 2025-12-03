<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Inertia\Inertia;

class CertificateVerificationController extends Controller
{
    public function verify(string $code)
    {
        $certificate = Certificate::with(['user', 'course', 'template'])
            ->where('certificate_code', $code)
            ->first();

        if (!$certificate) {
            return Inertia::render('Certificate/Verify', [
                'found' => false,
                'certificate' => null,
            ]);
        }

        // Increment verification count
        $certificate->increment('verification_count');
        $certificate->update(['verified_at' => now()]);

        return Inertia::render('Certificate/Verify', [
            'found' => true,
            'certificate' => [
                'code' => $certificate->certificate_code,
                'student_name' => $certificate->user->full_name ?? $certificate->user->first_name . ' ' . $certificate->user->last_name,
                'course_title' => $certificate->course->title,
                'course_direction' => $certificate->course->direction->name ?? null,
                'issued_at' => $certificate->issued_at->format('d.m.Y'),
                'pdf_url' => $certificate->pdf_path ? asset('storage/' . $certificate->pdf_path) : null,
                'verification_count' => $certificate->verification_count,
            ],
        ]);
    }
}
