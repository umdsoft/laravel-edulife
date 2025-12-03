<?php

namespace App\Jobs;

use App\Models\TestAttempt;
use App\Services\TestEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoSubmitExpiredTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(TestEvaluationService $evaluationService): void
    {
        // Find expired attempts
        $expiredAttempts = TestAttempt::where('status', 'in_progress')
            ->where('expires_at', '<', now())
            ->get();
        
        foreach ($expiredAttempts as $attempt) {
            // Evaluate
            $evaluationService->evaluate($attempt);
            
            // Mark as expired
            $attempt->update([
                'status' => 'expired',
                'submitted_at' => $attempt->expires_at,
                'time_spent' => $attempt->started_at->diffInSeconds($attempt->expires_at),
            ]);
            
            // Log event
            $attempt->logEvent('test_expired', [
                'auto_submitted' => true,
            ]);
        }
    }
}
