// Shared helpers for exam progress pages (student "O'sishim", guardian panel).

/** Tone for a mastery / percent value (null = no data). */
export function masteryTone(score) {
    if (score === null || score === undefined) return 'neutral';
    const n = Number(score);
    if (n < 40) return 'danger';
    if (n < 60) return 'warning';
    if (n < 80) return 'brand';
    return 'success';
}

export function masteryLabel(score) {
    if (score === null || score === undefined) return 'hali ma\'lumot yo\'q';
    return `${formatPercent(score)}`;
}

/** 62.5 -> "62,5%", null -> "—" */
export function formatPercent(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = Math.round(Number(value) * 10) / 10;
    return `${String(n).replace('.', ',')}%`;
}

/** +3.5 -> "+3,5", -2 -> "−2", 0/null -> null */
export function formatDelta(value) {
    if (value === null || value === undefined) return null;
    const n = Math.round(Number(value) * 10) / 10;
    if (!n) return '0';
    return `${n > 0 ? '+' : '−'}${String(Math.abs(n)).replace('.', ',')}`;
}

/** "2026-10-19" -> "19.10.2026" */
export function formatWeek(iso) {
    if (!iso) return '';
    const [y, m, d] = String(iso).split('-');
    return `${d}.${m}.${y}`;
}

export const SUBJECT_NAMES = { math: 'Matematika', physics: 'Fizika' };

export const REASON_LABELS = {
    weak: 'Zaif mavzu',
    improve: 'Mustahkamlash',
    new: 'Yangi mavzu',
};
