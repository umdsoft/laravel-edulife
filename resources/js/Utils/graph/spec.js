/**
 * Graph block spec v1 (docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §1.2, teacher guide docs/exam/RASM_VA_GRAFIK.md).
 *
 *   ```graph
 *   {"x":[-5,5],"y":[-3,9],"grid":true,"functions":[{"expr":"x^2-2","label":"f(x)"}]}
 *   ```
 *
 * validateGraphSpec() mirrors App\Support\RichText\GraphSpec (PHP) — keep the rules and limits in sync.
 * sampleFunction() turns an expression into polyline pieces, breaking at discontinuities (1/x, tan x)
 * so no vertical spikes are drawn.
 */
import { compileExpr, exprError } from './expr';

export const GRAPH_COLORS = ['blue', 'red', 'green', 'orange', 'purple', 'gray'];

export const LIMITS = {
    json: 8000,
    abs: 1e6,
    label: 40,
    functions: 8,
    polylines: 10,
    polylinePoints: 200,
    points: 50,
    segments: 50,
    vectors: 30,
    polygons: 20,
    polygonPoints: 50,
    circles: 20,
    labels: 50,
    angles: 20,
    ticksPerAxis: 100,
};

const TOP_KEYS = ['x', 'y', 'grid', 'ticks', 'axes', 'equal', 'width', 'functions', 'polylines', 'points', 'segments', 'vectors', 'polygons', 'circles', 'labels', 'angles'];
const ITEM_KEYS = {
    functions: ['expr', 'domain', 'dashed', 'label', 'color'],
    polylines: ['points', 'dashed', 'label', 'color'],
    points: ['x', 'y', 'label', 'color', 'hollow'],
    segments: ['from', 'to', 'dashed', 'label', 'color'],
    vectors: ['from', 'to', 'label', 'color'],
    polygons: ['points', 'dashed', 'fill', 'label', 'color'],
    circles: ['center', 'r', 'dashed', 'fill', 'label', 'color'],
    labels: ['x', 'y', 'text', 'color'],
    angles: ['vertex', 'from', 'to', 'label', 'right', 'color'],
};
const ITEM_NAMES = {
    functions: 'Funksiya', polylines: 'Siniq chiziq', points: 'Nuqta', segments: 'Kesma', vectors: 'Vektor',
    polygons: "Ko'pburchak", circles: 'Aylana', labels: 'Yozuv', angles: 'Burchak',
};

const isNum = (v) => typeof v === 'number' && Number.isFinite(v) && Math.abs(v) <= LIMITS.abs;
const isPair = (v) => Array.isArray(v) && v.length === 2 && isNum(v[0]) && isNum(v[1]);
const isRange = (v) => isPair(v) && v[0] < v[1];

/** Parse the JSON text of a ```graph block → { spec, errors }. */
export function parseGraphBlock(source) {
    const text = String(source ?? '').trim();
    if (text.length > LIMITS.json) return { spec: null, errors: [`Grafik juda katta (ko'pi bilan ${LIMITS.json} belgi)`] };
    let spec;
    try {
        spec = JSON.parse(text);
    } catch {
        return { spec: null, errors: ["Grafik JSON noto'g'ri yozilgan"] };
    }
    const errors = validateGraphSpec(spec);

    return { spec: errors.length ? null : spec, errors };
}

/** List of Uzbek error messages; empty when the spec is valid. */
export function validateGraphSpec(spec) {
    const errors = [];
    if (!spec || typeof spec !== 'object' || Array.isArray(spec)) return ["Grafik obyekt ({…}) bo'lishi kerak"];

    for (const key of Object.keys(spec)) {
        if (!TOP_KEYS.includes(key)) errors.push(`Noma'lum kalit: "${key}"`);
    }
    for (const axis of ['x', 'y']) {
        if (!isRange(spec[axis])) errors.push(`"${axis}" oralig'i [min, max] ko'rinishida, min < max bo'lsin`);
    }
    if ('grid' in spec && typeof spec.grid !== 'boolean') errors.push('"grid" true yoki false bo\'lsin');
    if ('equal' in spec && typeof spec.equal !== 'boolean') errors.push('"equal" true yoki false bo\'lsin');
    if ('ticks' in spec) {
        const t = spec.ticks;
        const ok = (isNum(t) && t > 0) || (isPair(t) && t[0] > 0 && t[1] > 0);
        if (!ok) errors.push('"ticks" musbat son yoki [x qadami, y qadami] bo\'lsin');
        else if (isRange(spec.x) && isRange(spec.y)) {
            const [tx, ty] = Array.isArray(t) ? t : [t, t];
            if ((spec.x[1] - spec.x[0]) / tx > LIMITS.ticksPerAxis || (spec.y[1] - spec.y[0]) / ty > LIMITS.ticksPerAxis) {
                errors.push(`"ticks" juda kichik (bir o'qda ko'pi bilan ${LIMITS.ticksPerAxis} bo'linma)`);
            }
        }
    }
    if ('axes' in spec) {
        const a = spec.axes;
        if (!a || typeof a !== 'object' || Array.isArray(a) || Object.keys(a).some((k) => k !== 'x' && k !== 'y')) {
            errors.push('"axes" {"x": "…", "y": "…"} ko\'rinishida bo\'lsin');
        } else {
            for (const k of ['x', 'y']) if (k in a) checkLabel(a[k], `"axes.${k}"`, errors);
        }
    }
    if ('width' in spec && !(isNum(spec.width) && spec.width >= 20 && spec.width <= 100)) {
        errors.push('"width" 20 dan 100 gacha foiz bo\'lsin');
    }

    for (const [list, keys] of Object.entries(ITEM_KEYS)) {
        if (!(list in spec)) continue;
        const items = spec[list];
        if (!Array.isArray(items)) { errors.push(`"${list}" ro'yxat ([…]) bo'lsin`); continue; }
        if (items.length > LIMITS[list]) errors.push(`"${list}": ko'pi bilan ${LIMITS[list]} ta`);
        items.slice(0, LIMITS[list]).forEach((item, i) => {
            const where = `${ITEM_NAMES[list]} ${i + 1}`;
            if (!item || typeof item !== 'object' || Array.isArray(item)) { errors.push(`${where}: obyekt bo'lsin`); return; }
            for (const k of Object.keys(item)) if (!keys.includes(k)) errors.push(`${where}: noma'lum kalit "${k}"`);
            checkItem(list, item, where, errors);
        });
    }

    return errors;
}

function checkLabel(v, where, errors, required = false) {
    if (v === undefined || v === null) {
        if (required) errors.push(`${where}: matn kerak`);
        return;
    }
    if (typeof v !== 'string') errors.push(`${where}: matn bo'lsin`);
    else if (v.length > LIMITS.label) errors.push(`${where}: ko'pi bilan ${LIMITS.label} belgi`);
    else if (required && !v.trim()) errors.push(`${where}: matn kerak`);
}

function checkPoints(points, max, min, where, errors) {
    if (!Array.isArray(points) || points.length < min) { errors.push(`${where}: kamida ${min} ta nuqta [[x, y], …] kerak`); return; }
    if (points.length > max) errors.push(`${where}: ko'pi bilan ${max} ta nuqta`);
    if (!points.every(isPair)) errors.push(`${where}: har nuqta [x, y] sonlar juftligi bo'lsin`);
}

function checkItem(list, item, where, errors) {
    if ('color' in item && !GRAPH_COLORS.includes(item.color)) errors.push(`${where}: rang ${GRAPH_COLORS.join(', ')} dan biri bo'lsin`);
    for (const flag of ['dashed', 'fill', 'hollow', 'right']) {
        if (flag in item && typeof item[flag] !== 'boolean') errors.push(`${where}: "${flag}" true yoki false bo'lsin`);
    }
    if ('label' in item) checkLabel(item.label, where, errors);

    switch (list) {
        case 'functions': {
            const err = typeof item.expr === 'string' ? exprError(item.expr) : 'ifoda (expr) kerak';
            if (err) errors.push(`${where}: ${err}`);
            if ('domain' in item && !isRange(item.domain)) errors.push(`${where}: "domain" [a, b], a < b bo'lsin`);
            break;
        }
        case 'polylines':
            checkPoints(item.points, LIMITS.polylinePoints, 2, where, errors);
            break;
        case 'polygons':
            checkPoints(item.points, LIMITS.polygonPoints, 3, where, errors);
            break;
        case 'points':
        case 'labels':
            if (!isNum(item.x) || !isNum(item.y)) errors.push(`${where}: "x" va "y" son bo'lsin`);
            if (list === 'labels') checkLabel(item.text, where, errors, true);
            break;
        case 'segments':
        case 'vectors':
            if (!isPair(item.from) || !isPair(item.to)) errors.push(`${where}: "from" va "to" [x, y] bo'lsin`);
            break;
        case 'circles':
            if (!isPair(item.center)) errors.push(`${where}: "center" [x, y] bo'lsin`);
            if (!(isNum(item.r) && item.r > 0)) errors.push(`${where}: radius "r" musbat son bo'lsin`);
            break;
        case 'angles':
            if (!isPair(item.vertex) || !isPair(item.from) || !isPair(item.to)) errors.push(`${where}: "vertex", "from", "to" [x, y] bo'lsin`);
            break;
    }
}

/** "Nice" tick step (1, 2, 5 × 10^k) giving about `target` ticks over the span. */
export function niceStep(span, target = 8) {
    if (!(span > 0)) return 1;
    const raw = span / target;
    const mag = Math.pow(10, Math.floor(Math.log10(raw)));
    const norm = raw / mag;
    const nice = norm < 1.5 ? 1 : norm < 3 ? 2 : norm < 7 ? 5 : 10;

    return nice * mag;
}

/** Tick values from min to max (inclusive) on multiples of step. */
export function ticks(min, max, step) {
    const out = [];
    const first = Math.ceil(min / step - 1e-9);
    const last = Math.floor(max / step + 1e-9);
    for (let k = first; k <= last && out.length <= LIMITS.ticksPerAxis; k++) {
        const v = k * step;
        out.push(Math.abs(v) < step * 1e-9 ? 0 : Number(v.toPrecision(12)));
    }
    return out;
}

/** Tick label: no float noise, decimal comma (Uzbek), unicode minus. */
export function formatTick(v) {
    const s = String(Number(v.toPrecision(10)));
    return s.replace('-', '−').replace('.', ',');
}

/**
 * Sample y = f(x) over [a, b] → array of pieces, each an array of [x, y] (finite values only).
 * A piece ends where f is undefined or jumps (a gap that does not shrink when the interval is
 * bisected), so asymptotes and step jumps are not joined by a vertical line.
 *
 * @param {(x:number)=>number} f
 * @param {[number, number]} xRange  drawing range (the function's domain ∩ the axis range)
 * @param {[number, number]} yRange  visible y range (decides what counts as a jump)
 */
export function sampleFunction(f, [a, b], [ymin, ymax], samples = 480) {
    const span = ymax - ymin;
    const threshold = span * 0.02;
    const clampHi = ymax + span * 10;
    const clampLo = ymin - span * 10;
    const clamp = (y) => Math.min(clampHi, Math.max(clampLo, y));
    const pieces = [];
    let piece = [];
    let prev = null;

    const flush = () => {
        if (piece.length > 1) pieces.push(piece);
        else if (piece.length === 1) pieces.push(piece); // isolated defined point
        piece = [];
    };

    for (let i = 0; i <= samples; i++) {
        const x = a + ((b - a) * i) / samples;
        const y = safe(f, x);
        if (!Number.isFinite(y)) {
            flush();
            prev = null;
            continue;
        }
        if (prev && Math.abs(y - prev[1]) > threshold && isJump(f, prev[0], prev[1], x, y, threshold)) {
            flush();
        }
        piece.push([x, clamp(y)]);
        prev = [x, y];
    }
    flush();

    // drop single-point pieces that are only numerical noise next to a gap
    return pieces.filter((p) => p.length > 1);
}

function safe(f, x) {
    try {
        const y = f(x);
        return typeof y === 'number' ? y : NaN;
    } catch {
        return NaN;
    }
}

/** True when the gap between (xa, ya) and (xb, yb) persists under bisection (a discontinuity). */
export function isJump(f, xa, ya, xb, yb, threshold) {
    for (let i = 0; i < 30; i++) {
        const xm = (xa + xb) / 2;
        const ym = safe(f, xm);
        if (!Number.isFinite(ym)) return true;
        if (Math.abs(ym - ya) >= Math.abs(yb - ym)) { xb = xm; yb = ym; } else { xa = xm; ya = ym; }
        if (Math.abs(yb - ya) <= threshold) return false;
    }
    return true;
}

/** Compiled functions of a valid spec: [{ item, pieces }]. */
export function functionPaths(spec) {
    return (spec.functions || []).map((item) => {
        const f = compileExpr(item.expr);
        const lo = Math.max(spec.x[0], item.domain ? item.domain[0] : -Infinity);
        const hi = Math.min(spec.x[1], item.domain ? item.domain[1] : Infinity);
        return { item, pieces: lo < hi ? sampleFunction(f, [lo, hi], spec.y) : [] };
    });
}

/** Default spec the editor's "Grafik" dialog starts from. */
export function blankGraphSpec() {
    return { x: [-5, 5], y: [-5, 5], grid: true, functions: [{ expr: 'x^2 - 2', label: 'f(x)' }] };
}

/** Pretty, compact JSON for the text: one item per line. */
export function stringifyGraphSpec(spec) {
    const parts = Object.entries(spec).map(([k, v]) => {
        if (Array.isArray(v) && v.length && typeof v[0] === 'object' && !Array.isArray(v[0])) {
            return `  "${k}": [\n${v.map((it) => `    ${JSON.stringify(it)}`).join(',\n')}\n  ]`;
        }
        return `  "${k}": ${JSON.stringify(v)}`;
    });
    return `{\n${parts.join(',\n')}\n}`;
}
