<?php

namespace Database\Seeders\English\Speakout;

use Illuminate\Database\Seeder;
use Illuminate\Console\Command;
use App\Models\English\{
    EnglishUnit,
    EnglishLesson,
    EnglishVocabulary,
    EnglishGrammarRule,
    EnglishExampleSentence,
    EnglishCommonMistake
};

class A1SpeakoutLessonSeeder extends Seeder
{
    private EnglishUnit $unit;
    private array $lessonData;
    private ?Command $console;

    public function __construct(EnglishUnit $unit, array $lessonData, ?Command $console = null)
    {
        $this->unit = $unit;
        $this->lessonData = $lessonData;
        $this->console = $console;
    }

    /**
     * Run the lesson seeder
     */
    public function run(): void
    {
        // Lesson yaratish
        $lesson = EnglishLesson::create([
            'unit_id' => $this->unit->id,
            'lesson_number' => $this->lessonData['lesson_number'],
            'slug' => $this->lessonData['slug'],
            'title' => $this->lessonData['title_en'],
            'title_uz' => $this->lessonData['title_uz'],
            'lesson_type' => $this->lessonData['lesson_type'],
            'estimated_minutes' => $this->lessonData['estimated_minutes'],
            'xp_reward' => $this->lessonData['xp_reward'],
            'coin_reward' => $this->lessonData['coin_reward'],
            'content' => $this->lessonData['content'] ?? [],
            'is_active' => true,
        ]);

        if ($this->console) {
            $this->console->info("         • Lesson {$lesson->lesson_number}: {$lesson->title_uz}");
        }

        // Vocabulary qo'shish
        if (isset($this->lessonData['content']['vocabulary'])) {
            $vocabCount = $this->attachVocabulary($lesson);
            if ($this->console && $vocabCount > 0) {
                $this->console->info("           ✓ {$vocabCount} vocabulary items");
            }
        }

        // Grammar qo'shish
        if (isset($this->lessonData['content']['grammar'])) {
            $this->attachGrammar($lesson);
            if ($this->console) {
                $grammarSlug = $this->lessonData['content']['grammar']['slug'] ?? 'unknown';
                $this->console->info("           ✓ Grammar: {$grammarSlug}");
            }
        }
    }

    /**
     * Vocabulary attach qilish
     */
    private function attachVocabulary(EnglishLesson $lesson): int
    {
        $vocabularyIds = [];

        foreach ($this->lessonData['content']['vocabulary'] as $vocabData) {
            // Vocabulary yaratish yoki topish
            $vocab = EnglishVocabulary::firstOrCreate(
                ['word_lowercase' => strtolower($vocabData['word'])],
                [
                    'level_id' => $this->unit->level_id,
                    'word' => $vocabData['word'],
                    'phonetic_uk' => $vocabData['phonetic_uk'] ?? null,
                    'phonetic_us' => $vocabData['phonetic_us'] ?? null,
                    'audio_uk' => $vocabData['audio_uk'] ?? null,
                    'audio_us' => $vocabData['audio_us'] ?? null,
                    'part_of_speech' => $vocabData['part_of_speech'],
                    'definition' => $vocabData['definition'],
                    'definition_uz' => $vocabData['definition_uz'],
                    'definition_simple' => $vocabData['definition_simple'] ?? null,
                    'tags' => $vocabData['tags'] ?? [],
                    'image' => $vocabData['image_url'] ?? null,
                    'image_thumbnail' => $vocabData['thumbnail_url'] ?? null,
                    'frequency_rank' => $vocabData['frequency_rank'] ?? null,
                    'difficulty_score' => $vocabData['difficulty_score'] ?? 0.5,
                ]
            );

            $vocabularyIds[] = $vocab->id;

            // Example sentences qo'shish (eski example sentenclarni o'chirmaslik uchun check qilamiz)
            if (isset($vocabData['example_sentences'])) {
                foreach ($vocabData['example_sentences'] as $example) {
                    // Agar aynan shu sentence mavjud bo'lsa, qayta qo'shmaymiz
                    $existingSentence = $vocab->exampleSentences()
                        ->where('sentence', $example['sentence_en'])
                        ->first();

                    if (!$existingSentence) {
                        $vocab->exampleSentences()->create([
                            'sentence' => $example['sentence_en'],
                            'sentence_uz' => $example['sentence_uz'] ?? null,
                            'audio_url' => $example['audio'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Pivot table'ga qo'shish (sync ishlatamiz, shunda duplicate bo'lmaydi)
        $lesson->vocabulary()->sync($vocabularyIds);

        return count($vocabularyIds);
    }

    /**
     * Grammar attach qilish
     */
    private function attachGrammar(EnglishLesson $lesson): void
    {
        $grammarData = $this->lessonData['content']['grammar'];

        // Category slug'dan ID topish
        $categorySlug = $grammarData['category'] ?? 'verbs';
        $category = \App\Models\English\EnglishGrammarCategory::where('slug', $categorySlug)->first();

        if (!$category) {
            // Default: verbs category
            $category = \App\Models\English\EnglishGrammarCategory::where('slug', 'verbs')->first();
        }

        // Tips'larni to'g'ri formatga o'tkazish
        $tips = [];
        $tipsUz = [];
        if (isset($grammarData['tips'])) {
            foreach ($grammarData['tips'] as $tip) {
                if (is_array($tip)) {
                    $tips[] = $tip['tip_en'] ?? '';
                    $tipsUz[] = $tip['tip_uz'] ?? '';
                } else {
                    $tips[] = $tip;
                    $tipsUz[] = $tip;
                }
            }
        }

        // Grammar rule yaratish yoki topish
        $grammar = EnglishGrammarRule::firstOrCreate(
            ['slug' => $grammarData['slug']],
            [
                'level_id' => $this->unit->level_id,
                'category_id' => $category->id,
                'title' => $grammarData['title_en'],
                'title_uz' => $grammarData['title_uz'],
                'explanation' => $grammarData['explanation_en'] ?? $grammarData['explanation_uz'],
                'explanation_uz' => $grammarData['explanation_uz'],
                'explanation_simple' => $grammarData['explanation_simple'] ?? null,
                'formulas' => $grammarData['formulas'],
                'usage_cases' => $grammarData['usage_cases'] ?? [],
                'signal_words' => $grammarData['signal_words'] ?? [],
                'tips' => $tips,
                'tips_uz' => $tipsUz,
                'related_rule_ids' => $grammarData['related_rules'] ?? [],
                'video_url' => $grammarData['video_url'] ?? null,
                'infographic_url' => $grammarData['infographic_url'] ?? null,
            ]
        );

        // Common mistakes qo'shish
        if (isset($grammarData['common_mistakes'])) {
            foreach ($grammarData['common_mistakes'] as $mistake) {
                // Agar aynan shu mistake mavjud bo'lsa, qayta qo'shmaymiz
                $existingMistake = $grammar->commonMistakes()
                    ->where('incorrect_form', $mistake['wrong'])
                    ->where('correct_form', $mistake['correct'])
                    ->first();

                if (!$existingMistake) {
                    $grammar->commonMistakes()->create([
                        'level_id' => $this->unit->level_id,
                        'incorrect_form' => $mistake['wrong'],
                        'correct_form' => $mistake['correct'],
                        'explanation' => $mistake['explanation_en'] ?? $mistake['explanation_uz'],
                        'explanation_uz' => $mistake['explanation_uz'],
                        'mistake_type' => 'grammar',
                    ]);
                }
            }
        }

        // Pivot table'ga qo'shish
        $lesson->grammarRules()->syncWithoutDetaching($grammar->id);
    }
}
