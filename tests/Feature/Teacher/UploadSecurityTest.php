<?php

namespace Tests\Feature\Teacher;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_php_attachment_is_rejected(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)));

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.attachments.store', $lesson), [
                'file' => UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_pdf_attachment_is_stored_with_server_generated_name(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)));

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.attachments.store', $lesson), [
                'file' => UploadedFile::fake()->createWithContent('../../evil.pdf', "%PDF-1.4\n%test\n"),
            ])
            ->assertSessionHasNoErrors();

        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('#^lessons/[0-9a-f-]+/attachments/[0-9a-f-]{36}\.pdf$#', $files[0]);
    }

    public function test_foreign_teacher_cannot_upload_attachment(): void
    {
        $lesson = $this->lesson($this->module($this->course($this->teacher())));

        $this->actingAs($this->teacher())
            ->post(route('teacher.lessons.attachments.store', $lesson), [
                'file' => UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n"),
            ])
            ->assertForbidden();
    }

    public function test_chunk_upload_rejects_path_traversal_and_non_video_names(): void
    {
        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)));

        $this->actingAs($teacher)
            ->postJson(route('teacher.lessons.video.upload-chunk', $lesson), [
                'chunk' => UploadedFile::fake()->create('blob', 10),
                'chunk_index' => 0,
                'total_chunks' => 1,
                'upload_id' => '../../../../public',
                'filename' => 'shell.php',
            ])
            ->assertJsonValidationErrors(['upload_id', 'filename']);

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
    public function test_video_status_is_owner_only(): void
    {
        $lesson = $this->lesson($this->module($this->course($this->teacher())));

        $this->actingAs($this->teacher())
            ->getJson(route('teacher.lessons.video.status', $lesson))
            ->assertForbidden();
    }
}
