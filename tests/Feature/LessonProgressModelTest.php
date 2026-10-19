<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LessonProgressModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_progress_uses_the_lesson_progresses_table(): void
    {
        $table = (new LessonProgress())->getTable();

        $this->assertSame('lesson_progresses', $table);
        foreach ((new LessonProgress())->getFillable() as $column) {
            $this->assertTrue(Schema::hasColumn($table, $column), "{$table}.{$column} is missing");
        }
    }

    public function test_lesson_has_video_and_lesson_progress_relations(): void
    {
        $lesson = new Lesson();

        $this->assertInstanceOf(HasOne::class, $lesson->video());
        $this->assertInstanceOf(Video::class, $lesson->video()->getRelated());
        $this->assertInstanceOf(HasMany::class, $lesson->lessonProgress());
        $this->assertSame('lesson_progresses', $lesson->lessonProgress()->getRelated()->getTable());
    }
}
