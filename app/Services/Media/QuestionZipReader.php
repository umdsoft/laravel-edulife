<?php

namespace App\Services\Media;

/**
 * ZIP import package: questions.json (or questions.csv) + images/.
 *
 *   savollar.zip
 *   ├── questions.json        same format as the JSON template
 *   └── images/
 *       └── uchburchak.png    referenced in a text as ![chizma](images/uchburchak.png)
 *
 * Every image a text references goes through QuestionImageStore (same checks
 * as the "Rasm" button: no SVG, ≤ 5 MB, WebP ≤ 1600 px) once, and the
 * reference is rewritten to its /storage/questions/… URL. A missing or
 * refused image is a row error (shown on the preview); images nobody
 * references are not uploaded. Files are read from the archive in memory,
 * never extracted to disk.
 */
class QuestionZipReader
{
    public const MAX_ENTRIES = 1000;

    public const MAX_IMAGES = 300;

    public const MAX_TOTAL_BYTES = 200 * 1024 * 1024;

    public const MAX_SHEET_BYTES = 5 * 1024 * 1024;

    /** Text columns of a raw row that may reference images. */
    public const TEXT_COLUMNS = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'solution_text'];

    private const LOCAL_IMAGE = '/!\[([^\]\n]{0,300})\]\(\s*(?:\.\/)?([^)\s:]{1,300})\s*\)/u';

    public function __construct(private readonly QuestionImageStore $images) {}

    /**
     * @param  callable(string): array  $readJson  QuestionImportService::readJson
     * @param  callable(string): array  $readCsv  QuestionImportService::readCsv
     * @return array{raw: array<int, array>, errors: list<string>, row_errors: array<int, list<string>>}
     */
    public function read(string $path, callable $readJson, callable $readCsv): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return ['raw' => [], 'errors' => ['ZIP faylni ochib bo\'lmadi.'], 'row_errors' => []];
        }

        try {
            if ($zip->numFiles > self::MAX_ENTRIES) {
                return ['raw' => [], 'errors' => ['ZIP ichida fayllar juda ko\'p (ko\'pi bilan '.self::MAX_ENTRIES.').'], 'row_errors' => []];
            }
            $entries = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = str_replace('\\', '/', (string) $stat['name']);
                if (str_ends_with($name, '/') || str_starts_with(basename($name), '.') || str_contains($name, '__MACOSX')) {
                    continue;
                }
                $entries[$name] = (int) $stat['size'];
                $total += (int) $stat['size'];
            }
            if ($total > self::MAX_TOTAL_BYTES) {
                return ['raw' => [], 'errors' => ['ZIP ichidagi fayllar juda katta (jami 200 MB dan ko\'p).'], 'row_errors' => []];
            }

            // questions.json / questions.csv at the root or inside one folder
            $sheet = null;
            foreach (['questions.json', 'questions.csv'] as $wanted) {
                foreach (array_keys($entries) as $name) {
                    if ($name === $wanted || (substr_count($name, '/') === 1 && basename($name) === $wanted)) {
                        $sheet = $name;
                        break 2;
                    }
                }
            }
            if ($sheet === null) {
                return ['raw' => [], 'errors' => ['ZIP ichida questions.json (yoki questions.csv) topilmadi. Shablonga qarang.'], 'row_errors' => []];
            }
            if ($entries[$sheet] > self::MAX_SHEET_BYTES) {
                return ['raw' => [], 'errors' => [basename($sheet).' juda katta (5 MB dan ko\'p).'], 'row_errors' => []];
            }
            $base = str_contains($sheet, '/') ? dirname($sheet).'/' : '';
            $content = (string) $zip->getFromName($sheet);
            $raw = str_ends_with($sheet, '.json') ? $readJson($content) : $readCsv($content);

            $uploaded = []; // zip path => url | error
            $rowErrors = [];
            foreach ($raw as $line => &$cols) {
                foreach (self::TEXT_COLUMNS as $col) {
                    if (! isset($cols[$col]) || ! is_string($cols[$col]) || ! str_contains($cols[$col], '![')) {
                        continue;
                    }
                    $cols[$col] = preg_replace_callback(self::LOCAL_IMAGE, function ($m) use ($zip, $entries, $base, &$uploaded, &$rowErrors, $line) {
                        $ref = $m[2];
                        if (str_starts_with($ref, '/')) {
                            return $m[0]; // /storage/questions/… — already ours, RichTextMedia checks it
                        }
                        $name = $this->resolve($ref, $entries, $base);
                        if ($name === null) {
                            $rowErrors[$line][] = "Rasm ZIP ichida topilmadi: {$ref}";

                            return $m[0];
                        }
                        if (! array_key_exists($name, $uploaded)) {
                            $uploaded[$name] = $this->upload($zip, $name, $entries[$name], count($uploaded));
                        }
                        if (! str_starts_with($uploaded[$name], '/storage/')) {
                            $rowErrors[$line][] = "{$ref}: {$uploaded[$name]}";

                            return $m[0];
                        }

                        return '!['.$m[1].']('.$uploaded[$name].')';
                    }, $cols[$col]);
                }
            }
            unset($cols);

            return ['raw' => $raw, 'errors' => [], 'row_errors' => $rowErrors];
        } finally {
            $zip->close();
        }
    }

    private function resolve(string $ref, array $entries, string $base): ?string
    {
        $ref = ltrim(rawurldecode($ref), '/');
        if (str_contains($ref, '..')) {
            return null;
        }
        foreach ([$base.$ref, $base.'images/'.basename($ref), $ref] as $candidate) {
            if (isset($entries[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /** URL of the stored image, or the (Uzbek) reason it was refused. */
    private function upload(\ZipArchive $zip, string $name, int $size, int $alreadyUploaded): string
    {
        if ($alreadyUploaded >= self::MAX_IMAGES) {
            return 'bitta ZIP da ko\'pi bilan '.self::MAX_IMAGES.' ta rasm';
        }
        if ($size > QuestionImageStore::MAX_BYTES) {
            return 'rasm hajmi 5 MB dan oshmasin';
        }
        if (! preg_match('/\.(png|jpe?g|webp|gif)$/i', $name)) {
            return 'faqat PNG, JPG, WebP yoki GIF (SVG qabul qilinmaydi)';
        }
        try {
            return $this->images->storeBytes((string) $zip->getFromName($name))['url'];
        } catch (InvalidQuestionImage $e) {
            return mb_strtolower(mb_substr($e->getMessage(), 0, 1)).mb_substr($e->getMessage(), 1);
        }
    }
}
