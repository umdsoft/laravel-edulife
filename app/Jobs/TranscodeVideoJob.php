<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoTranscodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 3600; // 1 hour
    public int $tries = 3;
    
    public function __construct(
        public Video $video
    ) {}
    
    public function handle(VideoTranscodingService $service): void
    {
        $service->transcode($this->video);
    }
    
    public function failed(\Throwable $exception): void
    {
        $this->video->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
