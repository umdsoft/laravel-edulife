<?php

namespace App\Services\Exam;

use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\User;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use GdImage;

/**
 * Shareable result card, 1080x1920 PNG (spec section 9), drawn with GD
 * (no Intervention/Imagick/Browsershot in the project; GD + FreeType are).
 *
 * Shows: week, subject, score and percent, "+delta" only when the student
 * grew, the most improved topic, rank, and a QR code with the student's
 * referral link. The student's name is NOT on the card.
 */
class ResultCardRenderer
{
    private const BRAND = [79, 70, 229];

    private const BRAND_DARK = [55, 48, 163];

    private const INK = [15, 23, 42];

    private const MUTED = [71, 84, 103];

    private const SUBTLE = [102, 112, 133];

    private const SURFACE = [255, 255, 255];

    private const CANVAS = [241, 243, 246];

    private const SUCCESS = [22, 163, 74];

    private const PHYSICS = [13, 148, 136];

    public function __construct(
        private readonly ReportBuilder $reports,
        private readonly ReferralService $referrals,
    ) {}

    /** PNG bytes. */
    public function render(StudentWeeklyResult $result, User $owner): string
    {
        return $this->draw($this->data($result, $owner));
    }

    /**
     * What the card shows. Deliberately no name: the card is shared publicly.
     */
    public function data(StudentWeeklyResult $result, User $owner): array
    {
        $subject = Subject::query()->find($result->subject_id, ['id', 'code', 'name_uz']);

        return [
            'week' => $result->week_start->format('d.m.Y').' – '.$result->week_start->copy()->addDays(6)->format('d.m.Y'),
            'subject' => $subject?->name_uz ?? 'Imtihon',
            'subject_code' => $subject?->code,
            'score' => self::num((float) $result->score),
            'max_score' => self::num((float) $result->max_score),
            'percent' => round((float) $result->score_percent, 1),
            'delta' => $result->delta_prev !== null && (float) $result->delta_prev > 0 ? round((float) $result->delta_prev, 1) : null,
            'rank' => $result->rank,
            'participants' => $result->participants_count,
            'improved' => $this->reports->mostImprovedTopic($result),
            'link' => $this->referrals->linkFor($owner),
        ];
    }

    /**
     * @param  array{week: string, subject: string, subject_code: ?string, score: string, max_score: string, percent: float, delta: ?float, rank: ?int, participants: ?int, improved: ?array, link: string}  $d
     */
    public function draw(array $d): string
    {
        $w = (int) config('exam.result_card.width', 1080);
        $h = (int) config('exam.result_card.height', 1920);
        $regular = (string) config('exam.result_card.font_regular');
        $bold = (string) config('exam.result_card.font_bold');
        if (! is_file($regular) || ! is_file($bold)) {
            throw new \RuntimeException('Result card fonts are missing (config exam.result_card.font_*).');
        }

        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imageantialias($img, true);
        $c = fn (array $rgb) => imagecolorallocate($img, ...$rgb);

        // background + brand header
        imagefilledrectangle($img, 0, 0, $w, $h, $c(self::CANVAS));
        imagefilledrectangle($img, 0, 0, $w, 620, $c(self::BRAND));
        imagefilledellipse($img, $w - 80, 80, 420, 420, $c(self::BRAND_DARK));
        imagefilledellipse($img, 60, 560, 260, 260, $c(self::BRAND_DARK));

        $this->text($img, 'EDULIFE', $bold, 34, 80, 130, $c(self::SURFACE));
        $this->text($img, 'Shanba imtihoni', $bold, 72, 80, 290, $c(self::SURFACE));
        $this->text($img, $d['week'], $regular, 38, 80, 370, $c([224, 231, 255]));

        // subject pill
        $accent = $d['subject_code'] === 'physics' ? self::PHYSICS : self::BRAND_DARK;
        $pillW = $this->textWidth($d['subject'], $bold, 34) + 80;
        $this->roundedRect($img, 80, 440, 80 + $pillW, 520, 40, $c($accent));
        $this->text($img, $d['subject'], $bold, 34, 120, 495, $c(self::SURFACE));

        // main card
        $this->roundedRect($img, 60, 560, $w - 60, 1300, 48, $c(self::SURFACE));
        $this->centered($img, 'Natija', $regular, 40, $w / 2, 690, $c(self::SUBTLE));
        $this->centered($img, $d['score'].' / '.$d['max_score'], $bold, 120, $w / 2, 860, $c(self::INK));
        $this->centered($img, self::num($d['percent']).'%', $bold, 64, $w / 2, 970, $c(self::BRAND));

        $y = 1080;
        if ($d['delta'] !== null) {
            $label = '+'.self::num($d['delta']).' ball o\'tgan haftaga nisbatan';
            $bw = $this->textWidth($label, $bold, 34) + 80;
            $this->roundedRect($img, (int) ($w / 2 - $bw / 2), $y - 60, (int) ($w / 2 + $bw / 2), $y + 20, 40, $c([220, 252, 231]));
            $this->centered($img, $label, $bold, 34, $w / 2, $y - 5, $c(self::SUCCESS));
            $y += 110;
        }
        if ($d['rank']) {
            $rank = $d['rank'].'-o\'rin'.($d['participants'] ? ' / '.$d['participants'].' ishtirokchi' : '');
            $this->centered($img, $rank, $regular, 36, $w / 2, $y, $c(self::MUTED));
        }

        // most improved topic
        if ($d['improved']) {
            $this->roundedRect($img, 60, 1330, $w - 60, 1500, 48, $c(self::SURFACE));
            $this->text($img, 'Eng ko\'p o\'sgan mavzu', $regular, 34, 120, 1395, $c(self::SUBTLE));
            $this->text($img, $this->fit($d['improved']['name'], $bold, 46, $w - 240), $bold, 46, 120, 1465, $c(self::INK));
        }

        // QR with the referral link
        $qrSize = 260;
        $qrX = $w - 60 - $qrSize - 30;
        $qrY = $h - 60 - $qrSize - 30;
        $this->roundedRect($img, 60, $qrY - 30, $w - 60, $h - 60, 48, $c(self::SURFACE));
        $this->qr($img, $d['link'], $qrX, $qrY, $qrSize);
        $this->text($img, 'Do\'stingni taklif qil', $bold, 40, 120, $qrY + 80, $c(self::INK));
        $this->text($img, 'QR kodni skanerla va', $regular, 32, 120, $qrY + 150, $c(self::MUTED));
        $this->text($img, 'qabul testini bepul topshir', $regular, 32, 120, $qrY + 200, $c(self::MUTED));

        ob_start();
        imagepng($img, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private function qr(GdImage $img, string $text, int $x, int $y, int $size): void
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $n = $matrix->getWidth();
        $quiet = 2;
        $cell = intdiv($size, $n + 2 * $quiet);
        $offset = intdiv($size - $cell * $n, 2);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, ...self::INK);
        imagefilledrectangle($img, $x, $y, $x + $size, $y + $size, $white);
        for ($row = 0; $row < $n; $row++) {
            for ($col = 0; $col < $n; $col++) {
                if ($matrix->get($col, $row) === 1) {
                    $px = $x + $offset + $col * $cell;
                    $py = $y + $offset + $row * $cell;
                    imagefilledrectangle($img, $px, $py, $px + $cell - 1, $py + $cell - 1, $black);
                }
            }
        }
    }

    private function text(GdImage $img, string $text, string $font, int $size, float $x, float $y, int $color): void
    {
        imagettftext($img, $size, 0, (int) $x, (int) $y, $color, $font, $text);
    }

    private function centered(GdImage $img, string $text, string $font, int $size, float $cx, float $y, int $color): void
    {
        $this->text($img, $text, $font, $size, $cx - $this->textWidth($text, $font, $size) / 2, $y, $color);
    }

    private function textWidth(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return (int) abs($box[2] - $box[0]);
    }

    /** Shorten with an ellipsis to fit $max pixels. */
    private function fit(string $text, string $font, int $size, int $max): string
    {
        if ($this->textWidth($text, $font, $size) <= $max) {
            return $text;
        }
        while (mb_strlen($text) > 1 && $this->textWidth($text.'…', $font, $size) > $max) {
            $text = mb_substr($text, 0, -1);
        }

        return rtrim($text).'…';
    }

    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** 34.5 -> "34,5"; 34.0 -> "34" (Uzbek decimal comma). */
    public static function num(float $value): string
    {
        $s = rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');

        return $s === '' ? '0' : $s;
    }
}
