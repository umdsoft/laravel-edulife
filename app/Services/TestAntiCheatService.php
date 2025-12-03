<?php

namespace App\Services;

use App\Models\TestAttempt;

class TestAntiCheatService
{
    /**
     * Log an anti-cheat event
     */
    public function logEvent(TestAttempt $attempt, string $eventType, array $data = []): void
    {
        $attempt->logEvent($eventType, $data);
    }
    
    /**
     * Check if attempt should be flagged or terminated
     */
    public function checkStatus(TestAttempt $attempt): void
    {
        // Logic moved to TestAttempt model for cohesion, 
        // but can be expanded here for more complex rules
        
        if ($attempt->tab_switches >= 5 || $attempt->fullscreen_exits >= 3) {
            if (!$attempt->is_flagged) {
                $attempt->update([
                    'is_flagged' => true,
                    'flag_reason' => 'Suspicious activity detected: ' . 
                        $attempt->tab_switches . ' tab switches, ' . 
                        $attempt->fullscreen_exits . ' fullscreen exits',
                ]);
            }
        }
    }
}
