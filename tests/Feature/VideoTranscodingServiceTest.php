<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Services\VideoTranscodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class VideoTranscodingServiceTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    protected function tearDown(): void
    {
        VideoTranscodingService::flushAvailability();
        parent::tearDown();
    }

    /** QA-T-09: without ffmpeg / a queue worker the video stayed "processing 0%" forever */
    public function test_without_ffmpeg_the_uploaded_file_is_published_as_is(): void
    {
        Storage::fake('public');
        Process::fake(['*' => Process::result('', 'not found', 1)]);
        VideoTranscodingService::flushAvailability();

        $teacher = $this->teacher();
        $lesson = $this->lesson($this->module($this->course($teacher)), ['type' => 'video']);

        $this->actingAs($teacher)->post(route('teacher.lessons.video.upload', $lesson), [
            'video' => \Illuminate\Http\UploadedFile::fake()->create('dars.mp4', 100, 'video/mp4'),
        ])->assertOk()->assertJsonPath('video.status', 'completed');

        $video = Video::where('lesson_id', $lesson->id)->firstOrFail();
        $this->assertSame('completed', $video->status);
        $this->assertNull($video->hls_path);
        // the player gets the original file
        $this->assertStringEndsWith('.mp4', $video->url);
        $this->assertStringContainsString('ffmpeg', $this->actingAs($teacher)
            ->getJson(route('teacher.lessons.video.status', $lesson))->json('notice'));
    }

    public function test_video_url_is_the_hls_playlist_once_transcoded(): void
    {
        $video = new Video(['status' => 'completed', 'hls_path' => 'videos/1/hls/master.m3u8', 'original_path' => 'videos/1/a.mp4', 'qualities' => ['360p']]);
        $this->assertStringEndsWith('master.m3u8', $video->url);

        $video->status = 'processing';
        $this->assertStringEndsWith('a.mp4', $video->url);
    }

    public function test_transcode_uses_long_timeout_and_stores_lesson_video_duration(): void
    {
        Storage::fake('public');
        Process::fake([
            '*format=duration*' => Process::result('125.4'),
            '*stream=width,height*' => Process::result('640x360'),
            'ffmpeg *' => Process::result(''),
        ]);

        $lesson = $this->lesson($this->module($this->course($this->teacher())), ['type' => 'video', 'video_duration' => 0]);
        $video = Video::forceCreate([
            'lesson_id' => $lesson->id,
            'original_path' => 'videos/original.mp4',
            'original_name' => 'original.mp4',
            'size' => 1024,
            'mime_type' => 'video/mp4',
        ]);

        app(VideoTranscodingService::class)->transcode($video);

        Process::assertRan(fn (PendingProcess $process) => str_starts_with($process->command, 'ffmpeg')
            && $process->timeout === 3600);

        $this->assertSame(125, $lesson->fresh()->video_duration);
        $video->refresh();
        $this->assertSame('completed', $video->status);
        $this->assertSame(['360p'], $video->qualities);
    }
}
