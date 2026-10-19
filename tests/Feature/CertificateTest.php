<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
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

    /**
     * QA2-S-03: certificates were issued without a PDF and "Qayta generatsiya"
     * wrote a 104-byte text file named .pdf.
     */
    public function test_a_real_pdf_is_made_when_issued_and_on_download(): void
    {
        Storage::fake('public');
        $enrollment = $this->enrollment();
        $certificate = app(CertificateService::class)->generate($enrollment)->fresh();

        $this->assertNotNull($certificate->pdf_path);
        $pdf = Storage::disk('public')->get($certificate->pdf_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));

        // an old placeholder file is replaced by a real PDF on download
        Storage::disk('public')->put($certificate->pdf_path, 'Certificate: placeholder');
        $response = $this->actingAs($enrollment->user)->get(route('student.certificates.download', $certificate))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->streamedContent());

        // a missing file is built again too, also for the admin
        Storage::disk('public')->delete($certificate->fresh()->pdf_path);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->assertStringStartsWith('%PDF', $this->actingAs($admin)->get(route('admin.certificates.download', $certificate))->assertOk()->streamedContent());
    }

    /**
     * QA2-S-08 / QA2-A-07: the verify page called the verification code the
     * "certificate number", and the admin page showed "19 October 2026".
     */
    public function test_number_code_and_date_read_the_same_everywhere(): void
    {
        Storage::fake('public');
        $certificate = app(CertificateService::class)->generate($this->enrollment())->fresh();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->get(route('certificate.verify', $certificate->verification_code))
            ->assertInertia(fn (Assert $page) => $page
                ->where('certificate.number', $certificate->certificate_number)
                ->where('certificate.code', $certificate->verification_code)
                ->where('certificate.issued_at', $certificate->issued_at->format('d.m.Y')));

        $this->actingAs($admin)->get(route('admin.certificates.show', $certificate))
            ->assertInertia(fn (Assert $page) => $page->where('certificate.issued_at', $certificate->issued_at->format('d.m.Y')));
    }

    public function test_unknown_code_is_not_found(): void
    {
        $this->get(route('certificate.verify', 'NOPE12345678'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('found', false));
    }
}
