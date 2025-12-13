<?php

namespace Database\Seeders\English\Speakout;

use Illuminate\Database\Seeder;
use App\Models\English\EnglishLevel;
use Illuminate\Support\Facades\DB;

class A1SpeakoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Yaratadi: Speakout Starter 3rd Edition asosida A1 kursni
     * - 8 ta Unit
     * - 40 ta Lesson
     * - ~350 ta Vocabulary
     * - 32 ta Grammar Rule
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting A1 Speakout Course Seeder...');

        DB::beginTransaction();

        try {
            // 1. A1 Level olish
            $a1Level = EnglishLevel::where('code', 'A1')->first();

            if (!$a1Level) {
                throw new \Exception('❌ A1 level not found in database. Please run LevelSeeder first.');
            }

            $this->command->info("✓ Found A1 Level: {$a1Level->title}");

            // 2. Eski A1 contentni o'chirish
            $this->command->warn('⚠️  Deleting old A1 content...');
            $this->deleteOldA1Content($a1Level);

            // 3. Metadata yuklash
            $metadata = $this->loadMetadata();
            $this->command->info("📚 Loading: {$metadata['course']['source']} ({$metadata['course']['edition']} Edition)");

            // 4. Har bir unit uchun seeder chaqirish
            for ($unitNum = 1; $unitNum <= 8; $unitNum++) {
                $this->command->info("📖 Processing Unit {$unitNum}...");

                $unitSeeder = new A1SpeakoutUnitSeeder($a1Level, $unitNum, $this->command);
                $unitSeeder->run();

                $this->command->info("   ✓ Unit {$unitNum} completed");
            }

            DB::commit();

            $this->command->newLine();
            $this->command->info('════════════════════════════════════════');
            $this->command->info('✅ A1 Speakout course seeded successfully!');
            $this->command->info('════════════════════════════════════════');
            $this->command->info("📊 Statistics:");
            $this->command->info("   • Units: 8");
            $this->command->info("   • Lessons: ~40");
            $this->command->info("   • Vocabulary: ~350");
            $this->command->info("   • Grammar Rules: ~32");
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Eski A1 contentni o'chirish
     */
    private function deleteOldA1Content(EnglishLevel $level): void
    {
        $oldUnits = $level->units()->with('lessons')->get();

        if ($oldUnits->isEmpty()) {
            $this->command->info('   No old content to delete');
            return;
        }

        $deletedUnits = 0;
        $deletedLessons = 0;
        $deletedVocab = 0;
        $deletedGrammar = 0;

        foreach ($oldUnits as $unit) {
            foreach ($unit->lessons as $lesson) {
                // Lesson steps o'chirish
                $lesson->steps()->delete();

                // Vocabulary pivot o'chirish
                $vocabCount = $lesson->vocabulary()->count();
                $lesson->vocabulary()->detach();
                $deletedVocab += $vocabCount;

                // Grammar pivot o'chirish
                $grammarCount = $lesson->grammarRules()->count();
                $lesson->grammarRules()->detach();
                $deletedGrammar += $grammarCount;

                $lesson->delete();
                $deletedLessons++;
            }
            $unit->delete();
            $deletedUnits++;
        }

        $this->command->info("   ✓ Deleted: {$deletedUnits} units, {$deletedLessons} lessons");
        $this->command->info("   ✓ Detached: {$deletedVocab} vocabulary, {$deletedGrammar} grammar relations");
    }

    /**
     * Metadata yuklash
     */
    private function loadMetadata(): array
    {
        $path = storage_path('data/a1-speakout/metadata.json');

        if (!file_exists($path)) {
            throw new \Exception("Metadata file not found: {$path}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in metadata file: ' . json_last_error_msg());
        }

        return $data;
    }
}
