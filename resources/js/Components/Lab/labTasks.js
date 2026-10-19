// Virtual lab: API paths and task-checking helpers used by Pages/Student/Lab/Simulate.vue.
//
// Tasks are checked on the server (App\Services\Lab\LabTaskChecker): the browser never
// sees the expected answers and never sends a score — it sends what the student did.

const attemptBase = (attemptId) => `/student/lab/api/attempt/${encodeURIComponent(attemptId)}`;

/** API / page URLs (the global route() helper does not know the lab routes). */
export const labUrls = {
    saveState: (id) => `${attemptBase(id)}/save-state`,
    measurement: (id) => `${attemptBase(id)}/measurement`,
    calculation: (id) => `${attemptBase(id)}/calculation`,
    checkTask: (id) => `${attemptBase(id)}/check-task`,
    complete: (id) => `${attemptBase(id)}/complete`,
    pause: (id) => `${attemptBase(id)}/pause`,
    experiment: (slug) => `/student/lab/experiment/${encodeURIComponent(slug)}`,
};

/** How many recent measurements / calculations a check sends (server limit is 20). */
export const MAX_SUBMITTED = 10;

/**
 * What the student has to provide for a task:
 *  'ack'         – instruction: just confirm it was read
 *  'measurement' – a measurement recorded in the simulation
 *  'calculation' – a calculator result
 *  'text'        – a written observation / conclusion
 */
export function taskAnswerKind(task) {
    switch (task?.type) {
        case 'instruction':
            return 'ack';
        case 'measurement':
            return 'measurement';
        case 'calculation':
            return 'calculation';
        default:
            return 'text';
    }
}

/**
 * Normalise tasks_progress into { [step]: {completed, score, ...} }.
 * Accepts the server map, the raw stored array ([{step, status, ...}]) or nothing.
 */
export function normalizeTasksProgress(progress) {
    if (!progress) return {};
    const entries = Array.isArray(progress) ? progress : Object.values(progress);
    const map = {};
    for (const e of entries) {
        if (!e || e.step == null) continue;
        map[Number(e.step)] = { ...e, completed: e.completed ?? e.status === 'completed' };
    }
    return map;
}

export function completedCount(progress, tasks = []) {
    return tasks.filter((t) => progress?.[t.step]?.completed).length;
}

export function progressPercent(progress, tasks = []) {
    if (!tasks.length) return 0;
    return Math.round((completedCount(progress, tasks) / tasks.length) * 100);
}

/** Next unfinished step after `step` (wrapping around); `step` itself when all are done. */
export function nextOpenStep(tasks, progress, step) {
    const open = tasks.map((t) => t.step).filter((s) => !progress?.[s]?.completed).sort((a, b) => a - b);
    if (!open.length) return step;
    return open.find((s) => s > step) ?? open[0];
}

const isNumeric = (v) => v !== null && v !== '' && typeof v !== 'boolean' && Number.isFinite(Number(v));

// Recent numeric entries recorded during this step (or overall, if none were).
const recentFor = (list, step, valueKey) => {
    const items = (Array.isArray(list) ? list : []).filter((x) => x && isNumeric(x[valueKey]));
    const own = items.filter((x) => Number(x.step) === Number(step));
    return (own.length ? own : items).slice(-MAX_SUBMITTED);
};

/**
 * Request body for POST check-task.
 * @param {object} task         active task ({step, type})
 * @param {object} ctx          {measurements, calculations, text}
 */
export function buildCheckPayload(task, { measurements = [], calculations = [], text = '' } = {}) {
    const payload = { task_number: task.step };
    switch (taskAnswerKind(task)) {
        case 'measurement':
            payload.measurements = recentFor(measurements, task.step, 'value').map((m) => ({
                name: String(m.name ?? '').slice(0, 100),
                value: Number(m.value),
                unit: String(m.unit ?? '').slice(0, 20),
            }));
            break;
        case 'calculation':
            payload.calculations = recentFor(calculations, task.step, 'result').map((c) => ({
                formula_id: c.formula_id,
                result: Number(c.result),
                unit: String(c.unit ?? '').slice(0, 20),
            }));
            break;
        case 'text':
            payload.text = String(text ?? '');
            break;
        default:
            break;
    }
    return payload;
}
