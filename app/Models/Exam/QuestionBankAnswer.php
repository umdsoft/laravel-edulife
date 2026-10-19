<?php

namespace App\Models\Exam;

use App\Enums\Exam\ToleranceType;
use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Expected answer of an open question (numeric / numeric_with_unit).
 *
 * value          exact decimal in the canonical unit (NULL until the teacher sets it)
 * tolerance_type abs | rel, tolerance its size (0 = exact)
 * unit           canonical unit, e.g. "m/s" (numeric_with_unit only)
 * accepted_units {"km/soat": 0.2777777778, ...}: factor converting ONE
 *                accepted unit into the canonical unit (canonical = 1)
 *
 * Parsing of student input ("1/2", "0,5") and partial credit for a wrong
 * unit belong to the stage 5 graders; this model only offers the
 * comparison primitives they build on.
 */
class QuestionBankAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'question_bank_answers';

    protected $fillable = [
        'question_id',
        'value',
        'tolerance_type',
        'tolerance',
        'unit',
        'accepted_units',
        'note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:10',
            'tolerance' => 'decimal:10',
            'tolerance_type' => ToleranceType::class,
            'accepted_units' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    /**
     * Factor converting $unit into the canonical unit, NULL when the unit is
     * not accepted. Units compare case-sensitively after trimming (mA != MA).
     */
    public function unitFactor(?string $unit): ?float
    {
        $unit = $unit === null ? null : trim($unit);
        if ($this->unit === null) {
            return ($unit === null || $unit === '') ? 1.0 : null;
        }
        if ($unit === $this->unit) {
            return 1.0;
        }
        $factor = ($this->accepted_units ?? [])[$unit] ?? null;

        return is_numeric($factor) && (float) $factor > 0 ? (float) $factor : null;
    }

    /** Whether $given (expressed in $unit) equals the expected value within tolerance. */
    public function accepts(float $given, ?string $unit = null): bool
    {
        if ($this->value === null) {
            return false;
        }
        $factor = $this->unitFactor($unit ?? $this->unit);
        if ($factor === null) {
            return false;
        }

        return ($this->tolerance_type ?? ToleranceType::Absolute)->accepts(
            (float) $this->value,
            $given * $factor,
            (float) $this->tolerance
        );
    }
}
