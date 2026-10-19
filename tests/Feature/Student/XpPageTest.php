<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** QA-S-10: "Daraja 1, 8 XP, 0%, keyingi darajagacha 0 XP" — the props read undefined accessors. */
class XpPageTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_progress_to_the_next_level_is_computed(): void
    {
        $student = $this->studentUser();
        $student->studentProfile->update(['xp' => 150, 'level' => 2]); // level 2 = 100..399 XP

        $this->actingAs($student)->get(route('student.xp.index'))
            ->assertInertia(fn (Assert $p) => $p->where('xp_to_next_level', 250)->where('level_progress', 16));
    }
}
