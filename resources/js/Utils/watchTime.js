/**
 * Watch-time accounting for the lesson video player (pure, framework-free).
 *
 * Only real playback counts: the accumulator is fed every `timeupdate` with the
 * media position and a monotonic wall clock. Wall time between two ticks is
 * credited only when the media advanced the way continuous playback would —
 * a seek / skip (position jump), a stall, a paused or suspended tab (long gap)
 * credits nothing. `interrupt()` (pause, seeking, waiting, ended) starts a new
 * segment so the time across the interruption is never counted.
 *
 * Wall-clock seconds are counted (not media seconds): the server caps every
 * student at the real elapsed time, and the daily mission means "spend 30 minutes".
 */

/** Send a chunk once this much played time is pending. */
export const WATCH_FLUSH_SECONDS = 30;
/** Never report more than this in one request (mirrors the server limit). */
export const WATCH_MAX_CHUNK_SECONDS = 120;

const DEFAULTS = {
    maxChunk: WATCH_MAX_CHUNK_SECONDS,
    maxGapMs: 5000, // timeupdate fires every 15–250 ms; a longer gap is a suspended tab, not watching
    maxRate: 2, // fastest playback rate the player offers
    seekSlack: 1, // media seconds tolerated on top of maxRate before a move counts as a seek
};

export function createWatchAccumulator(options = {}) {
    const opts = { ...DEFAULTS, ...options };
    let lastMedia = null;
    let lastWall = null;
    let pending = 0; // played seconds not reported yet (fractional)
    let startPosition = null;
    let endPosition = null;
    let seq = 0;

    return {
        /** Feed one timeupdate; returns the seconds credited by this tick. */
        tick(mediaTime, wallMs) {
            if (!Number.isFinite(mediaTime) || !Number.isFinite(wallMs)) return 0;
            let added = 0;
            if (lastMedia !== null) {
                const wallDelta = wallMs - lastWall;
                const mediaDelta = mediaTime - lastMedia;
                const continuous = mediaDelta > 0 && mediaDelta <= (wallDelta / 1000) * opts.maxRate + opts.seekSlack;
                if (wallDelta > 0 && wallDelta <= opts.maxGapMs && continuous) {
                    added = wallDelta / 1000;
                    pending += added;
                    if (startPosition === null) startPosition = lastMedia;
                    endPosition = mediaTime;
                }
            }
            lastMedia = mediaTime;
            lastWall = wallMs;
            return added;
        },

        /** Pause / seeking / buffering / ended: the next tick starts a fresh segment. */
        interrupt() {
            lastMedia = null;
            lastWall = null;
        },

        get pending() {
            return pending;
        },

        shouldFlush(threshold = WATCH_FLUSH_SECONDS) {
            return pending >= threshold;
        },

        /**
         * Take whole pending seconds (at most maxChunk) as a request payload;
         * the fractional remainder stays. Returns null when under one second.
         */
        take() {
            const seconds = Math.min(Math.floor(pending), opts.maxChunk);
            if (seconds < 1) return null;
            pending -= seconds;
            seq += 1;
            const start = startPosition ?? endPosition ?? 0;
            const chunk = {
                seq,
                duration: seconds,
                start_position: Math.max(0, Math.floor(start)),
                end_position: Math.max(0, Math.floor(endPosition ?? start)),
            };
            startPosition = pending > 0 ? endPosition : null;
            return chunk;
        },

        /** A send failed for a transient reason: put its seconds back (a new seq is used). */
        restore(chunk) {
            if (chunk && chunk.duration > 0) pending += chunk.duration;
        },
    };
}

/** A failed send worth retrying: network error, server error or rate limit (not 4xx validation / auth). */
export function isRetryableError(error) {
    const status = error?.response?.status;
    return !status || status >= 500 || status === 429;
}

export function makeWatchSessionId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

function csrfToken(doc) {
    return doc?.querySelector?.('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Fire-and-forget send that survives page hide / unload: fetch keepalive
 * (custom headers allowed), falling back to sendBeacon with a form body.
 * Returns true when the browser accepted the request.
 */
export function sendWatchChunkKeepalive(url, payload, env = {}) {
    const doc = env.document ?? (typeof document !== 'undefined' ? document : undefined);
    const fetchFn = env.fetch ?? (typeof fetch !== 'undefined' ? fetch : undefined);
    const nav = env.navigator ?? (typeof navigator !== 'undefined' ? navigator : undefined);
    const token = csrfToken(doc);

    if (typeof fetchFn === 'function') {
        try {
            fetchFn(url, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(payload),
            }).catch(() => {});
            return true;
        } catch {
            // fall through to sendBeacon
        }
    }

    if (nav && typeof nav.sendBeacon === 'function' && typeof FormData !== 'undefined') {
        const form = new FormData();
        form.append('_token', token);
        Object.entries(payload).forEach(([k, v]) => {
            if (v !== null && v !== undefined) form.append(k, String(v));
        });
        return nav.sendBeacon(url, form);
    }
    return false;
}
