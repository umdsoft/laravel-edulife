<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** QA2-S-12: 403/404 showed Laravel's raw English pages. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_unknown_page_is_an_uzbek_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (Assert $p) => $p->component('Error')->where('status', 404));
    }

    public function test_someone_elses_certificate_is_an_uzbek_403_page(): void
    {
        Storage::fake('public');
        $owner = $this->studentUser();
        $enrollment = Enrollment::forceCreate([
            'user_id' => $owner->id, 'course_id' => $this->course($this->teacher())->id, 'access_type' => 'free', 'status' => 'completed',
        ]);
        $certificate = app(CertificateService::class)->generate($enrollment);

        $this->actingAs($this->studentUser())->get(route('student.certificates.show', $certificate))
            ->assertForbidden()
            ->assertInertia(fn (Assert $p) => $p->component('Error')->where('status', 403));
    }

    public function test_json_callers_still_get_json(): void
    {
        $this->getJson('/this-page-does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }
}
