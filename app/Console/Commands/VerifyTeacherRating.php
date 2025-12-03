<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\TeacherProfile;
use App\Services\TeacherScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyTeacherRating extends Command
{
    protected $signature = 'verify:teacher-rating';
    protected $description = 'Verify Teacher Rating System Logic';

    public function handle(TeacherScoreService $service)
    {
        $this->info('Starting Teacher Rating System Verification...');

        DB::beginTransaction();

        try {
            // 1. Create a test teacher
            $teacher = User::factory()->create([
                'role' => 'teacher',
                'first_name' => 'Rating',
                'last_name' => 'Test',
                'email' => 'rating_test_' . time() . '@example.com',
            ]);
            
            TeacherProfile::create([
                'user_id' => $teacher->id,
                'username' => 'rating_test',
                'level' => 'new',
                'score' => 0,
            ]);

            $this->info("Created test teacher: {$teacher->email}");

            // 2. Add some data to influence score
            
            // Create Direction
            $direction = \App\Models\Direction::create([
                'name_uz' => 'Test Direction',
                'name_ru' => 'Test Direction',
                'name_en' => 'Test Direction',
                'slug' => 'test-direction-' . time(),
                'is_active' => true,
            ]);

            // Create a course
            $course = Course::create([
                'teacher_id' => $teacher->id,
                'direction_id' => $direction->id,
                'title' => 'Test Course',
                'slug' => 'test-course-' . time(),
                'short_description' => 'Test Short Description',
                'description' => 'Test Description',
                'status' => 'published',
                'price' => 100000,
                'is_free' => false,
                'difficulty' => 'beginner',
                'language' => 'uz',
            ]);
            
            // Add students (Enrollments)
            $students = User::factory(10)->create(['role' => 'student']);
            foreach ($students as $student) {
                Enrollment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'status' => 'active',
                    'progress' => 50,
                ]);
            }
            
            // Add reviews (Rating Score)
            foreach ($students->take(5) as $student) {
                $enrollment = Enrollment::where('user_id', $student->id)
                    ->where('course_id', $course->id)
                    ->first();
                    
                Review::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'enrollment_id' => $enrollment->id,
                    'rating' => 5,
                    'review_text' => 'Great course!',
                    'status' => 'approved',
                ]);
            }
            
            // Complete some enrollments (Completion Score)
            foreach ($students->take(3) as $student) {
                $enrollment = Enrollment::where('user_id', $student->id)
                    ->where('course_id', $course->id)
                    ->first();
                $enrollment->update(['status' => 'completed', 'progress' => 100]);
            }

            $this->info("Added course, students, reviews, and completions.");

            // 3. Calculate Score
            $this->info("Calculating score...");
            $service->updateTeacherScore($teacher);
            
            $teacher->refresh();
            $profile = $teacher->teacherProfile;
            
            $this->info("Score: {$profile->score}");
            $this->info("Level: {$profile->level}");
            $this->info("Commission Rate: {$profile->commission_rate}%");
            
            // Verify Score Components
            $breakdown = $profile->score_breakdown;
            $this->table(
                ['Component', 'Score', 'Raw Value'],
                [
                    ['Rating', $breakdown['rating_score'], $breakdown['raw']['avg_rating']],
                    ['Completion', $breakdown['completion_score'], $breakdown['raw']['completion_rate']],
                    ['Students', $breakdown['students_score'], $breakdown['raw']['total_students']],
                    ['Total', $breakdown['total'], '-'],
                ]
            );

            // 4. Verify Level Change
            if ($profile->level !== 'new') {
                $this->info("Level changed successfully!");
                
                $change = $teacher->levelChanges()->latest()->first();
                if ($change) {
                    $this->info("Level change log found: {$change->old_level} -> {$change->new_level}");
                } else {
                    $this->error("Level change log MISSING!");
                }
            } else {
                $this->warn("Level did not change (might be expected depending on score).");
            }

            // 5. Test Admin Override
            $this->info("Testing Admin Override...");
            $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
            
            $service->adminOverrideLevel($teacher, 'top', 'Testing override', $admin);
            
            $teacher->refresh();
            if ($teacher->teacherProfile->level === 'top') {
                $this->info("Admin override successful! Level is now TOP.");
            } else {
                $this->error("Admin override FAILED!");
            }

        } catch (\Exception $e) {
            $this->error("Verification failed: " . $e->getMessage());
            // $this->error($e->getTraceAsString());
        } finally {
             DB::rollBack();
             $this->info("Rolled back database changes.");
        }
    }
}
