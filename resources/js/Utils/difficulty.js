// The one difficulty scale (docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §2.3) — twin of app/Support/Difficulty.php.
//   exam bank: levels 1..5 · course tests and battles: easy | medium | hard
//   mapping: 1–2 = easy, 3 = medium, 4–5 = hard (both directions)

export const LEVELS = [1, 2, 3, 4, 5];
export const BANDS = ['easy', 'medium', 'hard'];

export const LEVEL_NAMES = { 1: 'juda oson', 2: 'oson', 3: "o'rta", 4: 'qiyin', 5: 'juda qiyin' };
export const LEVEL_LABELS = Object.fromEntries(LEVELS.map((l) => [l, `${l} — ${LEVEL_NAMES[l]}`]));
export const BAND_LABELS = { easy: 'Oson', medium: "O'rta", hard: 'Qiyin' };
export const BAND_LEVELS = { easy: [1, 2], medium: [3], hard: [4, 5] };
export const BAND_DEFAULT_LEVEL = { easy: 2, medium: 3, hard: 4 };

/** [{ value, label }] for a <Select> of bank levels. */
export const LEVEL_OPTIONS = LEVELS.map((value) => ({ value, label: LEVEL_LABELS[value] }));
/** [{ value, label }] for a <Select> / radio group of course-test bands. */
export const BAND_OPTIONS = BANDS.map((value) => ({ value, label: BAND_LABELS[value] }));

export const isLevel = (v) => (typeof v === 'number' || (typeof v === 'string' && /^\s*[1-5]\s*$/.test(v))) && LEVELS.includes(Number(v));
export const isBand = (v) => BANDS.includes(v);

export const levelLabel = (level) => (isLevel(level) ? LEVEL_LABELS[Number(level)] : null);
export const bandLabel = (band) => BAND_LABELS[band] || null;

/** 1..5 → easy | medium | hard (null for anything else). */
export function bandOf(level) {
    if (!isLevel(level)) return null;
    const l = Number(level);
    return l <= 2 ? 'easy' : l === 3 ? 'medium' : 'hard';
}

/** easy | medium | hard → [levels]. */
export const levelsOf = (band) => BAND_LEVELS[band] || [];

/** Badge colour of a level or a band. */
export function difficultyVariant(value) {
    const band = isBand(value) ? value : bandOf(value);
    return { easy: 'success', medium: 'warning', hard: 'danger' }[band] || 'neutral';
}

/** Sum per-level counts {1: n, …} into bands {easy, medium, hard}. */
export function bandCounts(levelCounts = {}) {
    return Object.fromEntries(BANDS.map((b) => [b, levelsOf(b).reduce((s, l) => s + Number(levelCounts?.[l] ?? 0), 0)]));
}

/** "1: 4 · 2: 0 · 3: 12 · 4: 3 · 5: 1" */
export const levelCountsSummary = (counts = {}) => LEVELS.map((l) => `${l}: ${Number(counts?.[l] ?? 0)}`).join(' · ');

/** "oson 5 · o'rta 10 · qiyin 5" */
export const distributionSummary = (dist = {}) => BANDS
    .filter((b) => Number(dist?.[b] ?? 0) > 0)
    .map((b) => `${BAND_LABELS[b].toLowerCase()} ${Number(dist[b])}`)
    .join(' · ');

/**
 * Calibration hint: "ustoz: qiyin · amalda: 82% yechgan". `calibration` comes from the server
 * ({ rate, attempts, band, level?, mismatch }); null when there is not enough data.
 */
export function calibrationText(calibration) {
    if (!calibration || calibration.rate === null || calibration.rate === undefined) return null;
    const pct = Math.round(Number(calibration.rate) * 100);
    return `amalda: ${pct}% yechgan (${Math.round(Number(calibration.attempts) || 0)} ta javob)`;
}
