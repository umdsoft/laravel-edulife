<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\OlympiadCertificate;
use Inertia\Inertia;

class CertificateVerificationController extends Controller
{
    /**
     * {code} is the verification code (the QR link) or the certificate number
     * printed in large type (EDULIFE-2026-…, OLY-2026-…); either finds it.
     */
    public function verify(string $code)
    {
        $code = strtoupper(trim($code));

        $certificate = Certificate::with(['user', 'course.direction'])
            ->where(fn ($q) => $q->where('certificate_code', $code)
                ->orWhere('verification_code', $code)
                ->orWhere('certificate_number', $code))
            ->where(fn ($q) => $q->whereNull('is_valid')->orWhere('is_valid', true))
            ->first();

        if (!$certificate) {
            // the QR code on an olympiad certificate points here too
            $olympiadCertificate = OlympiadCertificate::with(['user', 'olympiad.olympiadType'])
                ->where(fn ($q) => $q->where('verification_code', $code)->orWhere('certificate_number', $code))
                ->first();

            if ($olympiadCertificate && $olympiadCertificate->is_verified) {
                return Inertia::render('Certificate/Verify', [
                    'found' => true,
                    'certificate' => [
                        'number' => $olympiadCertificate->certificate_number,
                        'code' => $olympiadCertificate->verification_code,
                        'student_name' => $olympiadCertificate->user->full_name,
                        'subject_label' => 'Olimpiada',
                        'course_title' => $olympiadCertificate->olympiad->title,
                        // "Yo'nalish" is the subject; "2-o'rin sertifikati" is the award
                        'course_direction' => $olympiadCertificate->olympiad?->olympiadType?->display_name_uz
                            ?? $olympiadCertificate->olympiad?->olympiadType?->display_name,
                        'award' => $olympiadCertificate->certificate_type_label,
                        'issued_at' => $olympiadCertificate->issued_at->format('d.m.Y'),
                        'pdf_url' => null,
                        'verification_count' => null,
                    ],
                ]);
            }

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
                // the same number the student's certificate page and the PDF show
                'number' => $certificate->certificate_number,
                'code' => $certificate->certificate_code,
                'subject_label' => 'Kurs',
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
