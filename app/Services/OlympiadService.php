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
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        
        $olympiad = Olympiad::create($data);

        // Create default sections based on olympiad type
        if (isset($data['create_default_sections']) && $data['create_default_sections']) {
            $this->createDefaultSections($olympiad);
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
            throw new \Exception('Cannot update olympiad in current status');
        }

        $olympiad->update($data);
        return $olympiad->fresh(['olympiadType', 'stage', 'sections']);
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

        $order = 1;
        foreach ($sections as $sectionType) {
            OlympiadSection::create([
                'olympiad_id' => $olympiad->id,
                'section_type' => $sectionType,
                'title' => $this->getSectionTitle($sectionType),
                'title_uz' => $this->getSectionTitleUz($sectionType),
                'duration_minutes' => $this->getDefaultDuration($sectionType),
                'order_number' => $order++,
                'max_points' => 100,
                'weight_percent' => $weights[$sectionType] ?? (100 / count($sections)),
                'requires_manual_grading' => in_array($sectionType, ['writing', 'speaking']),
            ]);
        }
    }

    /**
     * Get section title in English
     */
    private function getSectionTitle(string $sectionType): string
    {
        $titles = [
            'test' => 'Multiple Choice Test',
            'listening' => 'Listening Comprehension',
            'reading' => 'Reading Comprehension',
            'writing' => 'Writing Section',
            'speaking' => 'Speaking Section',
            'coding' => 'Programming Problems',
            'math' => 'Mathematics Problems',
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
            'listening' => 'Tinglash bo\'limi',
            'reading' => 'O\'qish bo\'limi',
            'writing' => 'Yozish bo\'limi',
            'speaking' => 'Gapirish bo\'limi',
            'coding' => 'Dasturlash masalalari',
            'math' => 'Matematika masalalari',
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
            'listening' => 25,
            'reading' => 40,
            'writing' => 45,
            'speaking' => 15,
            'coding' => 90,
            'math' => 60,
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
        $newData = array_merge($olympiad->toArray(), $overrideData, [
            'id' => null,
            'slug' => Str::slug($overrideData['title'] ?? $olympiad->title) . '-' . Str::random(6),
            'status' => Olympiad::STATUS_DRAFT,
            'created_at' => null,
            'updated_at' => null,
        ]);

        unset($newData['id']);
        
        $newOlympiad = Olympiad::create($newData);

        // Duplicate sections
        foreach ($olympiad->sections as $section) {
            $sectionData = $section->toArray();
            unset($sectionData['id'], $sectionData['olympiad_id']);
            $newOlympiad->sections()->create($sectionData);
        }

        return $newOlympiad->load('sections');
    }
}
