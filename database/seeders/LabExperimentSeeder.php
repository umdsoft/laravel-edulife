<?php

namespace Database\Seeders;

use App\Models\LabCategory;
use App\Models\LabExperiment;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Virtual fizika laboratoriyasi tajribalari.
 *
 * Tajribalar mazmuni (nazariya, maqsadlar, formulalar, simulyatsiya sozlamalari,
 * vazifalar) database/seeders/data/lab/ dagi JSON fayllarda saqlanadi:
 *   - formulas.json            — formulalar katalogi (id → name/latex/description/variables).
 *                                Har bir id uchun MeasurementPanel kalkulyatorida hisoblagich bor
 *                                (resources/js/Components/Lab/physics/calculator.js).
 *   - experiments/<kategoriya>.json — kategoriya tajribalari.
 *
 * Vazifalardagi "expected" bloklari simulyatsiyalarning standart sozlamalarida
 * fizika modullari (resources/js/Components/Lab/physics/*.js) bilan hisoblangan;
 * har bir qiymat "check" (formula_id + inputs) bo'yicha vitest testida qayta
 * hisoblanib tekshiriladi (physics/__tests__/calculator.test.js).
 */
class LabExperimentSeeder extends Seeder
{
    /** Kategoriya slug → JSON fayl (database/seeders/data/lab/experiments/). */
    private const CATEGORY_FILES = [
        'mechanics' => 'mechanics.json',
        'thermodynamics' => 'thermodynamics.json',
        'electricity' => 'electricity.json',
        'optics' => 'optics.json',
        'waves' => 'waves.json',
        'magnetism' => 'magnetism.json',
        'atomic' => 'atomic.json',
    ];

    private const TASK_TYPES = ['instruction', 'measurement', 'calculation', 'conclusion', 'graph_analysis', 'circuit_build'];

    /** @var array<string, array> formula id → formula */
    private array $formulaCatalog = [];

    public function run(): void
    {
        $categories = LabCategory::all()->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command?->error('Avval LabCategorySeeder ni ishga tushiring!');
            return;
        }

        $count = 0;
        foreach ($this->allExperiments($categories->map(fn ($c) => $c->id)->all()) as $data) {
            if (!$data['category_id']) {
                continue;
            }

            LabExperiment::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
            $count++;
        }

        $this->command?->info("✅ {$count} ta tajriba yaratildi!");
    }

    /**
     * All experiment rows ready for LabExperiment::updateOrCreate().
     *
     * @param array<string, string|int|null> $categoryIds category slug → id
     * @return array<int, array>
     */
    public function allExperiments(array $categoryIds): array
    {
        $rows = [];
        foreach (self::CATEGORY_FILES as $categorySlug => $file) {
            foreach ($this->readJson('experiments/' . $file) as $spec) {
                $rows[] = $this->buildExperiment($categoryIds[$categorySlug] ?? null, $spec);
            }
        }

        return $rows;
    }

    /** Directory holding the lab seed data. */
    public static function dataPath(string $file = ''): string
    {
        return database_path('seeders/data/lab' . ($file !== '' ? '/' . $file : ''));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // YORDAMCHI METODLAR
    // ═══════════════════════════════════════════════════════════════════════

    private function buildExperiment($categoryId, array $spec): array
    {
        foreach (['slug', 'number', 'title_uz', 'grade', 'difficulty', 'is_free', 'duration', 'total_points', 'simulation_type', 'theory_uz', 'objectives_uz', 'formulas', 'tasks'] as $key) {
            if (!array_key_exists($key, $spec)) {
                throw new RuntimeException("Lab tajribasi ({$spec['slug']}): '{$key}' maydoni yo'q.");
            }
        }

        $slug = $spec['slug'];
        $totalPoints = (int) $spec['total_points'];

        return $this->createExperiment(
            $categoryId,
            $slug,
            (int) $spec['number'],
            $spec['title_uz'],
            (int) $spec['grade'],
            $spec['difficulty'],
            (bool) $spec['is_free'],
            (int) $spec['duration'],
            $totalPoints,
            [
                'short_description_uz' => $spec['short_description_uz'] ?? null,
                'description_uz' => $spec['description_uz'] ?? null,
                'simulation_type' => $spec['simulation_type'],
                'objectives' => ['uz' => array_values($spec['objectives_uz'])],
                'theory' => ['uz' => $spec['theory_uz']],
                'formulas' => $this->resolveFormulas($slug, $spec['formulas']),
                'simulation_config' => $spec['simulation_config'] ?? $this->getDefaultSimConfig(),
                'tasks' => $this->buildTasks($slug, $spec['tasks'], $totalPoints),
                'xp_reward' => $spec['xp_reward'] ?? $totalPoints,
                'coin_reward' => $spec['coin_reward'] ?? 10,
            ]
        );
    }

    private function createExperiment(
        $categoryId,
        string $slug,
        int $number,
        string $titleUz,
        int $grade,
        string $difficulty,
        bool $isFree,
        int $duration,
        int $totalPoints,
        array $extra = []
    ): array {
        $difficultyScores = ['easy' => 2, 'medium' => 5, 'hard' => 8];

        return [
            'category_id' => $categoryId,
            'slug' => $slug,
            'experiment_number' => $number,
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'title_uz' => $titleUz,
            'title_ru' => $titleUz, // Placeholder
            'short_description_uz' => $extra['short_description_uz'] ?? "Bu tajribada {$titleUz} mavzusini o'rganasiz.",
            'description_uz' => $extra['description_uz'] ?? ($extra['short_description_uz'] ?? "Bu tajribada siz {$titleUz} mavzusini chuqur o'rganasiz va amaliy tajriba o'tkazasiz."),
            'grade_level' => $grade,
            'difficulty' => $difficulty,
            'difficulty_score' => $difficultyScores[$difficulty],
            'estimated_duration' => $duration,
            'is_free' => $isFree,
            'is_premium' => !$isFree,
            'required_subscription' => $isFree ? 'free' : 'premium',
            'objectives' => $extra['objectives'],
            'theory_introduction' => $extra['theory'],
            'formulas' => $extra['formulas'],
            'required_equipment' => $extra['equipment'] ?? [],
            'simulation_type' => $extra['simulation_type'],
            'simulation_config' => $extra['simulation_config'],
            'tasks' => $extra['tasks'],
            'total_points' => $totalPoints,
            'passing_points' => (int) ($totalPoints * 0.6),
            'xp_reward' => $extra['xp_reward'],
            'xp_reward_premium' => $extra['xp_reward'] * 2,
            'coin_reward' => $extra['coin_reward'],
            'coin_reward_premium' => $extra['coin_reward'] * 2,
            'status' => 'active',
            'is_featured' => $isFree,
        ];
    }

    /**
     * Full simulation config used when an experiment does not define its own.
     * A custom simulation_config REPLACES this array (no merge), so experiments
     * that override it always provide the complete structure.
     */
    private function getDefaultSimConfig(): array
    {
        return [
            'canvas' => ['width' => 800, 'height' => 600, 'background' => '#1a1a2e'],
            'physics' => ['gravity' => 9.81],
            'measurements' => ['showValues' => true],
        ];
    }

    /**
     * Formula ids → full formula objects in the shape FormulaSheet.vue and
     * MeasurementPanel.vue consume: {id, name, latex, description, variables:[{symbol,name,unit}]}.
     */
    private function resolveFormulas(string $slug, array $ids): array
    {
        if ($this->formulaCatalog === []) {
            $this->formulaCatalog = $this->readJson('formulas.json');
        }

        return array_map(function ($id) use ($slug) {
            if (!isset($this->formulaCatalog[$id])) {
                throw new RuntimeException("Lab tajribasi ({$slug}): noma'lum formula id '{$id}'.");
            }
            $f = $this->formulaCatalog[$id];

            return [
                'id' => $id,
                'name' => $f['name'],
                'latex' => $f['latex'],
                'description' => $f['description'],
                'variables' => $f['variables'],
            ];
        }, $ids);
    }

    /**
     * Number the tasks (step 1…n) and split total_points between them
     * (equal shares, the remainder goes to the last task).
     */
    private function buildTasks(string $slug, array $tasks, int $totalPoints): array
    {
        $n = count($tasks);
        if ($n === 0) {
            throw new RuntimeException("Lab tajribasi ({$slug}): vazifalar yo'q.");
        }

        $share = intdiv($totalPoints, $n);
        $remainder = $totalPoints - $share * $n;

        $out = [];
        foreach (array_values($tasks) as $i => $task) {
            if (!in_array($task['type'] ?? null, self::TASK_TYPES, true)) {
                throw new RuntimeException("Lab tajribasi ({$slug}): vazifa turi noto'g'ri: " . ($task['type'] ?? 'null'));
            }

            $row = [
                'step' => $i + 1,
                'title' => $task['title'],
                'description' => $task['description'],
                'type' => $task['type'],
                'max_score' => $share + ($i === $n - 1 ? $remainder : 0),
            ];
            if (!empty($task['hints'])) {
                $row['hints'] = array_values($task['hints']);
            }
            if (!empty($task['expected'])) {
                $row['expected'] = $task['expected'];
            }
            $out[] = $row;
        }

        return $out;
    }

    private function readJson(string $file): array
    {
        $path = self::dataPath($file);
        if (!is_file($path)) {
            throw new RuntimeException("Lab ma'lumotlari topilmadi: {$path}");
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException("Lab ma'lumotlari noto'g'ri formatda: {$path}");
        }

        return $data;
    }
}
