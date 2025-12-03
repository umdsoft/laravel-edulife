<?php

namespace App\Services;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\TeacherScoreHistory;
use App\Models\TeacherLevelChange;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\CourseQuestion;
use App\Notifications\TeacherLevelChangedNotification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherScoreService
{
    /**
     * Bitta o'qituvchi uchun score hisoblash
     */
    public function calculateScore(User $teacher): array
    {
        $profile = $teacher->teacherProfile;
        
        if (!$profile) {
            return ['score' => 0, 'level' => 'new', 'breakdown' => []];
        }
        
        // 1. O'quvchi baholari (30%)
        $ratingData = $this->calculateRatingScore($teacher);
        
        // 2. Kurs tugatish darajasi (20%)
        $completionData = $this->calculateCompletionScore($teacher);
        
        // 3. O'quvchilar soni (15%)
        $studentsData = $this->calculateStudentsScore($teacher);
        
        // 4. Javob berish tezligi (10%)
        $responseData = $this->calculateResponseScore($teacher);
        
        // 5. Faollik darajasi (10%)
        $activityData = $this->calculateActivityScore($teacher);
        
        // 6. Test natijalari (10%)
        $testData = $this->calculateTestScore($teacher);
        
        // 7. Refund darajasi (5%)
        $refundData = $this->calculateRefundScore($teacher);
        
        // Jami score
        $totalScore = 
            $ratingData['score'] +
            $completionData['score'] +
            $studentsData['score'] +
            $responseData['score'] +
            $activityData['score'] +
            $testData['score'] +
            $refundData['score'];
        
        $totalScore = round(min(100, max(0, $totalScore)), 2);
        
        // Level aniqlash
        $level = $this->determineLevel($totalScore, $teacher);
        
        // Breakdown
        $breakdown = [
            'rating_score' => round($ratingData['score'], 2),
            'completion_score' => round($completionData['score'], 2),
            'students_score' => round($studentsData['score'], 2),
            'response_score' => round($responseData['score'], 2),
            'activity_score' => round($activityData['score'], 2),
            'test_score' => round($testData['score'], 2),
            'refund_score' => round($refundData['score'], 2),
            'total' => $totalScore,
            'raw' => [
                'avg_rating' => $ratingData['raw'],
                'completion_rate' => $completionData['raw'],
                'total_students' => $studentsData['raw'],
                'avg_response_hours' => $responseData['raw'],
                'activity_actions' => $activityData['raw'],
                'avg_test_score' => $testData['raw'],
                'refund_rate' => $refundData['raw'],
            ],
        ];
        
        return [
            'score' => $totalScore,
            'level' => $level,
            'breakdown' => $breakdown,
        ];
    }
    
    /**
     * 1. O'quvchi baholari (30%)
     * Formula: (avg_rating / 5) * 100 * 0.30
     */
    private function calculateRatingScore(User $teacher): array
    {
        $avgRating = Review::whereHas('course', fn($q) => $q->where('teacher_id', $teacher->id))
            ->avg('rating') ?? 0;
        
        $score = ($avgRating / 5) * 100 * 0.30;
        
        return ['score' => $score, 'raw' => round($avgRating, 2)];
    }
    
    /**
     * 2. Kurs tugatish darajasi (20%)
     * Formula: (completed_enrollments / total_enrollments) * 100 * 0.20
     */
    private function calculateCompletionScore(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        $totalEnrollments = Enrollment::whereIn('course_id', $courseIds)->count();
        $completedEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->count();
        
        $completionRate = $totalEnrollments > 0 
            ? ($completedEnrollments / $totalEnrollments) * 100 
            : 0;
        
        $score = $completionRate * 0.20;
        
        return ['score' => $score, 'raw' => round($completionRate, 1)];
    }
    
    /**
     * 3. O'quvchilar soni (15%)
     * Formula: min(total_students / 1000, 1) * 100 * 0.15
     * Max 1000 ta o'quvchida 100% ball oladi
     */
    private function calculateStudentsScore(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        $totalStudents = Enrollment::whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');
        
        $score = min($totalStudents / 1000, 1) * 100 * 0.15;
        
        return ['score' => $score, 'raw' => $totalStudents];
    }
    
    /**
     * 4. Javob berish tezligi (10%)
     * < 1 soat = 100 ball
     * < 6 soat = 80 ball
     * < 24 soat = 60 ball
     * < 48 soat = 40 ball
     * > 48 soat = 20 ball
     */
    private function calculateResponseScore(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        // Oxirgi 30 kundagi javob berilgan savollar
        $answeredQuestions = CourseQuestion::whereIn('course_id', $courseIds)
            ->whereNotNull('answered_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNull('parent_id') // faqat asosiy savollar
            ->get();
        
        if ($answeredQuestions->isEmpty()) {
            return ['score' => 100 * 0.10, 'raw' => null]; // Default 100 ball agar savol yo'q bo'lsa
        }
        
        $totalHours = 0;
        foreach ($answeredQuestions as $question) {
            $hours = $question->created_at->diffInHours($question->answered_at);
            $totalHours += $hours;
        }
        
        $avgHours = $totalHours / $answeredQuestions->count();
        
        if ($avgHours < 1) {
            $rawScore = 100;
        } elseif ($avgHours < 6) {
            $rawScore = 80;
        } elseif ($avgHours < 24) {
            $rawScore = 60;
        } elseif ($avgHours < 48) {
            $rawScore = 40;
        } else {
            $rawScore = 20;
        }
        
        $score = $rawScore * 0.10;
        
        return ['score' => $score, 'raw' => round($avgHours, 1)];
    }
    
    /**
     * 5. Faollik darajasi (10%)
     * Oxirgi 30 kunda:
     * - Yangi kurs = 30 ball
     * - Yangi modul = 15 ball
     * - Yangi dars = 10 ball
     * - Kurs yangilash = 5 ball
     * Max 100 ball
     */
    private function calculateActivityScore(User $teacher): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        // Yangi kurslar
        $newCourses = Course::where('teacher_id', $teacher->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        
        // Yangi modullar
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        $newModules = DB::table('modules')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        
        // Yangi darslar
        $newLessons = DB::table('lessons')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        
        // Kurs yangilashlar
        $updates = Course::where('teacher_id', $teacher->id)
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('updated_at', '!=', DB::raw('created_at'))
            ->count();
        
        $totalActions = $newCourses + $newModules + $newLessons + $updates;
        
        $rawScore = min(100, 
            ($newCourses * 30) + 
            ($newModules * 15) + 
            ($newLessons * 10) + 
            ($updates * 5)
        );
        
        $score = $rawScore * 0.10;
        
        return ['score' => $score, 'raw' => $totalActions];
    }
    
    /**
     * 6. Test natijalari (10%)
     * O'quvchilarning o'rtacha test natijasi
     */
    private function calculateTestScore(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        $avgScore = DB::table('test_attempts')
            ->join('tests', 'test_attempts.test_id', '=', 'tests.id')
            ->whereIn('tests.course_id', $courseIds)
            ->where('test_attempts.created_at', '>=', now()->subDays(90))
            ->avg('test_attempts.score') ?? 0;
        
        $score = $avgScore * 0.10;
        
        return ['score' => $score, 'raw' => round($avgScore, 1)];
    }
    
    /**
     * 7. Refund darajasi (5%)
     * Formula: max(0, 100 - refund_rate * 10) * 0.05
     * 0% refund = 100 ball (5 score)
     * 10%+ refund = 0 ball (0 score)
     */
    private function calculateRefundScore(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        // Oxirgi 90 kundagi to'lovlar
        $totalPayments = DB::table('payments')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();
        
        $refundedPayments = DB::table('payments')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(90))
            ->where('status', 'refunded')
            ->count();
        
        $refundRate = $totalPayments > 0 
            ? ($refundedPayments / $totalPayments) * 100 
            : 0;
        
        $rawScore = max(0, 100 - $refundRate * 10);
        $score = $rawScore * 0.05;
        
        return ['score' => $score, 'raw' => round($refundRate, 1)];
    }
    
    /**
     * Level aniqlash (score + requirements)
     */
    private function determineLevel(float $score, User $teacher): string
    {
        $stats = $this->getTeacherStats($teacher);
        
        // TOP: 90+ score + requirements
        if ($score >= 90 &&
            $stats['published_courses'] >= 5 &&
            $stats['total_students'] >= 500 &&
            $stats['avg_rating'] >= 4.5 &&
            $stats['completion_rate'] >= 75
        ) {
            return 'top';
        }
        
        // FEATURED: 75+ score + requirements
        if ($score >= 75 &&
            $stats['published_courses'] >= 3 &&
            $stats['total_students'] >= 100 &&
            $stats['avg_rating'] >= 4.0 &&
            $stats['completion_rate'] >= 60
        ) {
            return 'featured';
        }
        
        // VERIFIED: 50+ score + requirements
        if ($score >= 50 &&
            $stats['published_courses'] >= 1 &&
            $stats['total_students'] >= 10 &&
            $stats['avg_rating'] >= 3.5
        ) {
            return 'verified';
        }
        
        // NEW: default
        return 'new';
    }
    
    /**
     * O'qituvchi statistikasi (level requirements uchun)
     */
    private function getTeacherStats(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');
        
        $publishedCourses = Course::where('teacher_id', $teacher->id)
            ->where('status', 'published')
            ->count();
        
        $totalStudents = Enrollment::whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');
        
        $avgRating = Review::whereHas('course', fn($q) => $q->where('teacher_id', $teacher->id))
            ->avg('rating') ?? 0;
        
        $totalEnrollments = Enrollment::whereIn('course_id', $courseIds)->count();
        $completedEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->count();
        
        $completionRate = $totalEnrollments > 0 
            ? ($completedEnrollments / $totalEnrollments) * 100 
            : 0;
        
        return [
            'published_courses' => $publishedCourses,
            'total_students' => $totalStudents,
            'avg_rating' => round($avgRating, 1),
            'completion_rate' => round($completionRate, 1),
        ];
    }
    
    /**
     * Score ni profile ga saqlash va level o'zgarishini tekshirish
     */
    public function updateTeacherScore(User $teacher): void
    {
        $result = $this->calculateScore($teacher);
        $profile = $teacher->teacherProfile;
        
        if (!$profile) {
            return;
        }
        
        $oldLevel = $profile->level;
        $oldScore = $profile->score;
        $newLevel = $result['level'];
        $newScore = $result['score'];
        
        // Profile yangilash
        $profile->update([
            'score' => $newScore,
            'score_breakdown' => $result['breakdown'],
            'score_updated_at' => now(),
            'level' => $newLevel,
            'commission_rate' => $this->getCommissionRate($newLevel),
        ]);
        
        // Score history ga yozish
        TeacherScoreHistory::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'calculated_date' => now()->toDateString(),
            ],
            [
                'score' => $newScore,
                'level' => $newLevel,
                'breakdown' => $result['breakdown'],
            ]
        );
        
        // Level o'zgardimi?
        if ($oldLevel !== $newLevel) {
            $this->handleLevelChange($teacher, $oldLevel, $newLevel, $oldScore, $newScore);
        }
    }
    
    /**
     * Level o'zgarishini qayd qilish va notification yuborish
     */
    private function handleLevelChange(
        User $teacher, 
        string $oldLevel, 
        string $newLevel, 
        float $oldScore, 
        float $newScore
    ): void {
        // Level change yozish
        $change = TeacherLevelChange::create([
            'teacher_id' => $teacher->id,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => null, // automatic
            'changed_by' => null,
        ]);
        
        // Profile ga level_changed_at yozish
        $teacher->teacherProfile->update([
            'level_changed_at' => now(),
        ]);
        
        // Teacher ga notification
        \App\Models\Notification::send(
            $teacher,
            'teacher_level_changed',
            "Darajangiz o'zgardi!",
            "Sizning darajangiz {$oldLevel} dan {$newLevel} ga o'zgardi.",
            [
                'change_id' => $change->id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'old_score' => $oldScore,
                'new_score' => $newScore,
            ],
            'high'
        );
    }
    
    /**
     * Admin tomonidan qo'lda level o'zgartirish
     */
    public function adminOverrideLevel(User $teacher, string $newLevel, string $reason, User $admin): void
    {
        $profile = $teacher->teacherProfile;
        $oldLevel = $profile->level;
        $score = $profile->score;
        
        if ($oldLevel === $newLevel) {
            return;
        }
        
        // Profile yangilash
        $profile->update([
            'level' => $newLevel,
            'commission_rate' => $this->getCommissionRate($newLevel),
            'level_changed_at' => now(),
        ]);
        
        // Level change yozish (admin override)
        $change = TeacherLevelChange::create([
            'teacher_id' => $teacher->id,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'old_score' => $score,
            'new_score' => $score,
            'reason' => $reason,
            'changed_by' => $admin->id,
        ]);
        
        // Teacher ga notification
        \App\Models\Notification::send(
            $teacher,
            'teacher_level_changed',
            "Darajangiz admin tomonidan o'zgartirildi!",
            "Sizning darajangiz {$oldLevel} dan {$newLevel} ga o'zgartirildi. Sabab: {$reason}",
            [
                'change_id' => $change->id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'reason' => $reason,
                'admin_id' => $admin->id,
            ],
            'high'
        );
    }
    
    /**
     * Commission rate olish
     */
    public function getCommissionRate(string $level): int
    {
        return match($level) {
            'top' => 20,
            'featured' => 25,
            'verified' => 30,
            'new' => 30,
            default => 30,
        };
    }
    
    /**
     * Level uchun tavsiyalar
     */
    public function getRecommendations(User $teacher): array
    {
        $result = $this->calculateScore($teacher);
        $stats = $this->getTeacherStats($teacher);
        $breakdown = $result['breakdown'];
        $recommendations = [];
        
        // Rating yaxshilash
        if ($breakdown['raw']['avg_rating'] < 4.5) {
            $recommendations[] = [
                'type' => 'rating',
                'message' => 'Kurs sifatini oshiring va o\'quvchilardan ijobiy sharh so\'rang',
                'current' => $breakdown['raw']['avg_rating'],
                'target' => 4.5,
            ];
        }
        
        // Completion rate yaxshilash
        if ($breakdown['raw']['completion_rate'] < 75) {
            $recommendations[] = [
                'type' => 'completion',
                'message' => 'Kurslaringizni qiziqarli qiling, o\'quvchilar tugatsin',
                'current' => $breakdown['raw']['completion_rate'],
                'target' => 75,
            ];
        }
        
        // Response time yaxshilash
        if ($breakdown['raw']['avg_response_hours'] && $breakdown['raw']['avg_response_hours'] > 6) {
            $recommendations[] = [
                'type' => 'response',
                'message' => 'Q&A savollarga tezroq javob bering (< 6 soat)',
                'current' => $breakdown['raw']['avg_response_hours'] . ' soat',
                'target' => '< 6 soat',
            ];
        }
        
        // Ko'proq kurs qo'shish
        if ($stats['published_courses'] < 5) {
            $recommendations[] = [
                'type' => 'courses',
                'message' => 'Ko\'proq sifatli kurs qo\'shing',
                'current' => $stats['published_courses'],
                'target' => 5,
            ];
        }
        
        // Faollik oshirish
        if ($breakdown['activity_score'] < 8) {
            $recommendations[] = [
                'type' => 'activity',
                'message' => 'Kurslaringizni yangilab turing, yangi darslar qo\'shing',
                'current' => $breakdown['raw']['activity_actions'] . ' ta harakat',
                'target' => 'Oyiga 10+ harakat',
            ];
        }
        
        return $recommendations;
    }
}
