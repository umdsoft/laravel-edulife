<?php

namespace App\Services\Media;

/**
 * The downloadable ZIP import template: questions.json + images/uchburchak.png,
 * one question with a picture and one with a ```graph block (answers checked:
 * hypotenuse of a 3–4 right triangle is 5; area under the v–t graph is 20 m).
 */
class QuestionZipTemplate
{
    public function questions(): array
    {
        return [
            [
                'subject' => 'math', 'topic' => 'math.planimetry.triangles', 'type' => 'single_choice', 'difficulty' => 2,
                'question' => "Rasmdagi to'g'ri burchakli uchburchakda \$AC = 3\$, \$BC = 4\$. Gipotenuza \$AB\$ ni toping.\n\n![To'g'ri burchakli uchburchak ABC](images/uchburchak.png){width=60%}",
                'options' => ['$5$', '$7$', '$\\sqrt{7}$', '$12$'],
                'correct' => 'A',
                'source_ref' => 'Shablon',
                'solution_text' => 'Pifagor teoremasi: $AB = \\sqrt{3^2 + 4^2} = 5$.',
            ],
            [
                'subject' => 'physics', 'topic' => 'phys.kinematics.accelerated', 'type' => 'numeric_with_unit', 'difficulty' => 3,
                'question' => "Jism tezligining vaqtga bog'liqlik grafigi berilgan. 0–6 s oralig'ida jism bosib o'tgan yo'lni toping.\n\n```graph\n{\"x\":[0,7],\"y\":[0,5],\"ticks\":[1,1],\"axes\":{\"x\":\"t, s\",\"y\":\"v, m/s\"},\"polylines\":[{\"points\":[[0,0],[2,4],[6,4]],\"label\":\"v(t)\"}],\"segments\":[{\"from\":[6,0],\"to\":[6,4],\"dashed\":true}]}\n```",
                'answer' => ['value' => 20, 'unit' => 'm', 'tolerance' => 0, 'tolerance_type' => 'abs'],
                'source_ref' => 'Shablon',
                'solution_text' => "Yo'l — grafik ostidagi yuza: $\\frac{1}{2}\\cdot 2 \\cdot 4 + 4 \\cdot 4 = 4 + 16 = 20$ m.",
            ],
        ];
    }

    /** ZIP bytes. */
    public function build(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qzip');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('questions.json', json_encode(['questions' => $this->questions()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $zip->addFromString('images/uchburchak.png', self::trianglePng());
        $zip->addFromString('README.txt', implode("\n", [
            'ZIP import: questions.json + images/ papkasi.',
            "Matnda rasmga havola: ![izoh](images/fayl.png) — import paytida rasm yuklanadi va havola almashtiriladi.",
            'Rasm: PNG, JPG, WebP yoki GIF, 5 MB gacha (SVG qabul qilinmaydi).',
            'Grafik: ```graph bloki (docs/exam/RASM_VA_GRAFIK.md).',
            '',
        ]));
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /** Right triangle ABC (C = 90°), legs 3 and 4, drawn with GD. */
    public static function trianglePng(int $w = 480, int $h = 360): string
    {
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $ink = imagecolorallocate($img, 15, 23, 42);
        $accent = imagecolorallocate($img, 67, 56, 202);
        imagefill($img, 0, 0, $white);
        imagesetthickness($img, 3);

        $C = [80, 300];
        $A = [80, 300 - 3 * 60];  // AC = 3 (up)
        $B = [80 + 4 * 75, 300];  // BC = 4 (right)
        imageline($img, $A[0], $A[1], $C[0], $C[1], $ink);
        imageline($img, $C[0], $C[1], $B[0], $B[1], $ink);
        imageline($img, $A[0], $A[1], $B[0], $B[1], $accent);
        // right-angle mark at C
        imagesetthickness($img, 2);
        imagerectangle($img, $C[0], $C[1] - 20, $C[0] + 20, $C[1], $ink);

        $font = 5;
        imagestring($img, $font, $A[0] - 22, $A[1] - 10, 'A', $ink);
        imagestring($img, $font, $B[0] + 10, $B[1] - 8, 'B', $ink);
        imagestring($img, $font, $C[0] - 22, $C[1] - 4, 'C', $ink);
        imagestring($img, $font, $C[0] - 24, ($A[1] + $C[1]) / 2 - 8, '3', $ink);
        imagestring($img, $font, ($C[0] + $B[0]) / 2 - 4, $C[1] + 10, '4', $ink);
        imagestring($img, $font, ($A[0] + $B[0]) / 2 + 10, ($A[1] + $B[1]) / 2 - 20, '?', $accent);

        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
