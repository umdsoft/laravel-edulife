<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Teacher topic tree: add, rename, move (levels follow), drag-sort,
 * deactivate instead of delete when something uses the topic.
 */
class TopicTreeTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $math;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->math = Subject::factory()->math()->create();
    }

    public function test_tree_page_with_counts(): void
    {
        $section = Topic::factory()->create(['subject_id' => $this->math->id, 'name_uz' => 'Tenglamalar']);
        $child = Topic::factory()->childOf($section)->create();
        ExamQuestionFactory::new()->approved($this->teacher)->forTopic($child)->create();
        ExamQuestionFactory::new()->forTopic($child)->create();

        $this->actingAs($this->teacher)->get(route('teacher.exam.topics.index', ['subject' => 'math']))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Topics/Index')
                ->where('subject.code', 'math')
                ->has('tree', 1)
                ->where('tree.0.questions_total', 2)
                ->where('tree.0.questions_approved', 1)
                ->where('tree.0.children.0.id', $child->id));
    }

    public function test_add_section_and_child_with_generated_codes(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.topics.store'), ['subject_id' => $this->math->id, 'name_uz' => 'Kombinatorika va ehtimollik'])
            ->assertSessionHasNoErrors();
        $section = Topic::query()->where('name_uz', 'Kombinatorika va ehtimollik')->firstOrFail();
        $this->assertSame('math.kombinatorika_va_ehtimollik', $section->code);
        $this->assertSame(1, $section->level);

        $this->post(route('teacher.exam.topics.store'), ['subject_id' => $this->math->id, 'parent_id' => $section->id, 'name_uz' => 'O\'rin almashtirish'])
            ->assertSessionHasNoErrors();
        $child = Topic::query()->where('parent_id', $section->id)->firstOrFail();
        $this->assertSame(2, $child->level);
        $this->assertStringStartsWith('math.kombinatorika_va_ehtimollik.', $child->code);

        // parent of another subject
        $physics = Subject::factory()->physics()->create();
        $this->post(route('teacher.exam.topics.store'), ['subject_id' => $physics->id, 'parent_id' => $section->id, 'name_uz' => 'X'])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_rename_and_toggle(): void
    {
        $section = Topic::factory()->create(['subject_id' => $this->math->id]);
        $child = Topic::factory()->childOf($section)->create();
        $code = $child->code;

        $this->actingAs($this->teacher)->patch(route('teacher.exam.topics.update', $child), ['name_uz' => 'Yangi nom'])->assertSessionHasNoErrors();
        $this->assertSame('Yangi nom', $child->fresh()->name_uz);
        $this->assertSame($code, $child->fresh()->code, 'codes never change');

        $this->patch(route('teacher.exam.topics.update', $section), ['is_active' => false]);
        $this->assertFalse($section->fresh()->is_active);
        $this->assertFalse($child->fresh()->is_active, 'deactivating a section hides its subtree');

        $this->patch(route('teacher.exam.topics.update', $child), ['is_active' => true]);
        $this->assertTrue($child->fresh()->is_active);
        $this->assertTrue($section->fresh()->is_active, 'activating a child activates its ancestors');
    }

    public function test_move_updates_levels_of_the_subtree_and_refuses_cycles(): void
    {
        $a = Topic::factory()->create(['subject_id' => $this->math->id]);
        $b = Topic::factory()->create(['subject_id' => $this->math->id]);
        $b1 = Topic::factory()->childOf($b)->create();
        $b11 = Topic::factory()->childOf($b1)->create();

        $this->actingAs($this->teacher)->post(route('teacher.exam.topics.move', $b), ['parent_id' => $a->id])->assertSessionHasNoErrors();
        $this->assertSame([2, 3, 4], [$b->fresh()->level, $b1->fresh()->level, $b11->fresh()->level]);
        $this->assertSame($a->id, $b->fresh()->parent_id);

        $this->post(route('teacher.exam.topics.move', $a), ['parent_id' => $b11->id])->assertSessionHasErrors('parent_id');
        $this->assertNull($a->fresh()->parent_id);

        $this->post(route('teacher.exam.topics.move', $b), ['parent_id' => null])->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3], [$b->fresh()->level, $b1->fresh()->level, $b11->fresh()->level]);

        $physics = Topic::factory()->create(['subject_id' => Subject::factory()->physics()->create()->id]);
        $this->post(route('teacher.exam.topics.move', $b), ['parent_id' => $physics->id])->assertSessionHasErrors('parent_id');
    }

    public function test_drag_sort_of_siblings(): void
    {
        [$a, $b, $c] = Topic::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])
            ->create(['subject_id' => $this->math->id])->all();
        $child = Topic::factory()->childOf($a)->create();

        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.topics.reorder'), ['subject_id' => $this->math->id, 'parent_id' => null, 'ids' => [$c->id, $a->id, $b->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame([2, 3, 1], [$a->fresh()->sort_order, $b->fresh()->sort_order, $c->fresh()->sort_order]);

        // a topic of another parent can not be sorted in
        $this->post(route('teacher.exam.topics.reorder'), ['subject_id' => $this->math->id, 'parent_id' => null, 'ids' => [$child->id, $a->id]])
            ->assertSessionHasErrors('ids');
    }

    public function test_used_topic_is_deactivated_unused_is_deleted(): void
    {
        $used = Topic::factory()->create(['subject_id' => $this->math->id]);
        ExamQuestionFactory::new()->forTopic($used)->create();
        $parent = Topic::factory()->create(['subject_id' => $this->math->id]);
        Topic::factory()->childOf($parent)->create();
        $unused = Topic::factory()->create(['subject_id' => $this->math->id]);

        $this->actingAs($this->teacher);
        $this->delete(route('teacher.exam.topics.destroy', $used))->assertSessionHas('success');
        $this->assertNotSoftDeleted($used);
        $this->assertFalse($used->fresh()->is_active);

        $this->delete(route('teacher.exam.topics.destroy', $parent));
        $this->assertNotSoftDeleted($parent);
        $this->assertFalse($parent->fresh()->is_active);

        $this->delete(route('teacher.exam.topics.destroy', $unused));
        $this->assertSoftDeleted($unused);
    }
}
