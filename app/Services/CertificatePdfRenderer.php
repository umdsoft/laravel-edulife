<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Renders course and olympiad certificates as real A4-landscape PDFs (DomPDF,
 * DejaVu Sans for Uzbek text). The verification QR code is an inline SVG, so
 * neither Imagick nor a PNG back end is needed.
 */
class CertificatePdfRenderer
{
    /**
     * @param array{
     *     title: string, kind?: ?string, recipient: string, lead: string, subject: string,
     *     details?: array<string>, number: string, verificationCode: string, verifyUrl: string,
     *     issuedAt: string, signatory?: ?string, signatoryRole?: ?string
     * } $data
     */
    public function render(array $data): string
    {
        $data += [
            'kind' => null,
            'details' => [],
            'signatory' => null,
            'signatoryRole' => null,
        ];
        $data['qrDataUri'] = $this->qrDataUri($data['verifyUrl']);

        return Pdf::loadView('pdf.certificate', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false])
            ->output();
    }

    /**
     * QR code of the verification link as an SVG data URI.
     */
    public function qrSvg(string $url): string
    {
        return (string) QrCode::format('svg')->size(240)->margin(0)->errorCorrection('M')->generate($url);
    }

    private function qrDataUri(string $url): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->qrSvg($url));
    }
}
