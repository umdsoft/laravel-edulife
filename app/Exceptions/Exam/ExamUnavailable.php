<?php

namespace App\Exceptions\Exam;

use DomainException;

/**
 * The student can not start / continue an exam right now. The message is
 * Uzbek and safe to show; $reason is a stable code for clients and tests:
 * not_open, closed, not_allowed, already_done, placement_done, attempt_closed, no_questions.
 */
class ExamUnavailable extends DomainException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function notOpen(): self
    {
        return new self('not_open', 'Imtihon hali boshlanmagan.');
    }

    public static function closed(): self
    {
        return new self('closed', 'Imtihon vaqti tugagan.');
    }

    public static function notAllowed(): self
    {
        return new self('not_allowed', 'Bu imtihon siz uchun emas.');
    }

    public static function alreadyDone(): self
    {
        return new self('already_done', 'Siz bu imtihonni topshirib bo\'lgansiz.');
    }

    public static function placementDone(): self
    {
        return new self('placement_done', 'Qabul testini bu fan bo\'yicha topshirib bo\'lgansiz. Qayta topshirish uchun ustozga murojaat qiling.');
    }

    public static function attemptClosed(): self
    {
        return new self('attempt_closed', 'Urinish yakunlangan, javoblar endi qabul qilinmaydi.');
    }

    public static function noQuestions(): self
    {
        return new self('no_questions', 'Savollar bankida bu imtihon uchun savol yetarli emas. Ustozga xabar bering.');
    }
}
