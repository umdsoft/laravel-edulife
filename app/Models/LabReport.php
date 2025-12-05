<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attempt_id',
        'user_id',
        'experiment_id',
        'title',
        'introduction',
        'objectives_text',
        'theory_summary',
        'formulas_used',
        'equipment_list',
        'procedure_steps',
        'observations',
        'data_tables',
        'graphs',
        'calculations_detail',
        'results_summary',
        'error_analysis',
        'conclusion',
        'questions_answers',
        'status',
        'submitted_at',
        'submission_notes',
        'is_graded',
        'graded_by',
        'graded_at',
        'grading_rubric',
        'teacher_grade',
        'teacher_feedback',
        'feedback_audio_url',
        'pdf_generated',
        'pdf_path',
        'pdf_generated_at',
        'pdf_version',
        'is_premium_export',
    ];

    protected $casts = [
        'formulas_used' => 'array',
        'equipment_list' => 'array',
        'procedure_steps' => 'array',
        'observations' => 'array',
        'data_tables' => 'array',
        'graphs' => 'array',
        'results_summary' => 'array',
        'questions_answers' => 'array',
        'grading_rubric' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'is_graded' => 'boolean',
        'pdf_generated' => 'boolean',
        'is_premium_export' => 'boolean',
        'teacher_grade' => 'integer',
        'pdf_version' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_graded' => false,
        'pdf_generated' => false,
        'is_premium_export' => false,
        'pdf_version' => 1,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const STATUSES = [
        'draft' => ['label' => 'Qoralama', 'color' => '#9CA3AF'],
        'submitted' => ['label' => 'Topshirilgan', 'color' => '#3B82F6'],
        'graded' => ['label' => 'Baholangan', 'color' => '#10B981'],
        'returned' => ['label' => 'Qaytarilgan', 'color' => '#F59E0B'],
        'approved' => ['label' => 'Tasdiqlangan', 'color' => '#059669'],
    ];

    public const GRADING_CRITERIA = [
        'introduction' => ['name' => 'Kirish va maqsad', 'max_points' => 10],
        'theory' => ['name' => 'Nazariy asos', 'max_points' => 10],
        'procedure' => ['name' => 'Ish tartibi', 'max_points' => 10],
        'measurements' => ['name' => "O'lchov aniqligi", 'max_points' => 20],
        'calculations' => ['name' => 'Hisob-kitoblar', 'max_points' => 15],
        'graphs' => ['name' => 'Grafiklar', 'max_points' => 10],
        'conclusion' => ['name' => 'Xulosa', 'max_points' => 15],
        'formatting' => ['name' => 'Rasmiylashtirish', 'max_points' => 10],
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LabAttempt::class, 'attempt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(LabExperiment::class, 'experiment_id');
    }

    public function gradedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeGraded($query)
    {
        return $query->where('is_graded', true);
    }

    public function scopeNeedsGrading($query)
    {
        return $query->where('status', 'submitted')->where('is_graded', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['draft'];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_info['label'];
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    public function getTotalGradeAttribute(): int
    {
        $rubric = $this->grading_rubric ?? [];
        $criteria = $rubric['criteria'] ?? [];
        
        return collect($criteria)->sum('points');
    }

    public function getMaxGradeAttribute(): int
    {
        $rubric = $this->grading_rubric ?? [];
        $criteria = $rubric['criteria'] ?? [];
        
        return collect($criteria)->sum('max_points');
    }

    public function getGradePercentageAttribute(): float
    {
        if ($this->max_grade === 0) return 0;
        return round(($this->total_grade / $this->max_grade) * 100, 1);
    }

    public function getDataTablesCountAttribute(): int
    {
        return count($this->data_tables ?? []);
    }

    public function getGraphsCountAttribute(): int
    {
        return count($this->graphs ?? []);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Submit the report
     */
    public function submit(?string $notes = null): self
    {
        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->submission_notes = $notes;
        $this->save();
        
        return $this;
    }

    /**
     * Grade the report
     */
    public function grade(User $teacher, array $rubric, ?string $feedback = null): self
    {
        $this->is_graded = true;
        $this->graded_by = $teacher->id;
        $this->graded_at = now();
        $this->grading_rubric = $rubric;
        $this->teacher_feedback = $feedback;
        $this->teacher_grade = $rubric['total_points'] ?? $this->total_grade;
        $this->status = 'graded';
        $this->save();
        
        return $this;
    }

    /**
     * Return for revision
     */
    public function returnForRevision(string $feedback): self
    {
        $this->status = 'returned';
        $this->teacher_feedback = $feedback;
        $this->save();
        
        return $this;
    }

    /**
     * Add data table
     */
    public function addDataTable(array $table): self
    {
        $tables = $this->data_tables ?? [];
        $tables[] = $table;
        $this->data_tables = $tables;
        $this->save();
        
        return $this;
    }

    /**
     * Add graph
     */
    public function addGraph(array $graph): self
    {
        $graphs = $this->graphs ?? [];
        $graphs[] = $graph;
        $this->graphs = $graphs;
        $this->save();
        
        return $this;
    }

    /**
     * Generate PDF report
     */
    public function generatePdf(bool $isPremium = false): string
    {
        // This will be implemented with PDF service
        $path = "reports/{$this->user_id}/{$this->id}_v{$this->pdf_version}.pdf";
        
        $this->pdf_generated = true;
        $this->pdf_path = $path;
        $this->pdf_generated_at = now();
        $this->pdf_version += 1;
        $this->is_premium_export = $isPremium;
        $this->save();
        
        return $path;
    }

    /**
     * Initialize grading rubric with default criteria
     */
    public function initializeGradingRubric(): self
    {
        $criteria = [];
        
        foreach (self::GRADING_CRITERIA as $key => $info) {
            $criteria[] = [
                'key' => $key,
                'name' => $info['name'],
                'max_points' => $info['max_points'],
                'points' => 0,
                'feedback' => null,
            ];
        }
        
        $this->grading_rubric = [
            'criteria' => $criteria,
            'total_points' => 0,
            'max_points' => 100,
        ];
        
        $this->save();
        
        return $this;
    }

    /**
     * Create report from attempt data
     */
    public static function createFromAttempt(LabAttempt $attempt): self
    {
        $experiment = $attempt->experiment;
        $reportData = $attempt->toReportData();
        
        $report = self::create([
            'attempt_id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'experiment_id' => $attempt->experiment_id,
            'title' => "Lab: {$experiment->localized_title}",
            'objectives_text' => implode("\n", $experiment->localized_objectives),
            'formulas_used' => $experiment->formulas,
            'equipment_list' => $experiment->required_equipment,
            'data_tables' => $this->generateDataTablesFromMeasurements($reportData['measurements']),
            'results_summary' => [
                'measured_values' => $reportData['calculations'],
                'score' => $reportData['score'],
            ],
        ]);
        
        $report->initializeGradingRubric();
        
        return $report;
    }

    /**
     * Generate data tables from measurements
     */
    protected static function generateDataTablesFromMeasurements(array $measurements): array
    {
        if (empty($measurements)) {
            return [];
        }
        
        $tables = [];
        $grouped = collect($measurements)->groupBy('name');
        
        foreach ($grouped as $name => $items) {
            $rows = [];
            $columns = ['#', 'Qiymat', 'Birlik', 'Vaqt'];
            
            foreach ($items->values() as $i => $m) {
                $rows[] = [
                    'n' => $i + 1,
                    'value' => $m['value'] ?? '',
                    'unit' => $m['unit'] ?? '',
                    'timestamp' => $m['timestamp'] ?? '',
                ];
            }
            
            $tables[] = [
                'title' => ucfirst($name) . " o'lchovlari",
                'columns' => $columns,
                'rows' => $rows,
            ];
        }
        
        return $tables;
    }

    /**
     * Export for PDF generation
     */
    public function toExportData(): array
    {
        return [
            'title' => $this->title,
            'experiment' => [
                'name' => $this->experiment->localized_title,
                'category' => $this->experiment->category->localized_name,
            ],
            'student' => [
                'name' => $this->user->name,
                'class' => $this->user->student_profile?->class ?? '',
                'school' => $this->user->student_profile?->school ?? '',
            ],
            'date' => $this->submitted_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'sections' => [
                'introduction' => $this->introduction,
                'objectives' => $this->objectives_text,
                'theory' => $this->theory_summary,
                'formulas' => $this->formulas_used,
                'equipment' => $this->equipment_list,
                'procedure' => $this->procedure_steps,
                'observations' => $this->observations,
                'data_tables' => $this->data_tables,
                'graphs' => $this->graphs,
                'calculations' => $this->calculations_detail,
                'results' => $this->results_summary,
                'error_analysis' => $this->error_analysis,
                'conclusion' => $this->conclusion,
                'questions' => $this->questions_answers,
            ],
            'grading' => $this->is_graded ? [
                'grade' => $this->teacher_grade,
                'rubric' => $this->grading_rubric,
                'feedback' => $this->teacher_feedback,
            ] : null,
            'attempt' => [
                'score' => $this->attempt->percentage ?? 0,
                'duration' => $this->attempt->time_spent_text ?? '',
                'date' => $this->attempt->started_at?->format('d.m.Y') ?? '',
            ],
        ];
    }
}
