<?php

namespace Tests\Feature\Teacher;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** QA2-T-01: /teacher/lessons/{text lesson}/chapters was a 500 (no video). */
class VideoChaptersPageTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_a_lesson_without_video_goes_back_to_the_lesson_with_a_note(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)), ['type' => 'text']);

        $this->actingAs($teacher)->get(route('teacher.lessons.chapters.index', $lesson))
            ->assertRedirect(route('teacher.lessons.edit', $lesson))
            ->assertSessionHas('warning');
    }
}
