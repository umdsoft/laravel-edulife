import { describe, expect, it, vi } from 'vitest';
import {
    createWatchAccumulator, isRetryableError, sendWatchChunkKeepalive, WATCH_MAX_CHUNK_SECONDS,
} from '../watchTime';

/** Simulate continuous playback: timeupdate every `stepMs` at `rate`. */
function play(acc, { fromMedia, fromWall, seconds, rate = 1, stepMs = 250 }) {
    let media = fromMedia;
    let wall = fromWall;
    acc.tick(media, wall);
    for (let t = 0; t < seconds * 1000; t += stepMs) {
        wall += stepMs;
        media += (stepMs / 1000) * rate;
        acc.tick(media, wall);
    }
    return { media, wall };
}

describe('createWatchAccumulator', () => {
    it('counts continuous playback in wall-clock seconds', () => {
        const acc = createWatchAccumulator();
        play(acc, { fromMedia: 0, fromWall: 0, seconds: 30 });
        expect(acc.pending).toBeCloseTo(30, 5);
        expect(acc.shouldFlush(30)).toBe(true);
    });

    it('counts wall time (not media time) at faster playback rates', () => {
        const acc = createWatchAccumulator();
        play(acc, { fromMedia: 0, fromWall: 0, seconds: 20, rate: 2 });
        expect(acc.pending).toBeCloseTo(20, 5);
    });

    it('does not count a forward seek / skip', () => {
        const acc = createWatchAccumulator();
        acc.tick(10, 0);
        acc.tick(10.25, 250);
        expect(acc.tick(300, 500)).toBe(0); // jumped 290 s in 250 ms
        expect(acc.pending).toBeCloseTo(0.25, 5);
    });

    it('does not count a backward seek or a frozen position', () => {
        const acc = createWatchAccumulator();
        acc.tick(50, 0);
        expect(acc.tick(20, 250)).toBe(0);
        expect(acc.tick(20, 500)).toBe(0);
        expect(acc.pending).toBe(0);
    });

    it('does not count time across a pause / interruption', () => {
        const acc = createWatchAccumulator();
        const { media, wall } = play(acc, { fromMedia: 0, fromWall: 0, seconds: 10 });
        acc.interrupt();
        // resumes 60 s later: the first tick only sets the new baseline
        expect(acc.tick(media, wall + 60_000)).toBe(0);
        play(acc, { fromMedia: media, fromWall: wall + 60_000, seconds: 5 });
        expect(acc.pending).toBeCloseTo(15, 5);
    });

    it('ignores long gaps between ticks (suspended tab, stall)', () => {
        const acc = createWatchAccumulator();
        acc.tick(0, 0);
        expect(acc.tick(0.5, 10_000)).toBe(0);
        expect(acc.pending).toBe(0);
    });

    it('ignores non-finite input', () => {
        const acc = createWatchAccumulator();
        expect(acc.tick(NaN, 0)).toBe(0);
        expect(acc.tick(1, Infinity)).toBe(0);
        expect(acc.pending).toBe(0);
    });

    it('takes whole seconds with positions and an increasing seq, keeping the remainder', () => {
        const acc = createWatchAccumulator();
        play(acc, { fromMedia: 100, fromWall: 0, seconds: 30.5 });
        const first = acc.take();
        expect(first).toEqual({ seq: 1, duration: 30, start_position: 100, end_position: 130 });
        expect(acc.pending).toBeCloseTo(0.5, 5);
        expect(acc.take()).toBeNull(); // under one second

        play(acc, { fromMedia: 130.5, fromWall: 31_000, seconds: 10 });
        const second = acc.take();
        expect(second.seq).toBe(2);
        expect(second.duration).toBe(10);
        expect(second.start_position).toBeGreaterThanOrEqual(130);
    });

    it('caps one chunk at the per-request maximum and leaves the rest pending', () => {
        const acc = createWatchAccumulator();
        play(acc, { fromMedia: 0, fromWall: 0, seconds: 200 });
        expect(acc.take().duration).toBe(WATCH_MAX_CHUNK_SECONDS);
        expect(acc.pending).toBeCloseTo(200 - WATCH_MAX_CHUNK_SECONDS, 5);
    });

    it('restores the seconds of a failed send', () => {
        const acc = createWatchAccumulator();
        play(acc, { fromMedia: 0, fromWall: 0, seconds: 30 });
        const chunk = acc.take();
        acc.restore(chunk);
        expect(acc.pending).toBeCloseTo(30, 5);
        expect(acc.take().seq).toBe(2);
    });
});

describe('isRetryableError', () => {
    it('retries network errors, 5xx and 429 only', () => {
        expect(isRetryableError(new Error('Network Error'))).toBe(true);
        expect(isRetryableError({ response: { status: 503 } })).toBe(true);
        expect(isRetryableError({ response: { status: 429 } })).toBe(true);
        expect(isRetryableError({ response: { status: 422 } })).toBe(false);
        expect(isRetryableError({ response: { status: 419 } })).toBe(false);
        expect(isRetryableError({ response: { status: 404 } })).toBe(false);
    });
});

describe('sendWatchChunkKeepalive', () => {
    const doc = { querySelector: () => ({ getAttribute: () => 'tok123' }) };

    it('uses fetch keepalive with the CSRF header', () => {
        const fetch = vi.fn(() => Promise.resolve({}));
        expect(sendWatchChunkKeepalive('/log', { duration: 5 }, { fetch, document: doc })).toBe(true);
        const [url, init] = fetch.mock.calls[0];
        expect(url).toBe('/log');
        expect(init.keepalive).toBe(true);
        expect(init.headers['X-CSRF-TOKEN']).toBe('tok123');
        expect(JSON.parse(init.body)).toEqual({ duration: 5 });
    });

    it('falls back to sendBeacon with the token in the form body', () => {
        const fetch = vi.fn(() => { throw new TypeError('keepalive unsupported'); });
        const sendBeacon = vi.fn(() => true);
        expect(sendWatchChunkKeepalive('/log', { duration: 5, quality: null }, { fetch, document: doc, navigator: { sendBeacon } })).toBe(true);
        const form = sendBeacon.mock.calls[0][1];
        expect(form.get('_token')).toBe('tok123');
        expect(form.get('duration')).toBe('5');
        expect(form.has('quality')).toBe(false);
    });
});
