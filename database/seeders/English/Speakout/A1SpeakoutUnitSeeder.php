<?php

namespace Database\Seeders\English\Speakout;

use Illuminate\Database\Seeder;
use Illuminate\Console\Command;
use App\Models\English\{EnglishLevel, EnglishUnit};

class A1SpeakoutUnitSeeder extends Seeder
{
    private EnglishLevel $level;
    private int $unitNumber;
    private ?Command $console;

    public function __construct(EnglishLevel $level, int $unitNumber, ?Command $console = null)
    {
        $this->level = $level;
        $this->unitNumber = $unitNumber;
        $this->console = $console;
    }

    /**
     * Run the unit seeder
     */
    public function run(): void
    {
        // Unit JSON faylini yuklash
        $unitData = $this->loadUnitData();

        // Unit yaratish
        $unit = EnglishUnit::create([
            'level_id' => $this->level->id,
            'unit_number' => $unitData['unit']['number'],
            'slug' => $unitData['unit']['slug'],
            'title' => $unitData['unit']['title_en'],
            'title_uz' => $unitData['unit']['title_uz'],
            'description_uz' => $unitData['unit']['description_uz'],
            'objectives' => $unitData['unit']['objectives'],
            'vocabulary_focus' => $unitData['unit']['vocabulary_focus'],
            'grammar_focus' => $unitData['unit']['grammar_focus'],
            'estimated_minutes' => $unitData['unit']['estimated_minutes'],
            'xp_reward' => $unitData['unit']['xp_reward'],
            'is_free' => $unitData['unit']['is_free'] ?? false,
            'is_active' => true,
        ]);

        if ($this->console) {
            $this->console->info("      ✓ Unit created: {$unit->title_uz}");
        }

        // Lessons yaratish
        if (isset($unitData['unit']['lessons']) && !empty($unitData['unit']['lessons'])) {
            $lessonCount = 0;
            foreach ($unitData['unit']['lessons'] as $lessonData) {
                $lessonSeeder = new A1SpeakoutLessonSeeder($unit, $lessonData, $this->console);
                $lessonSeeder->run();
                $lessonCount++;
            }

            if ($this->console) {
                $this->console->info("      ✓ Created {$lessonCount} lessons");
            }
        } else {
            if ($this->console) {
                $this->console->warn("      ⚠️  No lessons found in unit data");
            }
        }
    }

    /**
     * Unit JSON faylini yuklash
     */
    private function loadUnitData(): array
    {
        $filename = $this->getUnitFilename();
        $path = storage_path("data/a1-speakout/{$filename}");

        if (!file_exists($path)) {
            throw new \Exception("Unit file not found: {$filename}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in {$filename}: " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Unit nomini olish
     */
    private function getUnitFilename(): string
    {
        $filenames = [
            1 => 'unit-1-welcome.json',
            2 => 'unit-2-people.json',
            3 => 'unit-3-things.json',
            4 => 'unit-4-everyday.json',
            5 => 'unit-5-action.json',
            6 => 'unit-6-where.json',
            7 => 'unit-7-health.json',
            8 => 'unit-8-time-out.json',
        ];

        if (!isset($filenames[$this->unitNumber])) {
            throw new \Exception("Invalid unit number: {$this->unitNumber}");
        }

        return $filenames[$this->unitNumber];
    }
}
