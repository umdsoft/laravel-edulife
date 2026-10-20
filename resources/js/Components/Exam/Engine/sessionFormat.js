// Shared labels / formatting of the teacher exam session pages.

const STATUS = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    scheduled: { label: 'Rejalashtirilgan', variant: 'info' },
    open: { label: 'Ochiq', variant: 'success' },
    closed: { label: 'Yopilgan', variant: 'warning' },
    graded: { label: 'Natijalar tayyor', variant: 'brand' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};

export function sessionStatus(status) {
    return STATUS[status] || { label: status || '—', variant: 'neutral' };
}

const pad = (n) => String(n).padStart(2, '0');

export function dateTimeLabel(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function timeLabel(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function num(value) {
    return String(Math.round((Number(value) || 0) * 100) / 100).replace('.', ',');
}
