<?php

namespace Tests\Feature;

use App\Models\LabCategory;
use App\Models\LabExperiment;
use Database\Seeders\LabBadgeSeeder;
use Database\Seeders\LabCategorySeeder;
use Database\Seeders\LabExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabExperimentSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Simulation types registered in resources/js/Components/Lab/simulationRegistry.js. */
    private function registryTypes(): array
    {
        $source = file_get_contents(resource_path('js/Components/Lab/simulationRegistry.js'));
        preg_match_all("/^\\s*'([a-z_]+)':\\s*[A-Za-z]+Simulation,/m", $source, $m);

        return $m[1];
    }

    private function seedLab(): void
    {
        $this->seed(LabCategorySeeder::class);
        $this->seed(LabBadgeSeeder::class);
        $this->seed(LabExperimentSeeder::class);
    }

    public function test_every_seeded_experiment_has_real_content_and_a_registered_simulation(): void
    {
        $this->seedLab();

        $types = $this->registryTypes();
        $this->assertCount(59, $types, 'simulationRegistry.js should map 59 simulation types');

        $experiments = LabExperiment::with('category')->get();
        $this->assertCount(59, $experiments);

        foreach ($experiments as $e) {
            $label = $e->slug;

            $this->assertContains($e->simulation_type, $types, "{$label}: simulation_type not in simulationRegistry.js");
            $this->assertSame('active', $e->status, $label);

            // Theory (real text, not the generic placeholder) and objectives
            $theory = $e->theory_introduction['uz'] ?? '';
            $this->assertGreaterThan(300, mb_strlen($theory), "{$label}: theory too short");
            $this->assertStringNotContainsString('haqida nazariy va amaliy bilim olasiz', $theory, "{$label}: placeholder theory");
            $this->assertNotEmpty($e->objectives['uz'] ?? [], "{$label}: objectives");

            // Formulas in the shape FormulaSheet.vue / MeasurementPanel.vue consume
            $this->assertNotEmpty($e->formulas, "{$label}: formulas");
            foreach ($e->formulas as $f) {
                foreach (['id', 'name', 'latex', 'description'] as $key) {
                    $this->assertIsString($f[$key] ?? null, "{$label}: formula {$key}");
                    $this->assertNotSame('', $f[$key], "{$label}: formula {$key}");
                }
                $this->assertNotEmpty($f['variables'], "{$label}: formula {$f['id']} variables");
                foreach ($f['variables'] as $v) {
                    $this->assertArrayHasKey('symbol', $v);
                    $this->assertArrayHasKey('name', $v);
                    $this->assertArrayHasKey('unit', $v);
                }
            }

            // Tasks: numbered steps, points add up to total_points
            $tasks = $e->tasks;
            $this->assertNotEmpty($tasks, "{$label}: tasks");
            $this->assertSame(range(1, count($tasks)), array_column($tasks, 'step'), "{$label}: steps");
            $this->assertSame($e->total_points, array_sum(array_column($tasks, 'max_score')), "{$label}: points");
            $withAnswer = 0;
            foreach ($tasks as $t) {
                $this->assertNotEmpty($t['title']);
                $this->assertNotEmpty($t['description']);
                $this->assertContains($t['type'], ['instruction', 'measurement', 'calculation', 'conclusion', 'graph_analysis', 'circuit_build']);
                if (isset($t['expected'])) {
                    $withAnswer++;
                    $this->assertContains($t['expected']['source'], ['measurement', 'calculation']);
                    $this->assertIsNumeric($t['expected']['value']);
                    $this->assertGreaterThan(0, $t['expected']['tolerance_percent']);
                    $this->assertContains($t['expected']['check']['formula_id'], array_column($e->formulas, 'id'), "{$label}: check formula offered");
                }
            }
            $this->assertGreaterThan(0, $withAnswer, "{$label}: at least one task with a computed answer");

            // Full simulation config with standard gravity
            $this->assertSame(9.81, (float) ($e->simulation_config['physics']['gravity'] ?? 0), "{$label}: gravity");
            $this->assertArrayHasKey('canvas', $e->simulation_config, $label);
        }

        // Every registered simulation is reachable (incl. generator and xray_diffraction)
        $this->assertEqualsCanonicalizing($types, $experiments->pluck('simulation_type')->unique()->values()->all());
    }

    public function test_new_experiments_text_fixes_and_ordering(): void
    {
        $this->seedLab();

        $generator = LabExperiment::where('slug', 'electric-generator')->firstOrFail();
        $this->assertSame('generator', $generator->simulation_type);
        $this->assertSame('magnetism', $generator->category->slug);
        $this->assertSame(7, $generator->experiment_number);

        $xray = LabExperiment::where('slug', 'xray-diffraction')->firstOrFail();
        $this->assertSame('xray_diffraction', $xray->simulation_type);
        $this->assertSame('atomic', $xray->category->slug);
        $this->assertSame(7, $xray->experiment_number);

        // experiment numbers are 1..n inside each category
        foreach (LabCategory::with('experiments')->get() as $category) {
            $numbers = $category->experiments->pluck('experiment_number')->sort()->values()->all();
            $this->assertSame(range(1, count($numbers)), $numbers, $category->slug);
        }

        $this->assertSame('Gorizontga burchak ostida otilgan jism', LabExperiment::where('slug', 'projectile-motion')->value('title_uz'));
        $this->assertSame('Lents qonuni', LabExperiment::where('slug', 'lenz-law')->value('title_uz'));
        $this->assertStringContainsString('tor tirqishdan', LabExperiment::where('slug', 'light-diffraction')->value('short_description_uz'));
        $this->assertStringContainsString('Termodinamikaning', LabExperiment::where('slug', 'entropy')->value('short_description_uz'));
        $this->assertStringContainsString('ustma-ust tushishida', LabExperiment::where('slug', 'beats-phenomenon')->value('short_description_uz'));
        $this->assertStringContainsString('Yung', LabExperiment::where('slug', 'wave-particle-duality')->value('short_description_uz'));

        // QA2-S-13: the resistance task names the measurement it means (6 V, 60 mA -> 100 Ω)
        $ohm = collect(LabExperiment::where('slug', 'ohm-law')->firstOrFail()->tasks)->last();
        $this->assertStringContainsString("2-vazifadagi o'lchov (U = 6 V, I = 60 mA", $ohm['description']);
        $this->assertEquals(100, $ohm['expected']['value']);

        // Seeding twice keeps one row per slug (updateOrCreate)
        $this->seed(LabExperimentSeeder::class);
        $this->assertSame(59, LabExperiment::count());
    }
}
