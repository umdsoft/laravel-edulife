// Formatting helpers shared by admin pages.
//   import { formatNumber, formatMoney, formatDate } from '@/Components/Admin/format';
// Numbers use a space as the thousands separator ("12 500"), money is "12 500 so'm",
// dates are "dd.mm.yyyy".

const numberFormatter = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 });

export const formatNumber = (value) => {
    const n = Number(value);
    if (value === null || value === undefined || value === '' || !Number.isFinite(n)) return '0';
    return numberFormatter.format(n);
};

export const formatMoney = (value) => `${formatNumber(Math.round(Number(value) || 0))} so'm`;

const pad = (n) => String(n).padStart(2, '0');

// Full URL / absolute path stays; a bare storage path ("courses/x.jpg") → "/storage/courses/x.jpg".
export const mediaUrl = (src) => {
    if (!src) return null;
    if (/^(https?:|data:|blob:|\/)/i.test(src)) return src;
    return `/storage/${src}`;
};

// Label + Badge variant lookups for common statuses.
const meta = (map, fallbackVariant = 'neutral') => (key) => map[key] || { label: key ?? '—', variant: fallbackVariant };

export const roleMeta = meta({
    student: { label: 'O\'quvchi', variant: 'neutral' },
    teacher: { label: 'O\'qituvchi', variant: 'info' },
    admin: { label: 'Admin', variant: 'brand' },
    super_admin: { label: 'Super admin', variant: 'brand' },
});

export const userStatusMeta = meta({
    active: { label: 'Faol', variant: 'success' },
    inactive: { label: 'Nofaol', variant: 'neutral' },
    blocked: { label: 'Bloklangan', variant: 'danger' },
});

export const courseStatusMeta = meta({
    draft: { label: 'Qoralama', variant: 'neutral' },
    pending: { label: 'Kutilmoqda', variant: 'warning' },
    published: { label: 'Nashr qilingan', variant: 'success' },
    rejected: { label: 'Rad etilgan', variant: 'danger' },
    archived: { label: 'Arxivlangan', variant: 'neutral' },
});

export const paymentStatusMeta = meta({
    completed: { label: 'To\'langan', variant: 'success' },
    paid: { label: 'To\'langan', variant: 'success' },
    pending: { label: 'Kutilmoqda', variant: 'warning' },
    processing: { label: 'Jarayonda', variant: 'info' },
    failed: { label: 'Muvaffaqiyatsiz', variant: 'danger' },
    cancelled: { label: 'Bekor qilingan', variant: 'neutral' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
});

export const toOptions = (map, allLabel) => [
    ...(allLabel !== undefined ? [{ value: '', label: allLabel }] : []),
    ...Object.entries(map).map(([value, label]) => ({ value, label: typeof label === 'string' ? label : label.label })),
];

// Accepts ISO strings, Date objects and already formatted "dd.mm.yyyy[ HH:ii]" strings.
export const formatDate = (value, { time = false } = {}) => {
    if (!value) return '—';
    if (typeof value === 'string' && /^\d{2}\.\d{2}\.\d{4}/.test(value)) {
        return time ? value : value.slice(0, 10);
    }
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    const date = `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
    return time ? `${date} ${pad(d.getHours())}:${pad(d.getMinutes())}` : date;
};
