<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CodingProblem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // Difficulty constants
    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';
    public const DIFFICULTY_EXPERT = 'expert';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    // Language constants
    public const LANG_PYTHON = 'python';
    public const LANG_JAVASCRIPT = 'javascript';
    public const LANG_CPP = 'cpp';
    public const LANG_JAVA = 'java';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'input_format',
        'output_format',
        'constraints',
        'examples',
        'test_cases',
        'difficulty',
        'max_points',
        'partial_scoring',
        'time_limit_ms',
        'memory_limit_mb',
        'allowed_languages',
        'starter_code',
        'editorial',
        'editorial_code',
        'tags',
        'algorithms',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'examples' => 'array',
            'test_cases' => 'array',
            'allowed_languages' => 'array',
            'starter_code' => 'array',
            'editorial_code' => 'array',
            'tags' => 'array',
            'algorithms' => 'array',
            'max_points' => 'integer',
            'partial_scoring' => 'boolean',
            'time_limit_ms' => 'integer',
            'memory_limit_mb' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function olympiadProblems(): HasMany
    {
        return $this->hasMany(OlympiadCodingProblem::class, 'problem_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CodingSubmission::class, 'problem_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeByAlgorithm($query, string $algorithm)
    {
        return $query->whereJsonContains('algorithms', $algorithm);
    }

    // ==================== ACCESSORS ====================

    public function getSampleTestCasesAttribute(): array
    {
        return collect($this->test_cases)
            ->filter(fn($tc) => $tc['is_sample'] ?? false)
            ->values()
            ->all();
    }

    public function getHiddenTestCasesAttribute(): array
    {
        return collect($this->test_cases)
            ->filter(fn($tc) => !($tc['is_sample'] ?? false))
            ->values()
            ->all();
    }

    public function getTotalTestCasesAttribute(): int
    {
        return count($this->test_cases ?? []);
    }

    public function getPointsPerTestCaseAttribute(): float
    {
        $total = $this->total_test_cases;
        return $total > 0 ? $this->max_points / $total : 0;
    }

    public function getTimeLimitSecondsAttribute(): float
    {
        return $this->time_limit_ms / 1000;
    }

    public function getDifficultyLabelAttribute(): string
    {
        $labels = [
            self::DIFFICULTY_EASY => 'Oson',
            self::DIFFICULTY_MEDIUM => "O'rtacha",
            self::DIFFICULTY_HARD => 'Qiyin',
            self::DIFFICULTY_EXPERT => 'Ekspert',
        ];
        return $labels[$this->difficulty] ?? $this->difficulty;
    }

    public function getDifficultyColorAttribute(): string
    {
        $colors = [
            self::DIFFICULTY_EASY => 'green',
            self::DIFFICULTY_MEDIUM => 'yellow',
            self::DIFFICULTY_HARD => 'orange',
            self::DIFFICULTY_EXPERT => 'red',
        ];
        return $colors[$this->difficulty] ?? 'gray';
    }

    // ==================== METHODS ====================

    /**
     * Get starter code for a language
     */
    public function getStarterCode(string $language): string
    {
        return $this->starter_code[$language] ?? $this->getDefaultStarterCode($language);
    }

    /**
     * Get default starter code template
     */
    private function getDefaultStarterCode(string $language): string
    {
        $templates = [
            self::LANG_PYTHON => "def solve():\n    # Your code here\n    pass\n\nif __name__ == '__main__':\n    solve()",
            self::LANG_JAVASCRIPT => "function solve() {\n    // Your code here\n}\n\nsolve();",
            self::LANG_CPP => "#include <iostream>\nusing namespace std;\n\nint main() {\n    // Your code here\n    return 0;\n}",
            self::LANG_JAVA => "import java.util.*;\n\npublic class Main {\n    public static void main(String[] args) {\n        // Your code here\n    }\n}",
        ];

        return $templates[$language] ?? '';
    }

    /**
     * Check if language is allowed
     */
    public function isLanguageAllowed(string $language): bool
    {
        $allowed = $this->allowed_languages ?? [self::LANG_PYTHON, self::LANG_JAVASCRIPT, self::LANG_CPP, self::LANG_JAVA];
        return in_array($language, $allowed);
    }

    /**
     * Calculate points for passed test cases
     */
    public function calculatePoints(int $passedTestCases): float
    {
        if (!$this->partial_scoring) {
            // All or nothing
            return $passedTestCases === $this->total_test_cases ? $this->max_points : 0;
        }

        // Partial scoring
        return round($passedTestCases * $this->points_per_test_case, 2);
    }
}
