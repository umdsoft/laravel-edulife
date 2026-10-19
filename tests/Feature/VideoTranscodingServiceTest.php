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
