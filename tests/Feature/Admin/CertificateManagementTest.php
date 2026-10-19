<?php

namespace Tests\Feature\Admin;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class CertificateManagementTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function certificate(array $attributes = []): Certificate
    {
        $student = $this->studentUser();
        $course = $this->course($this->teacher());
        $enrollment = Enrollment::forceCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'access_type' => 'free',
            'status' => 'completed',
        ]);
        $code = strtoupper(Str::random(12));

        return Certificate::forceCreate(array_merge([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'credential_id' => 'CERT-'.$code,
            'certificate_number' => 'CERT-'.$code,
            'certificate_code' => $code,
            'verification_code' => $code,
            'recipient_name' => $student->full_name,
            'course_title' => $course->title,
            'issued_at' => now(),
        ], $attributes));
    }

    public function test_index_and_show_render_with_template_name(): void
    {
        $template = CertificateTemplate::forceCreate([
            'name' => 'Klassik',
            'slug' => 'klassik',
            'pdf_template_path' => 'certificate-templates/klassik.pdf',
            'placeholders' => ['recipient_name'],
        ]);
        $certificate = $this->certificate(['template_id' => $template->id]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.certificates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Certificates/Index')
                ->where('certificates.data.0.template_name', 'Klassik'));

        $this->actingAs($admin)
            ->get(route('admin.certificates.show', $certificate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Certificates/Show')
                ->where('certificate.template_name', 'Klassik'));
    }

    public function test_regenerate_rebuilds_the_pdf(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        $this->actingAs($this->admin())
            ->post(route('admin.certificates.regenerate', $certificate))
            ->assertRedirect()
            ->assertSessionHas('success');

        $path = $certificate->fresh()->pdf_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_download_generates_a_missing_pdf(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        $this->actingAs($this->admin())
            ->get(route('admin.certificates.download', $certificate))
            ->assertOk()
            ->assertDownload();

        $this->assertNotNull($certificate->fresh()->pdf_path);
    }
}
