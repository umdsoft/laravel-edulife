<?php

namespace Tests\Feature\Admin;

use App\Models\Direction;
use App\Models\Olympiad;
use App\Models\OlympiadType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OlympiadManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function type(): OlympiadType
    {
        $direction = Direction::forceCreate(['name_uz' => 'Matematika', 'slug' => 'math']);

        return OlympiadType::forceCreate([
            'direction_id' => $direction->id, 'name' => 'math', 'display_name' => 'Mathematics',
            'display_name_uz' => 'Matematika', 'sections' => ['test', 'open_ended'], 'section_weights' => ['test' => 70, 'open_ended' => 30],
            'is_active' => true,
        ]);
    }

    private function payload(OlympiadType $type, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bahorgi matematika olimpiadasi',
            'short_description' => 'Qisqa',
            'rules' => 'Qoidalar matni',
            'olympiad_type_id' => $type->id,
            'registration_fee' => 15000,
            'registration_start_at' => '2026-11-01T09:00',
            'registration_end_at' => '2026-11-10T18:00',
            'olympiad_start_at' => '2026-11-15T10:00',
            'olympiad_end_at' => '2026-11-15T13:00',
            'max_participants' => 500,
            'demo_config' => ['enabled' => false, 'price' => 0, 'max_attempts' => 3],
            'anti_cheat_config' => ['fullscreen_required' => true, 'device_lock' => true, 'max_tab_switches' => 3, 'max_warnings' => 5],
            'reward_config' => ['coins_per_correct' => 1, 'top_10_coins' => 100],
            'create_default_sections' => true,
        ], $overrides);
    }

    public function test_olympiad_can_be_created_edited_and_updated(): void
    {
        $admin = $this->admin();
        $type = $this->type();

        $response = $this->actingAs($admin)->post(route('admin.olympiads.store'), $this->payload($type));
        $response->assertSessionHasNoErrors();

        $olympiad = Olympiad::firstOrFail();
        $response->assertRedirect(route('admin.olympiads.edit', $olympiad->id));
        $this->assertSame('draft', $olympiad->status);
        $this->assertEquals(15000, $olympiad->entry_fee);
        $this->assertSame('Qoidalar matni', $olympiad->rules);
        $this->assertSame(2, $olympiad->sections()->count());

        $this->actingAs($admin)->get(route('admin.olympiads.edit', $olympiad->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Olympiad/Edit')
                ->where('form.title', 'Bahorgi matematika olimpiadasi')
                ->where('form.registration_fee', 15000)
                ->where('form.olympiad_start_at', '2026-11-15T10:00')
                ->has('sections', 2));

        $this->actingAs($admin)->put(route('admin.olympiads.update', $olympiad->id), $this->payload($type, [
            'title' => 'Yangilangan nom',
            'registration_fee' => 0,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.olympiads.show', $olympiad->id));

        $olympiad->refresh();
        $this->assertSame('Yangilangan nom', $olympiad->title);
        $this->assertEquals(0, $olympiad->entry_fee);
        $this->assertSame(2, $olympiad->sections()->count(), 'update must not duplicate sections');
    }

    public function test_olympiad_can_be_duplicated_with_its_sections(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.olympiads.store'), $this->payload($this->type()));
        $original = Olympiad::firstOrFail();

        $this->actingAs($admin)->post(route('admin.olympiads.duplicate', $original->id), [
            'title' => 'Kuzgi olimpiada',
            'olympiad_start_at' => '2027-03-01T10:00',
            'olympiad_end_at' => '2027-03-01T13:00',
        ])->assertSessionHasNoErrors();

        $copy = Olympiad::where('title', 'Kuzgi olimpiada')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertSame(2, $copy->sections()->count());
        $this->assertSame(2, $original->sections()->count());
    }

    public function test_registrations_page_lists_participants(): void
    {
        $admin = $this->admin();
        $type = $this->type();
        $this->actingAs($admin)->post(route('admin.olympiads.store'), $this->payload($type));
        $olympiad = Olympiad::firstOrFail();
        $student = User::factory()->create(['role' => 'student', 'first_name' => 'Ali', 'last_name' => 'Valiyev']);
        $olympiad->registrations()->forceCreate([
            'user_id' => $student->id, 'original_price' => 15000, 'final_price' => 15000, 'status' => 'confirmed',
        ]);

        $this->actingAs($admin)->get(route('admin.olympiads.registrations', $olympiad->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Olympiad/Registrations')
                ->where('registrations.data.0.user_name', 'Ali Valiyev')
                ->where('registrations.data.0.status', 'confirmed')->where('counts.confirmed', 1));
    }

    /** QA-A-12: olympiad_types was empty unless OlympiadSeeder had been run by hand */
    public function test_create_page_offers_math_and_physics_types(): void
    {
        $this->actingAs($this->admin())->get(route('admin.olympiads.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('types', 2)
                ->where('types', fn ($types) => collect($types)->pluck('name')->sort()->values()->all() === ['math', 'physics'])
                ->has('stages'));

        // idempotent
        $this->actingAs($this->admin())->get(route('admin.olympiads.create'));
        $this->assertSame(2, OlympiadType::count());
    }

    public function test_olympiad_form_errors_name_the_fields_in_uzbek(): void
    {
        $this->actingAs($this->admin())->post(route('admin.olympiads.store'), [])
            ->assertSessionHasErrors(['olympiad_type_id' => "Olimpiada turi to'ldirilishi shart."]);
    }
}
