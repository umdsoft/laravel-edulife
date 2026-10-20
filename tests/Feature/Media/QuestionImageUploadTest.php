<?php

namespace Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** POST /media/question-images — the "Rasm" button of the editors. */
class QuestionImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public static function png(int $w = 40, int $h = 30, string $type = 'png'): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 30, 120, 200));
        ob_start();
        match ($type) {
            'jpg' => imagejpeg($img),
            'gif' => imagegif($img),
            'webp' => imagewebp($img),
            default => imagepng($img),
        };

        return (string) ob_get_clean();
    }

    private function upload(?User $user, UploadedFile $file)
    {
        $req = $user ? $this->actingAs($user) : $this;

        return $req->postJson(route('media.question-images.store'), ['image' => $file]);
    }

    public function test_teacher_uploads_png_and_gets_a_webp_url(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $res = $this->upload($teacher, UploadedFile::fake()->createWithContent('chizma.png', self::png()))->assertCreated();

        $url = $res->json('url');
        $this->assertMatchesRegularExpression('#^/storage/questions/\d{4}/\d{2}/[0-9a-f-]{36}\.webp$#', $url);
        $this->assertSame("![chizma]({$url})", $res->json('markdown'));
        $path = substr($url, strlen('/storage/'));
        Storage::disk('public')->assertExists($path);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('public')->get($path))[2]);
        $this->assertSame([40, 30], [$res->json('width'), $res->json('height')]);
    }

    public function test_jpg_webp_and_gif_are_accepted_and_admin_may_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        foreach (['jpg', 'webp', 'gif'] as $type) {
            $this->upload($admin, UploadedFile::fake()->createWithContent("a.{$type}", self::png(20, 20, $type)))->assertCreated();
        }
        $this->assertCount(3, Storage::disk('public')->allFiles('questions'));
    }

    public function test_large_images_are_scaled_to_1600_px(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $res = $this->upload($teacher, UploadedFile::fake()->createWithContent('big.png', self::png(3200, 1000)))->assertCreated();

        $this->assertSame([1600, 500], [$res->json('width'), $res->json('height')]);
        $stored = getimagesizefromstring(Storage::disk('public')->get(substr($res->json('url'), 9)));
        $this->assertSame([1600, 500], [$stored[0], $stored[1]]);
    }

    public function test_svg_and_disguised_files_are_rejected(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->upload($teacher, UploadedFile::fake()->createWithContent('x.svg', $svg))->assertStatus(422)->assertJsonValidationErrors('image');
        // SVG / HTML renamed to .png: the content decides
        $this->upload($teacher, UploadedFile::fake()->createWithContent('x.png', $svg))->assertStatus(422)->assertJsonValidationErrors('image');
        $this->upload($teacher, UploadedFile::fake()->createWithContent('x.png', '<html><script>alert(1)</script></html>'))->assertStatus(422);
        $this->upload($teacher, UploadedFile::fake()->createWithContent('x.pdf', '%PDF-1.4 fake'))->assertStatus(422);
        $this->assertSame([], Storage::disk('public')->allFiles('questions'));
    }

    public function test_files_over_5_mb_are_rejected(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->upload($teacher, UploadedFile::fake()->create('big.png', 5121, 'image/png'))
            ->assertStatus(422)->assertJsonValidationErrors(['image' => '5 MB']);
    }

    public function test_only_teachers_and_admins_may_upload(): void
    {
        $file = fn () => UploadedFile::fake()->createWithContent('a.png', self::png());
        $this->upload(null, $file())->assertUnauthorized();
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->upload($student, $file())->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles('questions'));
    }

    public function test_uploads_are_rate_limited(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $route = app('router')->getRoutes()->getByName('media.question-images.store');
        $this->assertContains('throttle:60,1', $route->gatherMiddleware());
    }
}
