<?php

namespace App\Exceptions\Exam;

use DomainException;

/**
 * Thrown when a question would become APPROVED while it is not complete
 * (e.g. no option marked correct). violations: code => Uzbek message.
 */
class QuestionNotApprovable extends DomainException
{
    /** @param array<string, string> $violations */
    public function __construct(public readonly array $violations)
    {
        parent::__construct('Question can not be approved: '.implode('; ', array_keys($violations)));
    }
}
