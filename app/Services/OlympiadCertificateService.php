<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadCertificate;
use App\Models\OlympiadLiveLeaderboard;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class OlympiadCertificateService
{
    public function __construct(protected CertificatePdfRenderer $renderer) {}

    /**
     * Generate certificate for an attempt
     */
    public function generate(OlympiadAttempt $attempt): OlympiadCertificate
    {
        $olympiad = $attempt->olympiad;
        $user = $attempt->user;
        $leaderboardEntry = $attempt->leaderboardEntry;

        $certificateType = OlympiadCertificate::getCertificateTypeFromRank($leaderboardEntry?->rank);

        $certificate = OlympiadCertificate::create([
            'olympiad_id' => $olympiad->id,
            'user_id' => $user->id,
            'attempt_id' => $attempt->id,
            'certificate_type' => $certificateType,
            'rank' => $leaderboardEntry?->rank,
            'score' => $attempt->total_raw_score,
            'issued_at' => now(),
        ]);

        // Generate QR code
        $certificate->qr_code = $this->generateQrCode($certificate);
        
        // Generate PDF
        $certificate->pdf_path = $this->generatePdf($certificate);
        
        // Prepare certificate data
        $certificate->certificate_data = $certificate->prepareCertificateData();

        $certificate->save();

        return $certificate;
    }

    /**
     * QR code of the verification link, stored as SVG (no Imagick needed).
     */
    private function generateQrCode(OlympiadCertificate $certificate): string
    {
        $path = "certificates/qr/{$certificate->id}.svg";
        Storage::disk('public')->put($path, $this->renderer->qrSvg($certificate->verification_url));

        return $path;
    }

    private function generatePdf(OlympiadCertificate $certificate): string
    {
        $certificate->loadMissing(['olympiad.olympiadType', 'olympiad.stage', 'user', 'attempt']);
        $olympiad = $certificate->olympiad;
        $attempt = $certificate->attempt;

        $details = array_values(array_filter([
            $certificate->rank ? "O'rin: {$certificate->rank}" : null,
            $attempt ? 'Ball: ' . number_format((float) $attempt->total_raw_score, 2) . ' / ' . number_format((float) $attempt->total_max_score, 2) : null,
            $olympiad->stage?->display_name,
        ]));

        $pdf = $this->renderer->render([
            'title' => 'Sertifikat',
            'kind' => $certificate->certificate_type_label,
            'recipient' => (string) $certificate->user->name,
            'lead' => ($olympiad->olympiadType?->display_name ?? 'Olimpiada') . ' ishtirokchisi sifatida quyidagi olimpiadada qatnashdi:',
            'subject' => (string) $olympiad->title,
            'details' => $details,
            'number' => (string) $certificate->certificate_number,
            'verificationCode' => (string) $certificate->verification_code,
            'verifyUrl' => $certificate->verification_url,
            'issuedAt' => ($certificate->issued_at ?? now())->format('d.m.Y'),
            'signatory' => null,
            'signatoryRole' => null,
        ]);

        $path = "certificates/pdf/{$certificate->certificate_number}.pdf";
        Storage::disk('public')->put($path, $pdf);

        return $path;
    }

    /**
     * Bulk generate certificates for olympiad
     */
    public function bulkGenerate(Olympiad $olympiad): int
    {
        $attempts = $olympiad->attempts()
            ->completed()
            ->notDisqualified()
            ->with(['user', 'leaderboardEntry'])
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
            $existing = OlympiadCertificate::where('olympiad_id', $olympiad->id)
                ->where('user_id', $attempt->user_id)
                ->first();

            if (!$existing) {
                $this->generate($attempt);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Verify certificate
     */
    public function verify(string $code): ?array
    {
        $certificate = OlympiadCertificate::where('verification_code', $code)
            ->orWhere('certificate_number', $code)
            ->with(['olympiad', 'user'])
            ->first();

        if (!$certificate) {
            return null;
        }

        return [
            'valid' => $certificate->is_verified,
            'certificate_number' => $certificate->certificate_number,
            'certificate_type' => $certificate->certificate_type_label,
            'holder_name' => $certificate->user->name,
            'olympiad_title' => $certificate->olympiad->title,
            'issued_at' => $certificate->issued_at->format('d.m.Y'),
            'rank' => $certificate->rank,
            'score' => $certificate->score,
        ];
    }

    /**
     * Get user's certificates
     */
    public function getUserCertificates(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return OlympiadCertificate::with(['olympiad.olympiadType', 'olympiad.stage'])
            ->where('user_id', $userId)
            ->orderByDesc('issued_at')
            ->get();
    }

    /**
     * Download certificate
     */
    public function download(OlympiadCertificate $certificate): string
    {
        if (!$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            $certificate->pdf_path = $this->generatePdf($certificate);
            $certificate->save();
        }

        $certificate->recordDownload();

        return Storage::disk('public')->path($certificate->pdf_path);
    }

    /**
     * Regenerate certificate
     */
    public function regenerate(OlympiadCertificate $certificate): OlympiadCertificate
    {
        // Delete old files
        if ($certificate->pdf_path) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }
        if ($certificate->qr_code) {
            Storage::disk('public')->delete($certificate->qr_code);
        }

        // Regenerate
        $certificate->qr_code = $this->generateQrCode($certificate);
        $certificate->pdf_path = $this->generatePdf($certificate);
        $certificate->certificate_data = $certificate->prepareCertificateData();
        $certificate->save();

        return $certificate;
    }
}
