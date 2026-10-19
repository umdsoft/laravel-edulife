/**
 * HLS playlists need hls.js (or native HLS on Safari); uploaded mp4/webm files
 * play directly in the <video> element.
 */
export function isHlsSource(src) {
    if (!src || typeof src !== 'string') return false;
    const path = src.split(/[?#]/)[0].toLowerCase();
    return path.endsWith('.m3u8');
}
