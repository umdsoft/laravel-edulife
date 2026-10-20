<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The one pipeline for question / lesson images (upload button, ZIP import,
 * demo seed): png / jpg / webp / gif only — never SVG — up to 5 MB, decoded
 * with GD (so the stored file is a re-encoded bitmap, not the bytes the user
 * sent), scaled down to at most 1600 px on the long side and saved as WebP on
 * the public disk at questions/YYYY/MM/<uuid>.webp.
 *
 * Returns the URL to put in the text: /storage/questions/YYYY/MM/<uuid>.webp
 * (the only image URL form RichTextMedia allows).
 */
class QuestionImageStore
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    public const MAX_SIDE = 1600;

    /** Decoding guard: a 40-megapixel image is ~160 MB in GD memory. */
    public const MAX_PIXELS = 40_000_000;

    public const DIRECTORY = 'questions';

    private const TYPES = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];

    /** @return array{url:string, path:string, width:int, height:int} */
    public function storeUpload(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new InvalidQuestionImage('Fayl yuklanmadi. Qaytadan urinib ko\'ring.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidQuestionImage('Rasm hajmi 5 MB dan oshmasin.');
        }

        return $this->storeBytes((string) file_get_contents($file->getRealPath()));
    }

    /**
     * @return array{url:string, path:string, width:int, height:int}
     *
     * @throws InvalidQuestionImage
     */
    public function storeBytes(string $bytes): array
    {
        if ($bytes === '') {
            throw new InvalidQuestionImage('Fayl bo\'sh.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidQuestionImage('Rasm hajmi 5 MB dan oshmasin.');
        }
        // the content decides the type, not the file name (an .png that is SVG / HTML is refused)
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! isset(self::TYPES[$info[2]])) {
            throw new InvalidQuestionImage('Faqat PNG, JPG, WebP yoki GIF rasm yuklash mumkin (SVG qabul qilinmaydi).');
        }
        [$w, $h] = $info;
        if ($w < 1 || $h < 1 || $w * $h > self::MAX_PIXELS) {
            throw new InvalidQuestionImage('Rasm o\'lchami juda katta (ko\'pi bilan 40 megapiksel).');
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw new InvalidQuestionImage('Rasmni o\'qib bo\'lmadi — fayl buzilgan bo\'lishi mumkin.');
        }
        try {
            if ($info[2] === IMAGETYPE_JPEG) {
                $src = $this->applyExifOrientation($src, $bytes);
            }
            $image = $this->fit($src);
            $width = imagesx($image);
            $height = imagesy($image);

            ob_start();
            imagewebp($image, null, 82);
            $webp = (string) ob_get_clean();
            if ($image !== $src) {
                imagedestroy($image);
            }
        } finally {
            imagedestroy($src);
        }
        if ($webp === '') {
            throw new InvalidQuestionImage('Rasmni saqlab bo\'lmadi.');
        }

        $path = self::DIRECTORY.'/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $webp);

        return ['url' => '/storage/'.$path, 'path' => $path, 'width' => $width, 'height' => $height];
    }

    /** Scale so the long side is ≤ MAX_SIDE; palette images become truecolor with alpha kept. */
    private function fit(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        if ($scale === 1 && imageistruecolor($src)) {
            imagesavealpha($src, true);

            return $src;
        }

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private function applyExifOrientation(\GdImage $img, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $img;
        }
        $rotated = imagerotate($img, $angle, 0);
        if ($rotated === false) {
            return $img;
        }
        imagedestroy($img);

        return $rotated;
    }
}
