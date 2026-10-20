<?php

namespace App\Jobs\Exam;

use App\Enums\Exam\ExamSessionStatus;
use App\Models\Exam\ExamSession;
use App\Services\Exam\VariantGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Pre-generates the personal variants of a session before it opens, so the
 * Saturday rush only reads them (spec section 11, "Ishlash").
 */
class GenerateExamVariantsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $uniqueFor = 900;

    public function __construct(public string $sessionId) {}

    public function uniqueId(): string
    {
        return $this->sessionId;
    }

    public function handle(VariantGenerator $generator): void
    {
        $session = ExamSession::query()->find($this->sessionId);
        if (! $session || ! in_array($session->status, [ExamSessionStatus::Draft, ExamSessionStatus::Scheduled, ExamSessionStatus::Open], true)) {
            return;
        }

        $summary = $generator->generateForSession($session);
        Log::info('exam: variants generated', ['session_id' => $session->id] + $summary);
    }
}
