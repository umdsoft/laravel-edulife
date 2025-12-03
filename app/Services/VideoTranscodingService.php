<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class VideoTranscodingService
{
    protected array $qualities = [
        '360p' => ['width' => 640, 'height' => 360, 'bitrate' => '800k'],
        '480p' => ['width' => 854, 'height' => 480, 'bitrate' => '1400k'],
        '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => '2800k'],
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '5000k'],
    ];
    
    /**
     * Transcode video to HLS format with multiple qualities
     */
    public function transcode(Video $video): void
    {
        try {
            $video->update(['status' => 'processing', 'transcoding_progress' => 0]);
            
            $inputPath = Storage::disk('public')->path($video->original_path);
            $outputDir = Storage::disk('public')->path('videos/' . $video->lesson_id . '/hls');
            
            // Create output directory
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Get video duration
            $duration = $this->getVideoDuration($inputPath);
            $video->update(['duration' => $duration]);
            
            // Update lesson duration
            $video->lesson->update(['duration' => $duration]);
            
            // Get video resolution to determine max quality
            $resolution = $this->getVideoResolution($inputPath);
            $maxHeight = $resolution['height'];
            
            $availableQualities = [];
            $progress = 0;
            $qualityCount = 0;
            
            // Count applicable qualities
            foreach ($this->qualities as $name => $quality) {
                if ($quality['height'] <= $maxHeight) {
                    $qualityCount++;
                }
            }
            
            // Transcode each quality
            foreach ($this->qualities as $name => $quality) {
                if ($quality['height'] > $maxHeight) {
                    continue; // Skip qualities higher than source
                }
                
                $qualityDir = $outputDir . '/' . $name;
                if (!file_exists($qualityDir)) {
                    mkdir($qualityDir, 0755, true);
                }
                
                // FFmpeg command for HLS
                $command = sprintf(
                    'ffmpeg -i %s -vf scale=%d:%d -c:v libx264 -preset fast -crf 22 ' .
                    '-c:a aac -b:a 128k -b:v %s -maxrate %s -bufsize %s ' .
                    '-hls_time 10 -hls_list_size 0 -hls_segment_filename %s/segment_%%03d.ts ' .
                    '-f hls %s/playlist.m3u8 -y 2>&1',
                    escapeshellarg($inputPath),
                    $quality['width'],
                    $quality['height'],
                    $quality['bitrate'],
                    $quality['bitrate'],
                    str_replace('k', '000', $quality['bitrate']) * 2,
                    escapeshellarg($qualityDir),
                    escapeshellarg($qualityDir)
                );
                
                $result = Process::run($command);
                
                if ($result->successful()) {
                    $availableQualities[] = $name;
                }
                
                // Update progress
                $progress += (100 / $qualityCount);
                $video->update(['transcoding_progress' => min(95, round($progress))]);
            }
            
            // Create master playlist
            $this->createMasterPlaylist($outputDir, $availableQualities);
            
            // Update video record
            $video->update([
                'status' => 'completed',
                'transcoding_progress' => 100,
                'qualities' => $availableQualities,
                'hls_path' => 'videos/' . $video->lesson_id . '/hls/master.m3u8',
            ]);
            
        } catch (\Exception $e) {
            $video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get video duration in seconds
     */
    private function getVideoDuration(string $path): int
    {
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellarg($path)
        );
        
        $result = Process::run($command);
        
        return (int) round((float) trim($result->output()));
    }
    
    /**
     * Get video resolution
     */
    private function getVideoResolution(string $path): array
    {
        $command = sprintf(
            'ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 %s',
            escapeshellarg($path)
        );
        
        $result = Process::run($command);
        $parts = explode('x', trim($result->output()));
        
        return [
            'width' => (int) ($parts[0] ?? 1920),
            'height' => (int) ($parts[1] ?? 1080),
        ];
    }
    
    /**
     * Create HLS master playlist
     */
    private function createMasterPlaylist(string $outputDir, array $qualities): void
    {
        $content = "#EXTM3U\n#EXT-X-VERSION:3\n\n";
        
        $bandwidths = [
            '360p' => 800000,
            '480p' => 1400000,
            '720p' => 2800000,
            '1080p' => 5000000,
        ];
        
        $resolutions = [
            '360p' => '640x360',
            '480p' => '854x480',
            '720p' => '1280x720',
            '1080p' => '1920x1080',
        ];
        
        foreach ($qualities as $quality) {
            $content .= sprintf(
                "#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%s,NAME=\"%s\"\n%s/playlist.m3u8\n\n",
                $bandwidths[$quality],
                $resolutions[$quality],
                $quality,
                $quality
            );
        }
        
        file_put_contents($outputDir . '/master.m3u8', $content);
    }
}
