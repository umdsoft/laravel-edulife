<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadCertificate;
use App\Models\OlympiadLiveLeaderboard;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OlympiadCertificateService
{
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
            'score' => $attempt->total_weighted_score,
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
     * Generate QR code for certificate
     */
    private function generateQrCode(OlympiadCertificate $certificate): string
    {
        $verificationUrl = route('certificates.verify', $certificate->verification_code);
        
        $qrCode = QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->generate($verificationUrl);

        $path = "certificates/qr/{$certificate->id}.png";
        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }

    /**
     * Generate PDF certificate
     */
    private function generatePdf(OlympiadCertificate $certificate): string
    {
        $data = $certificate->prepareCertificateData();
        $data['qr_code_path'] = $certificate->qr_code 
            ? Storage::disk('public')->path($certificate->qr_code)
            : null;

        // Select template based on certificate type
        $template = $this->getTemplate($certificate->certificate_type);

        $pdf = Pdf::loadView("certificates.{$template}", $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
            ]);

        $path = "certificates/pdf/{$certificate->certificate_number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get template name for certificate type
     */
    private function getTemplate(string $type): string
    {
        $templates = [
            OlympiadCertificate::TYPE_WINNER_1 => 'winner_gold',
            OlympiadCertificate::TYPE_WINNER_2 => 'winner_silver',
            OlympiadCertificate::TYPE_WINNER_3 => 'winner_bronze',
            OlympiadCertificate::TYPE_TOP_10 => 'top_ten',
            OlympiadCertificate::TYPE_ADVANCEMENT => 'advancement',
            OlympiadCertificate::TYPE_PARTICIPATION => 'participation',
        ];

        return $templates[$type] ?? 'participation';
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
        if (!$certificate->pdf_path) {
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
