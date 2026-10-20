<?php

namespace App\Console\Commands\Media;

use App\Services\Media\QuestionImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes uploaded question images (public disk, questions/YYYY/MM/*) that no
 * text references any more and that are older than --days (default 7): an
 * image uploaded into a form that was never saved, or removed from the text
 * later. Soft-deleted rows still count as references (they can be restored).
 *
 *   php artisan media:prune-question-images --dry-run
 */
class PruneQuestionImagesCommand extends Command
{
    protected $signature = 'media:prune-question-images
        {--days=7 : Keep unreferenced uploads younger than this}
        {--dry-run : Only list what would be deleted}';

    protected $description = 'Delete uploaded question images that no question, option, solution or lesson text references';

    /** Every column that can hold an image reference (missing tables / columns are skipped). */
    public const SOURCES = [
        'question_bank' => ['question_text', 'question_html', 'solution_text', 'explanation', 'question_media', 'explanation_media', 'options'],
        'question_bank_options' => ['text', 'image'],
        'questions' => ['question_text', 'explanation', 'question_image', 'matching_pairs', 'ordering_items'],
        'question_options' => ['option_text', 'match_text', 'option_image'],
        'lessons' => ['text_content', 'description'],
        'olympiad_demo_questions' => ['question_text', 'explanation', 'options'],
    ];

    private const REF = '#questions/\d{4}/\d{2}/[A-Za-z0-9_-]{1,80}\.(?:webp|png|jpe?g|gif)#';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $referenced = $this->referencedPaths();
        $disk = Storage::disk('public');
        $cutoff = now()->subDays($days)->getTimestamp();

        $deleted = 0;
        $kept = 0;
        foreach ($disk->allFiles(QuestionImageStore::DIRECTORY) as $path) {
            $path = str_replace('\\', '/', $path);
            if (isset($referenced[$path])) {
                $kept++;

                continue;
            }
            if ($disk->lastModified($path) > $cutoff) {
                $kept++;

                continue;
            }
            $this->line(($dry ? '[dry-run] ' : '').'delete '.$path);
            if (! $dry) {
                $disk->delete($path);
            }
            $deleted++;
        }

        $this->info(sprintf('%s %d unreferenced image(s); %d kept (%d referenced).', $dry ? 'Would delete' : 'Deleted', $deleted, $kept, count($referenced)));

        return self::SUCCESS;
    }

    /** @return array<string, true> public-disk paths referenced anywhere */
    public function referencedPaths(): array
    {
        $found = [];
        foreach (self::SOURCES as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            if ($columns === []) {
                continue;
            }
            $query = DB::table($table)->select($columns)->where(function ($q) use ($columns) {
                foreach ($columns as $c) {
                    $q->orWhere($c, 'like', '%questions/%');
                }
            });
            foreach ($query->cursor() as $row) {
                foreach ($columns as $c) {
                    $value = (string) ($row->{$c} ?? '');
                    if ($value !== '' && preg_match_all(self::REF, str_replace('\\/', '/', $value), $m)) {
                        foreach ($m[0] as $p) {
                            $found[$p] = true;
                        }
                    }
                }
            }
        }

        return $found;
    }
}
