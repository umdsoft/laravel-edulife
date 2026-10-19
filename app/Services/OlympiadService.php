<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadSection;
use App\Models\OlympiadSeries;
use App\Models\OlympiadType;
use App\Models\OlympiadStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class OlympiadService
{
    /**
     * Get all active olympiad types
     */
    public function getOlympiadTypes(): Collection
    {
        return OlympiadType::active()->with('direction')->get();
    }

    /**
     * Get all olympiad stages ordered
     */
    public function getOlympiadStages(): Collection
    {
        return OlympiadStage::orderBy('order_level')->get();
    }

    /**
     * Get upcoming olympiads for students
     */
    public function getUpcomingForStudents(?string $typeId = null): Collection
    {
        $query = Olympiad::with(['olympiadType', 'stage', 'series'])
            ->where('visibility', Olympiad::VISIBILITY_PUBLIC)
            ->whereIn('status', [
                Olympiad::STATUS_UPCOMING,
                Olympiad::STATUS_REGISTRATION_OPEN,
            ])
            ->orderBy('registration_start_at');

        if ($typeId) {
            $query->where('olympiad_type_id', $typeId);
        }

        return $query->get();
    }

    /**
     * Get currently live olympiads
     */
    public function getLiveOlympiads(): Collection
    {
        return Olympiad::with(['olympiadType', 'stage'])
            ->where('status', Olympiad::STATUS_LIVE)
            ->orderBy('olympiad_start_at')
            ->get();
    }

    /**
     * Get olympiad with full details
     */
    public function getOlympiadDetails(string $olympiadId): ?Olympiad
    {
        return Olympiad::with([
            'olympiadType',
            'stage',
            'series',
            'sections' => fn($q) => $q->ordered(),
            'region',
            'creator',
        ])->find($olympiadId);
    }

    /**
     * Create a new olympiad
     */
    public function create(array $data): Olympiad
    {
        $withSections = (bool) ($data['create_default_sections'] ?? false);
        $attributes = $this->toAttributes($data);
        $attributes['slug'] = Str::slug($data['title']).'-'.Str::random(6);
        // NOT NULL columns the form does not send; totals follow the sections below
        $attributes += [
            'sections_config' => [],
            'total_duration_minutes' => $this->windowMinutes($data),
            'total_max_points' => 0,
        ];

        $olympiad = Olympiad::create($attributes);

        if ($withSections) {
            $this->createDefaultSections($olympiad);
            $this->syncTotals($olympiad);
        }

        return $olympiad->load(['olympiadType', 'stage', 'sections']);
    }

    /**
     * Update olympiad
     */
    public function update(Olympiad $olympiad, array $data): Olympiad
    {
        // Only allow updates if not live or completed
        if (in_array($olympiad->status, [Olympiad::STATUS_LIVE, Olympiad::STATUS_COMPLETED])) {
            throw new \Exception("Jonli yoki yakunlangan olimpiadani tahrirlab bo'lmaydi");
        }

        $olympiad->update($this->toAttributes($data));

        return $olympiad->fresh(['olympiadType', 'stage', 'sections']);
    }

    /**
     * Form fields → olympiads columns (the form calls the fee registration_fee,
     * the table entry_fee; the demo switch lives inside demo_config).
     */
    private function toAttributes(array $data): array
    {
        if (array_key_exists('registration_fee', $data)) {
            $data['entry_fee'] = (float) ($data['registration_fee'] ?? 0);
        }
        if (isset($data['demo_config']) && is_array($data['demo_config'])) {
            $data['demo_enabled'] = (bool) ($data['demo_config']['enabled'] ?? false);
        }
        unset($data['registration_fee'], $data['create_default_sections']);

        return array_intersect_key($data, array_flip((new Olympiad)->getFillable()));
    }

    private function windowMinutes(array $data): int
    {
        try {
            $minutes = \Illuminate\Support\Carbon::parse($data['olympiad_start_at'])
                ->diffInMinutes(\Illuminate\Support\Carbon::parse($data['olympiad_end_at']));
        } catch (\Throwable) {
            $minutes = 0;
        }

        return max(1, (int) $minutes ?: 60);
    }

    /**
     * total_duration_minutes / total_max_points follow the sections.
     */
    public function syncTotals(Olympiad $olympiad): void
    {
        $sections = $olympiad->sections()->get();
        if ($sections->isEmpty()) {
            return;
        }

        $olympiad->update([
            'total_duration_minutes' => (int) $sections->sum('duration_minutes'),
            'total_max_points' => (int) $sections->sum('max_points'),
        ]);
    }

    /**
     * Update olympiad status
     */
    public function updateStatus(Olympiad $olympiad, string $newStatus): Olympiad
    {
        $allowedTransitions = [
            Olympiad::STATUS_DRAFT => [Olympiad::STATUS_UPCOMING],
            Olympiad::STATUS_UPCOMING => [Olympiad::STATUS_REGISTRATION_OPEN, Olympiad::STATUS_DRAFT],
            Olympiad::STATUS_REGISTRATION_OPEN => [Olympiad::STATUS_LIVE, Olympiad::STATUS_UPCOMING],
            Olympiad::STATUS_LIVE => [Olympiad::STATUS_GRADING],
            Olympiad::STATUS_GRADING => [Olympiad::STATUS_COMPLETED],
        ];

        $allowed = $allowedTransitions[$olympiad->status] ?? [];
        
        if (!in_array($newStatus, $allowed)) {
            throw new \Exception("Cannot transition from {$olympiad->status} to {$newStatus}");
        }

        $olympiad->status = $newStatus;
        $olympiad->save();

        return $olympiad;
    }

    /**
     * Create default sections for olympiad based on type
     */
    public function createDefaultSections(Olympiad $olympiad): void
    {
        $type = $olympiad->olympiadType;
        $sections = $type->sections ?? [];
        $weights = $type->section_weights ?? [];
        $defaults = $type->getDefaultSectionsConfig();

        $order = 1;
        foreach ($sections as $sectionType) {
            OlympiadSection::create([
                'olympiad_id' => $olympiad->id,
                'section_type' => $sectionType,
                // olympiad_sections has a single title column; the UI is Uzbek
                'title' => $this->getSectionTitleUz($sectionType),
                'duration_minutes' => $defaults[$sectionType]['duration_minutes'] ?? $this->getDefaultDuration($sectionType),
                'order_number' => $order++,
                'max_points' => 100,
                'weight_percent' => $weights[$sectionType] ?? (100 / count($sections)),
                // Test sections are auto-graded; open-ended questions are flagged per question
                'requires_manual_grading' => false,
            ]);
        }
    }

    /**
     * Get section title (display labels)
     */
    private function getSectionTitle(string $sectionType): string
    {
        $titles = [
            'test' => 'Multiple Choice Test',
        ];
        return $titles[$sectionType] ?? ucfirst($sectionType);
    }

    /**
     * Get section title in Uzbek
     */
    private function getSectionTitleUz(string $sectionType): string
    {
        $titles = [
            'test' => 'Test savollari',
            'open_ended' => 'Ochiq savollar',
            'practical' => 'Amaliy topshiriqlar',
            'essay' => 'Insho',
        ];
        return $titles[$sectionType] ?? ucfirst($sectionType);
    }

    /**
     * Get default duration for section type
     */
    private function getDefaultDuration(string $sectionType): int
    {
        $durations = [
            'test' => 30,
        ];
        return $durations[$sectionType] ?? 30;
    }

    /**
     * Calculate total duration for olympiad
     */
    public function calculateTotalDuration(Olympiad $olympiad): int
    {
        return $olympiad->sections->sum('duration_minutes');
    }

    /**
     * Get olympiad statistics
     */
    public function getStatistics(Olympiad $olympiad): array
    {
        return [
            'total_registrations' => $olympiad->registrations()->count(),
            'confirmed_registrations' => $olympiad->registrations()->confirmed()->count(),
            'completed_attempts' => $olympiad->attempts()->completed()->count(),
            'average_score' => $olympiad->attempts()
                ->completed()
                ->notDisqualified()
                ->avg('score_percent'),
            'top_score' => $olympiad->attempts()
                ->completed()
                ->notDisqualified()
                ->max('total_weighted_score'),
            'disqualified_count' => $olympiad->attempts()->where('is_disqualified', true)->count(),
        ];
    }

    /**
     * Duplicate olympiad for new event
     */
    public function duplicate(Olympiad $olympiad, array $overrideData = []): Olympiad
    {
        $copy = $olympiad->replicate(['slug', 'status']);
        $copy->fill(array_intersect_key($overrideData, array_flip($copy->getFillable())));
        $copy->slug = Str::slug($overrideData['title'] ?? $olympiad->title).'-'.Str::random(6);
        $copy->status = Olympiad::STATUS_DRAFT;
        $copy->save();

        foreach ($olympiad->sections()->get() as $section) {
            $newSection = $section->replicate();
            $newSection->olympiad_id = $copy->id;
            $newSection->save();
        }

        return $copy->load('sections');
    }
}
