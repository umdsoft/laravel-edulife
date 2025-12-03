<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TeacherScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateAllTeacherScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 3600; // 1 soat
    
    public function handle(TeacherScoreService $service): void
    {
        $teachers = User::where('role', 'teacher')
            ->whereHas('teacherProfile')
            ->get();
        
        $updated = 0;
        $levelChanges = 0;
        
        foreach ($teachers as $teacher) {
            try {
                $oldLevel = $teacher->teacherProfile->level;
                
                $service->updateTeacherScore($teacher);
                
                $teacher->refresh();
                $newLevel = $teacher->teacherProfile->level;
                
                $updated++;
                if ($oldLevel !== $newLevel) {
                    $levelChanges++;
                }
                
            } catch (\Exception $e) {
                Log::error("Teacher score calculation failed", [
                    'teacher_id' => $teacher->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info("Teacher scores calculated", [
            'total_teachers' => $teachers->count(),
            'updated' => $updated,
            'level_changes' => $levelChanges,
        ]);
    }
}
