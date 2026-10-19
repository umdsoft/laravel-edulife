<?php

namespace App\Exceptions\Exam;

use App\Enums\Exam\ReviewStatus;
use DomainException;

class InvalidReviewTransition extends DomainException
{
    public static function between(ReviewStatus $from, ReviewStatus $to): self
    {
        return new self("Question review status can not change from [{$from->value}] to [{$to->value}].");
    }
}
