/**
 * Small presentation helpers shared by the student learning pages/components.
 * Money: "12 500 so'm" · dates: "dd.mm.yyyy" · durations: "m:ss" / "1 soat 20 daq".
 */

const nf = new Intl.NumberFormat('ru-RU'); // thin-space grouping: 12 500

export function formatNumber(value) {
    const n = Number(value);
    return Number.isFinite(n) ? nf.format(n).replace(/ /g, ' ') : '0';
}

export function formatPrice(price, isFree = false) {
    if (isFree || !Number(price)) return 'Bepul';
    return `${formatNumber(Math.round(Number(price)))} so'm`;
}

export function formatDate(value) {
    if (!value) return '';
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}.${mm}.${d.getFullYear()}`;
}

/** 75 → "1:15", 3725 → "1:02:05" */
export function formatClock(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const ss = String(s).padStart(2, '0');
    return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${ss}` : `${m}:${ss}`;
}

export function percent(value) {
    return Math.round(Number(value) || 0);
}
