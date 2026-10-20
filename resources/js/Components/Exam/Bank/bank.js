// Question bank / review queue helpers (stage 3): labels, unit presets, review keyboard map.

export const OPTION_LABELS = ['A', 'B', 'C', 'D', 'E'];

export const TYPES = {
    single_choice: 'Bitta to\'g\'ri javob',
    multiple_choice: 'Bir nechta to\'g\'ri javob',
    numeric: 'Son javob',
    numeric_with_unit: 'Son + birlik',
};
export const typeLabel = (t) => TYPES[t] || t || '—';
export const isChoiceType = (t) => t === 'single_choice' || t === 'multiple_choice' || t === 'true_false';
export const isNumericType = (t) => t === 'numeric' || t === 'numeric_with_unit';

export const STATUSES = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    in_review: { label: 'Tekshiruvda', variant: 'warning' },
    approved: { label: 'Tasdiqlangan', variant: 'success' },
    rejected: { label: 'Rad etilgan', variant: 'danger' },
    retired: { label: 'Muomaladan chiqarilgan', variant: 'neutral' },
};
export const statusMeta = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };

export const ORIGINS = {
    ai_draft: 'AI qoralama',
    manual: 'Qo\'lda',
    import: 'Import',
};
export const originLabel = (o) => ORIGINS[o] || o || '—';

export const DIFFICULTIES = [
    { value: 1, label: '1 — juda oson' },
    { value: 2, label: '2 — oson' },
    { value: 3, label: '3 — o\'rta' },
    { value: 4, label: '4 — qiyin' },
    { value: 5, label: '5 — juda qiyin' },
];
export const difficultyVariant = (d) => ({ 1: 'success', 2: 'success', 3: 'warning', 4: 'danger', 5: 'danger' })[d] || 'neutral';

export const FORMATS = {
    milliy_sertifikat: 'Milliy sertifikat',
    abituriyent: 'Abituriyent (OTM)',
    sat: 'SAT',
};

/**
 * Accepted alternative units for a canonical SI unit: factor converts ONE
 * alternative unit into the canonical unit (72 km/soat × 0.2777… = 20 m/s).
 */
export const UNIT_PRESETS = {
    'm/s': [{ unit: 'km/soat', factor: 1 / 3.6 }, { unit: 'km/h', factor: 1 / 3.6 }, { unit: 'sm/s', factor: 0.01 }],
    'm': [{ unit: 'sm', factor: 0.01 }, { unit: 'mm', factor: 0.001 }, { unit: 'km', factor: 1000 }],
    's': [{ unit: 'min', factor: 60 }, { unit: 'soat', factor: 3600 }, { unit: 'ms', factor: 0.001 }],
    'kg': [{ unit: 'g', factor: 0.001 }, { unit: 't', factor: 1000 }],
    'N': [{ unit: 'kN', factor: 1000 }, { unit: 'mN', factor: 0.001 }],
    'J': [{ unit: 'kJ', factor: 1000 }, { unit: 'MJ', factor: 1e6 }, { unit: 'mJ', factor: 0.001 }],
    'W': [{ unit: 'kW', factor: 1000 }, { unit: 'MW', factor: 1e6 }],
    'Pa': [{ unit: 'kPa', factor: 1000 }, { unit: 'MPa', factor: 1e6 }, { unit: 'atm', factor: 101325 }],
    'V': [{ unit: 'kV', factor: 1000 }, { unit: 'mV', factor: 0.001 }],
    'A': [{ unit: 'mA', factor: 0.001 }, { unit: 'kA', factor: 1000 }],
    'Ω': [{ unit: 'kΩ', factor: 1000 }, { unit: 'MΩ', factor: 1e6 }, { unit: 'Om', factor: 1 }],
    'C': [{ unit: 'mC', factor: 0.001 }, { unit: 'μC', factor: 1e-6 }, { unit: 'nC', factor: 1e-9 }],
    'Hz': [{ unit: 'kHz', factor: 1000 }, { unit: 'MHz', factor: 1e6 }],
    'm/s²': [{ unit: 'm/s^2', factor: 1 }],
    'm³': [{ unit: 'l', factor: 0.001 }, { unit: 'sm³', factor: 1e-6 }],
    'F': [{ unit: 'μF', factor: 1e-6 }, { unit: 'nF', factor: 1e-9 }, { unit: 'pF', factor: 1e-12 }],
    'T': [{ unit: 'mT', factor: 0.001 }],
};

/** Preset rows for a canonical unit, without units already listed. */
export function unitPresetRows(unit, existing = []) {
    const have = new Set((existing || []).map((r) => String(r.unit || '').trim()));
    return (UNIT_PRESETS[String(unit || '').trim()] || [])
        .filter((r) => !have.has(r.unit))
        .map((r) => ({ unit: r.unit, factor: Number(r.factor.toPrecision(10)) }));
}

/** Truthy when the keyboard event comes from a text field (shortcuts must not fire there). */
export function isTypingTarget(target) {
    if (!target) return false;
    const tag = String(target.tagName || '').toLowerCase();
    return tag === 'input' || tag === 'textarea' || tag === 'select' || !!target.isContentEditable;
}

/**
 * Review queue shortcuts:
 *   1–5 → mark option A–E · Enter → approve · R → reject · N / → → next · E → edit
 * Returns { action, index? } or null.
 */
export function reviewKeyAction(event) {
    if (!event || event.ctrlKey || event.metaKey || event.altKey) return null;
    if (isTypingTarget(event.target)) return null;
    const key = String(event.key || '');
    if (/^[1-5]$/.test(key)) return { action: 'mark', index: Number(key) - 1 };
    if (key === 'Enter') {
        // Enter on a focused button / link keeps its own meaning
        const tag = String(event.target?.tagName || '').toLowerCase();
        return tag === 'button' || tag === 'a' ? null : { action: 'approve' };
    }
    const k = key.toLowerCase();
    if (k === 'r') return { action: 'reject' };
    if (k === 'n' || key === 'ArrowRight') return { action: 'next' };
    if (k === 'e') return { action: 'edit' };
    return null;
}

/** A question has its correct answer set (mirrors QuestionApprovalGuard on the client). */
export function hasCorrectAnswer(question) {
    if (!question) return false;
    if (isChoiceType(question.question_type)) {
        const marked = (question.options || []).filter((o) => o.is_correct === true).length;
        return question.question_type === 'multiple_choice' ? marked >= 1 : marked === 1;
    }
    if (isNumericType(question.question_type)) {
        const a = question.answer;
        if (!a || a.value === null || a.value === undefined || a.value === '') return false;
        return question.question_type === 'numeric' || String(a.unit || '').trim() !== '';
    }
    return false;
}

/** Topics of one subject for a <Select>, indented by level. */
export function topicOptions(topics, subjectId, { includeInactive = false, keep = null } = {}) {
    return (topics || [])
        .filter((t) => t.subject_id === subjectId && (includeInactive || t.is_active || t.id === keep))
        .map((t) => ({
            value: t.id,
            label: `${'   '.repeat(Math.max(0, (t.level || 1) - 1))}${t.name_uz}${t.is_active ? '' : ' (nofaol)'}`,
        }));
}
