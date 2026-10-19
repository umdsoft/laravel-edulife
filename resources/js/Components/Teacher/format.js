// Small presentation helpers shared by the teacher panel (dates dd.mm.yyyy, numbers, sizes).
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

const pad = (n) => String(n).padStart(2, '0');

const toDate = (value) => {
    if (!value) return null;
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
};

/** 05.03.2026 */
export function formatDate(value, fallback = '—') {
    const d = toDate(value);
    return d ? `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}` : fallback;
}

/** 05.03.2026, 14:30 */
export function formatDateTime(value, fallback = '—') {
    const d = toDate(value);
    return d ? `${formatDate(d)}, ${pad(d.getHours())}:${pad(d.getMinutes())}` : fallback;
}

/** "3 kun oldin" */
export function timeAgo(value, fallback = '') {
    const d = toDate(value);
    if (!d) return fallback;
    try {
        return formatDistanceToNow(d, { addSuffix: true, locale: uz });
    } catch {
        return formatDate(d);
    }
}

/** 12 345 (non-breaking spaces) */
export function formatNumber(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return '0';
    return new Intl.NumberFormat('ru-RU').format(n).replace(/\s/g, ' ');
}

/** 12 500 so'm */
export function formatMoney(value) {
    return `${formatNumber(value)} so'm`;
}

/** 1.5 MB */
export function formatSize(bytes) {
    const n = Number(bytes);
    if (!Number.isFinite(n) || n <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(n) / Math.log(k)), sizes.length - 1);
    return `${parseFloat((n / k ** i).toFixed(1))} ${sizes[i]}`;
}

/** 75 → "1:15", 3725 → "1:02:05" */
export function formatTimestamp(seconds) {
    const total = Math.max(0, Math.floor(Number(seconds) || 0));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return h ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
}

export function fullName(user) {
    if (!user) return '';
    return user.full_name || [user.first_name, user.last_name].filter(Boolean).join(' ') || user.name || '';
}

export const COURSE_STATUS = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    pending: { label: 'Tekshiruvda', variant: 'warning' },
    published: { label: 'Nashr qilingan', variant: 'success' },
    rejected: { label: 'Rad etilgan', variant: 'danger' },
    archived: { label: 'Arxivlangan', variant: 'neutral' },
};

export const courseStatus = (status) => COURSE_STATUS[status] || { label: status || '—', variant: 'neutral' };

export const ANNOUNCEMENT_TYPES = [
    { value: 'info', label: 'Ma\'lumot', variant: 'info' },
    { value: 'update', label: 'Yangilanish', variant: 'success' },
    { value: 'important', label: 'Muhim', variant: 'danger' },
    { value: 'promotion', label: 'Aksiya', variant: 'brand' },
];

export const announcementType = (type) =>
    ANNOUNCEMENT_TYPES.find((t) => t.value === type) || { value: type, label: type || '—', variant: 'neutral' };

export const QUESTION_TYPES = {
    single_choice: 'Bitta to\'g\'ri javob',
    multiple_choice: 'Ko\'p to\'g\'ri javob',
    true_false: 'Rost / Yolg\'on',
    fill_blank: 'Bo\'sh joyni to\'ldirish',
    matching: 'Moslashtirish',
    ordering: 'Tartiblash',
};

export const LESSON_TYPES = {
    video: 'Video dars',
    text: 'Matnli dars',
    quiz: 'Test',
};
