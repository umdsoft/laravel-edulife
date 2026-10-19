import { describe, expect, it } from 'vitest';
import { isHlsSource } from '../video';

describe('isHlsSource', () => {
    it('detects HLS playlists', () => {
        expect(isHlsSource('/storage/videos/1/hls/master.m3u8')).toBe(true);
        expect(isHlsSource('https://cdn.example.com/a/MASTER.M3U8?token=1')).toBe(true);
    });

    it('treats uploaded files and empty values as direct sources', () => {
        expect(isHlsSource('/storage/videos/1/original/a.mp4')).toBe(false);
        expect(isHlsSource('/storage/videos/1/original/a.webm#t=10')).toBe(false);
        expect(isHlsSource(null)).toBe(false);
        expect(isHlsSource('')).toBe(false);
    });
});
