<?php

namespace Tests\Feature\Admin;

use App\Models\Olympiad;
use App\Models\OlympiadType;
use App\Models\User;
use Database\Seeders\OlympiadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * QA2-A-02: the form says "times are local", but a 00:26 entered in Tashkent was
 * stored and compared as 00:26 UTC (5 hours late). The app now runs on Tashkent
 * time, so a datetime-local value is Tashkent time end to end.
 */
class OlympiadTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_application_runs_on_tashkent_time(): void
    {
        $this->assertSame('Asia/Tashkent', config('app.timezone'));
        $this->assertSame('Asia/Tashkent', now()->timezoneName);
        $this->assertSame(5 * 3600, now()->getOffset());
    }

    public function test_local_form_times_are_kept_as_tashkent_time(): void
    {
        // 00:30 in Tashkent = 19:30 UTC of the previous day
        Carbon::setTestNow(Carbon::parse('2026-10-20 00:30', 'Asia/Tashkent'));

        $this->seed(OlympiadSeeder::class);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.olympiads.store'), [
            'title' => 'Vaqt olimpiadasi',
            'olympiad_type_id' => OlympiadType::where('name', 'math')->value('id'),
            'registration_start_at' => '2026-10-20T00:26',
            'registration_end_at' => '2026-10-20T01:56',
            'olympiad_start_at' => '2026-10-20T01:57',
            'olympiad_end_at' => '2026-10-20T03:00',
            'create_default_sections' => true,
        ])->assertSessionHasNoErrors();

        $olympiad = Olympiad::firstOrFail();

        // stored as the wall-clock time the admin typed
        $this->assertStringStartsWith('2026-10-20 00:26', (string) DB::table('olympiads')->value('registration_start_at'));
        $this->assertSame('2026-10-19 19:26', $olympiad->registration_start_at->copy()->utc()->format('Y-m-d H:i'));

        // registration opened 4 minutes ago, not in 5 hours
        $this->assertTrue($olympiad->registration_start_at->isPast());
        $this->assertTrue($olympiad->registration_end_at->isFuture());

        // the edit form shows the same local time again
        $this->actingAs($admin)->get(route('admin.olympiads.edit', $olympiad->id))
            ->assertInertia(fn (Assert $p) => $p->where('form.registration_start_at', '2026-10-20T00:26'));
    }
}
