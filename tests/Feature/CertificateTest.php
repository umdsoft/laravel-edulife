<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function enrollment(): Enrollment
    {
        return Enrollment::forceCreate([
            'user_id' => $this->studentUser()->id,
            'course_id' => $this->course($this->teacher())->id,
            'access_type' => 'free',
            'status' => 'completed',
        ]);
    }

    public function test_certificate_is_generated_once_and_verifiable(): void
    {
        Storage::fake('public');
        $enrollment = $this->enrollment();
        $service = app(CertificateService::class);

        $certificate = $service->generate($enrollment);
        $again = $service->generate($enrollment->fresh());

        $this->assertTrue($certificate->is($again), 'second call must not create a duplicate');
        $this->assertNotEmpty($certificate->certificate_code);
        $this->assertSame($certificate->certificate_code, $certificate->verification_code);
        $this->assertStringContainsString('/verify/'.$certificate->verification_code, $certificate->fresh()->verification_url);

        $this->get(route('certificate.verify', $certificate->verification_code))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Certificate/Verify')->where('found', true));
    }

    public function test_unknown_code_is_not_found(): void
    {
        $this->get(route('certificate.verify', 'NOPE12345678'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('found', false));
    }
}
