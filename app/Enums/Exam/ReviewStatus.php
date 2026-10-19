<?php

namespace App\Enums\Exam;

/**
 * Teacher review lifecycle of a question_bank row.
 *
 * Only APPROVED questions may ever be placed into an exam variant.
 */
enum ReviewStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Retired = 'retired';

    /**
     * Allowed state transitions. Retired is terminal: a retired question is
     * kept for regrading history, a corrected copy is created instead.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InReview, self::Rejected],
            self::InReview => [self::Draft, self::Approved, self::Rejected],
            self::Approved => [self::InReview, self::Retired],
            self::Rejected => [self::Draft, self::InReview],
            self::Retired => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return $next === $this || in_array($next, $this->allowedTransitions(), true);
    }

    public function isExamEligible(): bool
    {
        return $this === self::Approved;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Qoralama',
            self::InReview => 'Tekshiruvda',
            self::Approved => 'Tasdiqlangan',
            self::Rejected => 'Rad etilgan',
            self::Retired => 'Muomaladan chiqarilgan',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
