<script setup>
/**
 * GraphView — draws a ```graph block (spec v1, Utils/graph/spec.js) as SVG with our theme tokens.
 *   <GraphView :spec="{ x: [-5, 5], y: [-3, 9], functions: [{ expr: 'x^2-2', label: 'f(x)' }] }" />
 *   <GraphView :spec="null" :errors="['Grafik JSON noto\'g\'ri yozilgan']" />
 * Rendered at the container's real pixel width (ResizeObserver), so lines and text stay crisp on a phone.
 */
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { formatTick, functionPaths, niceStep, ticks } from '@/Utils/graph/spec';

const props = defineProps({
    spec: { type: Object, default: null },
    errors: { type: Array, default: () => [] },
});

const uid = useId().replace(/[^a-zA-Z0-9_-]/g, '');
const box = ref(null);
const measured = ref(0);
let observer = null;

onMounted(() => {
    const el = box.value;
    if (!el) return;
    measured.value = Math.round(el.clientWidth || 0);
    if (typeof ResizeObserver !== 'undefined') {
        observer = new ResizeObserver((entries) => {
            const w = Math.round(entries[0]?.contentRect?.width || 0);
            if (w && Math.abs(w - measured.value) >= 2) measured.value = w;
        });
        observer.observe(el);
    }
});
onBeforeUnmount(() => observer?.disconnect());

const FUNCTION_COLORS = ['blue', 'red', 'green', 'orange', 'purple'];
const M = { left: 34, right: 18, top: 18, bottom: 26 };

const geo = computed(() => {
    const s = props.spec;
    if (!s) return null;
    const W = Math.max(220, Math.min(measured.value || 560, 1200));
    const plotW = W - M.left - M.right;
    const [x0, x1] = s.x;
    const [y0, y1] = s.y;
    let plotH;
    if (s.equal) plotH = plotW * ((y1 - y0) / (x1 - x0));
    else plotH = plotW * 0.62;
    plotH = Math.max(140, Math.min(plotH, Math.max(560, W * 1.1)));
    const H = plotH + M.top + M.bottom;
    const sx = (x) => M.left + ((x - x0) / (x1 - x0)) * plotW;
    const sy = (y) => M.top + ((y1 - y) / (y1 - y0)) * plotH;
    const kx = plotW / (x1 - x0);
    const ky = plotH / (y1 - y0);

    return { W, H, plotW, plotH, x0, x1, y0, y1, sx, sy, kx, ky };
});

const axes = computed(() => {
    const g = geo.value;
    const s = props.spec;
    if (!g) return null;
    const [tx, ty] = Array.isArray(s.ticks) ? s.ticks : s.ticks ? [s.ticks, s.ticks] : [niceStep(g.x1 - g.x0, g.plotW / 56), niceStep(g.y1 - g.y0, g.plotH / 40)];
    const xs = ticks(g.x0, g.x1, tx);
    const ys = ticks(g.y0, g.y1, ty);
    // axes cross at 0 when 0 is in range, else they sit on the edge
    const ax = g.y0 <= 0 && g.y1 >= 0 ? g.sy(0) : g.sy(g.y0);
    const ay = g.x0 <= 0 && g.x1 >= 0 ? g.sx(0) : g.sx(g.x0);
    // thin out labels that would overlap
    const everyX = Math.max(1, Math.ceil(30 / Math.max(1, tx * g.kx)));
    const everyY = Math.max(1, Math.ceil(18 / Math.max(1, ty * g.ky)));
    const hasOrigin = ax === g.sy(0) && ay === g.sx(0);

    return {
        ax, ay, hasOrigin,
        xTicks: xs.map((v, i) => ({ v, px: g.sx(v), label: i % everyX === 0 && !(hasOrigin && v === 0) ? formatTick(v) : null })),
        yTicks: ys.map((v, i) => ({ v, py: g.sy(v), label: i % everyY === 0 && !(hasOrigin && v === 0) ? formatTick(v) : null })),
    };
});

const toPath = (pts) => {
    const g = geo.value;
    return pts.map(([x, y], i) => `${i ? 'L' : 'M'}${g.sx(x).toFixed(2)} ${g.sy(y).toFixed(2)}`).join('');
};

const fnPaths = computed(() => {
    if (!geo.value) return [];
    try {
        return functionPaths(props.spec).map(({ item, pieces }, i) => {
            const g = geo.value;
            // label near the right end of the longest piece that is visible
            let anchor = null;
            const visible = pieces.flatMap((p) => p.filter(([, y]) => y >= g.y0 && y <= g.y1));
            if (visible.length) anchor = visible[Math.floor(visible.length * 0.86)] || visible[visible.length - 1];
            return {
                d: pieces.map(toPath).join(''),
                color: item.color || FUNCTION_COLORS[i % FUNCTION_COLORS.length],
                dashed: !!item.dashed,
                label: item.label || null,
                lx: anchor ? Math.min(g.sx(anchor[0]) + 6, g.W - M.right - 4) : 0,
                ly: anchor ? Math.max(g.sy(anchor[1]) - 6, M.top + 10) : 0,
            };
        });
    } catch {
        return [];
    }
});

/** Label position kept inside the picture: near the right edge the text grows to the left. */
const at = (x, y, anchor = 'start') => {
    const W = geo.value.W;
    if (anchor === 'start' && x > W - 44) return { x: W - 4, y, 'text-anchor': 'end' };
    return { x: Math.max(4, Math.min(x, W - 4)), y: Math.max(12, Math.min(y, geo.value.H - 4)), 'text-anchor': anchor };
};

const lineAttrs = (dashed) => (dashed ? { 'stroke-dasharray': '6 4' } : {});

/** Arrowhead triangle at (x2, y2) pointing from (x1, y1), screen coordinates. */
const arrowHead = (x1, y1, x2, y2, size = 9) => {
    const a = Math.atan2(y2 - y1, x2 - x1);
    const p = (ang) => `${(x2 - size * Math.cos(a - ang)).toFixed(2)},${(y2 - size * Math.sin(a - ang)).toFixed(2)}`;
    return `${x2.toFixed(2)},${y2.toFixed(2)} ${p(0.42)} ${p(-0.42)}`;
};

const shapes = computed(() => {
    const g = geo.value;
    const s = props.spec;
    if (!g) return null;
    const P = ([x, y]) => [g.sx(x), g.sy(y)];

    const polylines = (s.polylines || []).map((it, i) => ({
        d: toPath(it.points), color: it.color || FUNCTION_COLORS[(i + (s.functions || []).length) % FUNCTION_COLORS.length],
        dashed: !!it.dashed, label: it.label || null,
        lx: g.sx(it.points[it.points.length - 1][0]) + 6, ly: g.sy(it.points[it.points.length - 1][1]) - 6,
    }));
    const segments = (s.segments || []).map((it) => {
        const [x1, y1] = P(it.from);
        const [x2, y2] = P(it.to);
        return { x1, y1, x2, y2, color: it.color || 'default', dashed: !!it.dashed, label: it.label || null, ...midLabel(x1, y1, x2, y2) };
    });
    const vectors = (s.vectors || []).map((it) => {
        const [x1, y1] = P(it.from);
        const [x2, y2] = P(it.to);
        const len = Math.hypot(x2 - x1, y2 - y1) || 1;
        const shorten = Math.min(6, len / 2); // the line stops inside the arrowhead
        return {
            x1, y1, x2: x2 - ((x2 - x1) / len) * shorten, y2: y2 - ((y2 - y1) / len) * shorten,
            head: arrowHead(x1, y1, x2, y2), color: it.color || 'default', label: it.label || null, ...midLabel(x1, y1, x2, y2),
        };
    });
    const polygons = (s.polygons || []).map((it) => {
        const pts = it.points.map(P);
        const cx = pts.reduce((a, p) => a + p[0], 0) / pts.length;
        const cy = pts.reduce((a, p) => a + p[1], 0) / pts.length;
        return { points: pts.map((p) => p.map((v) => v.toFixed(2)).join(',')).join(' '), color: it.color || 'default', dashed: !!it.dashed, fill: !!it.fill, label: it.label || null, lx: cx, ly: cy };
    });
    const circles = (s.circles || []).map((it) => {
        const [cx, cy] = P(it.center);
        return { cx, cy, rx: it.r * g.kx, ry: it.r * g.ky, color: it.color || 'default', dashed: !!it.dashed, fill: !!it.fill, label: it.label || null, lx: cx + it.r * g.kx * 0.72 + 4, ly: cy - it.r * g.ky * 0.72 - 4 };
    });
    const points = (s.points || []).map((it) => ({ cx: g.sx(it.x), cy: g.sy(it.y), color: it.color || 'default', hollow: !!it.hollow, label: it.label || null }));
    const labels = (s.labels || []).map((it) => ({ x: g.sx(it.x), y: g.sy(it.y), text: it.text, color: it.color || 'default' }));
    const angles = (s.angles || []).map((it) => angleShape(P(it.vertex), P(it.from), P(it.to), it));

    return { polylines, segments, vectors, polygons, circles, points, labels, angles };
});

function midLabel(x1, y1, x2, y2) {
    const len = Math.hypot(x2 - x1, y2 - y1) || 1;
    // perpendicular offset, always towards the upper side
    let nx = -(y2 - y1) / len;
    let ny = (x2 - x1) / len;
    if (ny > 0) { nx = -nx; ny = -ny; }
    return { lx: (x1 + x2) / 2 + nx * 12, ly: (y1 + y2) / 2 + ny * 12 + 4 };
}

function angleShape([vx, vy], [fx, fy], [tx, ty], it) {
    const r = 22;
    let a1 = Math.atan2(fy - vy, fx - vx);
    let a2 = Math.atan2(ty - vy, tx - vx);
    let delta = a2 - a1;
    while (delta <= -Math.PI) delta += 2 * Math.PI;
    while (delta > Math.PI) delta -= 2 * Math.PI;
    const mid = a1 + delta / 2;
    const color = it.color || 'default';
    const label = it.label || null;
    const lx = vx + Math.cos(mid) * (r + 13);
    const ly = vy + Math.sin(mid) * (r + 13) + 4;
    if (it.right) {
        const s = 12;
        const u = [Math.cos(a1), Math.sin(a1)];
        const w = [Math.cos(a2), Math.sin(a2)];
        const p1 = [vx + u[0] * s, vy + u[1] * s];
        const p2 = [vx + (u[0] + w[0]) * s, vy + (u[1] + w[1]) * s];
        const p3 = [vx + w[0] * s, vy + w[1] * s];
        return { d: `M${p1[0]} ${p1[1]}L${p2[0]} ${p2[1]}L${p3[0]} ${p3[1]}`, color, label, lx, ly };
    }
    const start = [vx + Math.cos(a1) * r, vy + Math.sin(a1) * r];
    const end = [vx + Math.cos(a1 + delta) * r, vy + Math.sin(a1 + delta) * r];
    const sweep = delta > 0 ? 1 : 0;
    return { d: `M${start[0].toFixed(2)} ${start[1].toFixed(2)}A${r} ${r} 0 0 ${sweep} ${end[0].toFixed(2)} ${end[1].toFixed(2)}`, color, label, lx, ly };
}

const ariaLabel = computed(() => {
    const s = props.spec;
    if (!s) return 'Grafik';
    const fns = (s.functions || []).map((f) => `y = ${f.expr}`);
    const names = [s.axes?.x && `x o'qi: ${s.axes.x}`, s.axes?.y && `y o'qi: ${s.axes.y}`].filter(Boolean);
    return ['Grafik', ...fns, ...names].join('; ');
});

const wrapStyle = computed(() => (props.spec?.width ? { width: `${props.spec.width}%` } : {}));
const showGrid = computed(() => props.spec?.grid !== false);
</script>

<template>
    <figure ref="box" class="graph-view" :style="wrapStyle">
        <div v-if="!spec" class="graph-error" role="note">
            <p class="font-medium">Grafikni chizib bo'lmadi</p>
            <ul v-if="errors.length" class="mt-1 list-disc pl-5">
                <li v-for="(e, i) in errors.slice(0, 4)" :key="i">{{ e }}</li>
            </ul>
        </div>
        <svg v-else-if="geo" :width="geo.W" :height="geo.H" :viewBox="`0 0 ${geo.W} ${geo.H}`" role="img" :aria-label="ariaLabel"
            xmlns="http://www.w3.org/2000/svg" class="block max-w-full" style="height:auto">
            <defs>
                <clipPath :id="`gclip-${uid}`">
                    <rect :x="34" :y="18" :width="geo.plotW" :height="geo.plotH" />
                </clipPath>
            </defs>

            <!-- grid -->
            <g v-if="showGrid" class="g-grid">
                <line v-for="t in axes.xTicks" :key="`gx${t.v}`" :x1="t.px" :x2="t.px" :y1="18" :y2="18 + geo.plotH" />
                <line v-for="t in axes.yTicks" :key="`gy${t.v}`" :x1="34" :x2="34 + geo.plotW" :y1="t.py" :y2="t.py" />
            </g>

            <!-- axes -->
            <g class="g-axis">
                <line :x1="34" :x2="34 + geo.plotW + 8" :y1="axes.ax" :y2="axes.ax" />
                <line :x1="axes.ay" :x2="axes.ay" :y1="18 + geo.plotH" :y2="10" />
                <polygon class="g-axis-head" :points="arrowHead(34, axes.ax, 34 + geo.plotW + 10, axes.ax, 7)" />
                <polygon class="g-axis-head" :points="arrowHead(axes.ay, 18 + geo.plotH, axes.ay, 8, 7)" />
                <line v-for="t in axes.xTicks" :key="`tx${t.v}`" :x1="t.px" :x2="t.px" :y1="axes.ax - 3" :y2="axes.ax + 3" />
                <line v-for="t in axes.yTicks" :key="`ty${t.v}`" :x1="axes.ay - 3" :x2="axes.ay + 3" :y1="t.py" :y2="t.py" />
            </g>
            <g class="g-tick-label">
                <template v-for="t in axes.xTicks" :key="`lx${t.v}`">
                    <text v-if="t.label" :x="t.px" :y="Math.min(axes.ax + 15, geo.H - 3)" text-anchor="middle">{{ t.label }}</text>
                </template>
                <template v-for="t in axes.yTicks" :key="`ly${t.v}`">
                    <text v-if="t.label" :x="Math.max(axes.ay - 6, 12)" :y="t.py + 4" text-anchor="end">{{ t.label }}</text>
                </template>
                <text v-if="axes.hasOrigin" :x="axes.ay - 5" :y="axes.ax + 14" text-anchor="end">0</text>
            </g>
            <g class="g-axis-label">
                <text :x="34 + geo.plotW + 8" :y="axes.ax - 8" text-anchor="end">{{ spec.axes?.x || 'x' }}</text>
                <text :x="axes.ay + 8" :y="16" text-anchor="start">{{ spec.axes?.y || 'y' }}</text>
            </g>

            <!-- curves, clipped to the plot area -->
            <g :clip-path="`url(#gclip-${uid})`">
                <polygon v-for="(p, i) in shapes.polygons" :key="`pg${i}`" :points="p.points" :class="['g-shape', `c-${p.color}`, p.fill ? 'is-filled' : '']"
                    v-bind="lineAttrs(p.dashed)" />
                <ellipse v-for="(c, i) in shapes.circles" :key="`c${i}`" :cx="c.cx" :cy="c.cy" :rx="c.rx" :ry="c.ry"
                    :class="['g-shape', `c-${c.color}`, c.fill ? 'is-filled' : '']" v-bind="lineAttrs(c.dashed)" />
                <path v-for="(f, i) in fnPaths" :key="`f${i}`" :d="f.d" :class="['g-curve', `c-${f.color}`]" v-bind="lineAttrs(f.dashed)" />
                <path v-for="(p, i) in shapes.polylines" :key="`pl${i}`" :d="p.d" :class="['g-curve', `c-${p.color}`]" v-bind="lineAttrs(p.dashed)" />
                <line v-for="(s, i) in shapes.segments" :key="`s${i}`" :x1="s.x1" :y1="s.y1" :x2="s.x2" :y2="s.y2"
                    :class="['g-line', `c-${s.color}`]" v-bind="lineAttrs(s.dashed)" />
                <g v-for="(v, i) in shapes.vectors" :key="`v${i}`" :class="`c-${v.color}`">
                    <line :x1="v.x1" :y1="v.y1" :x2="v.x2" :y2="v.y2" class="g-line" />
                    <polygon :points="v.head" class="g-head" />
                </g>
                <path v-for="(a, i) in shapes.angles" :key="`a${i}`" :d="a.d" :class="['g-angle', `c-${a.color}`]" />
            </g>

            <circle v-for="(p, i) in shapes.points" :key="`p${i}`" :cx="p.cx" :cy="p.cy" r="4"
                :class="['g-point', `c-${p.color}`, p.hollow ? 'is-hollow' : '']" />

            <!-- labels last, with a halo so they stay readable over lines -->
            <g class="g-label">
                <template v-for="(f, i) in fnPaths" :key="`fl${i}`">
                    <text v-if="f.label" v-bind="at(f.lx, f.ly)" :class="`c-${f.color}`">{{ f.label }}</text>
                </template>
                <template v-for="(p, i) in shapes.polylines" :key="`pll${i}`">
                    <text v-if="p.label" v-bind="at(p.lx, p.ly)" :class="`c-${p.color}`">{{ p.label }}</text>
                </template>
                <template v-for="(s, i) in [...shapes.segments, ...shapes.vectors]" :key="`sl${i}`">
                    <text v-if="s.label" v-bind="at(s.lx, s.ly, 'middle')" :class="`c-${s.color}`">{{ s.label }}</text>
                </template>
                <template v-for="(p, i) in [...shapes.polygons, ...shapes.circles]" :key="`gl${i}`">
                    <text v-if="p.label" v-bind="at(p.lx, p.ly, 'middle')" :class="`c-${p.color}`">{{ p.label }}</text>
                </template>
                <template v-for="(a, i) in shapes.angles" :key="`al${i}`">
                    <text v-if="a.label" v-bind="at(a.lx, a.ly, 'middle')" :class="`c-${a.color}`">{{ a.label }}</text>
                </template>
                <template v-for="(p, i) in shapes.points" :key="`ptl${i}`">
                    <text v-if="p.label" v-bind="at(p.cx + 7, p.cy - 7)" :class="`c-${p.color}`">{{ p.label }}</text>
                </template>
                <text v-for="(l, i) in shapes.labels" :key="`l${i}`" v-bind="at(l.x, l.y, 'middle')" :class="`c-${l.color}`">{{ l.text }}</text>
            </g>
        </svg>
    </figure>
</template>

<style scoped>
.graph-view {
    --g-default: var(--fg);
    --g-blue: var(--brand-fg);
    --g-red: var(--danger-fg);
    --g-green: var(--success-fg);
    --g-orange: var(--warning-fg);
    --g-purple: 147 51 234;
    --g-gray: var(--fg-subtle);
    display: block;
    max-width: min(100%, 680px);
    margin: 0.75em 0;
    color: rgb(var(--fg));
}
:global(html.dark) .graph-view {
    --g-purple: 192 132 252;
}
.graph-view svg {
    shape-rendering: geometricPrecision;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}
.graph-error {
    border: 1px dashed rgb(var(--danger-600));
    background: rgb(var(--danger-50));
    color: rgb(var(--danger-fg));
    border-radius: 10px;
    padding: 0.6em 0.9em;
    font-size: 0.85em;
}
.g-grid line { stroke: rgb(var(--border)); stroke-width: 1; }
.g-axis line { stroke: rgb(var(--fg-subtle)); stroke-width: 1.25; }
.g-axis-head { fill: rgb(var(--fg-subtle)); }
.g-tick-label text { fill: rgb(var(--fg-subtle)); font-size: 11px; font-variant-numeric: tabular-nums; }
.g-axis-label text { fill: rgb(var(--fg-muted)); font-size: 12px; font-style: italic; }
.g-curve { fill: none; stroke-width: 2.25; stroke-linejoin: round; stroke-linecap: round; }
.g-line { stroke-width: 2; stroke-linecap: round; }
.g-shape { fill: none; stroke-width: 1.75; stroke-linejoin: round; }
.g-angle { fill: none; stroke-width: 1.5; }
.g-point { stroke-width: 2; }
.g-label text {
    font-size: 13px;
    font-weight: 600;
    paint-order: stroke;
    stroke: rgb(var(--surface));
    stroke-width: 3px;
    stroke-linejoin: round;
}

.c-default { --c: var(--g-default); }
.c-blue { --c: var(--g-blue); }
.c-red { --c: var(--g-red); }
.c-green { --c: var(--g-green); }
.c-orange { --c: var(--g-orange); }
.c-purple { --c: var(--g-purple); }
.c-gray { --c: var(--g-gray); }

.g-curve, .g-line, .g-shape, .g-angle { stroke: rgb(var(--c)); }
.g-shape.is-filled { fill: rgb(var(--c) / 0.12); }
.g-head { fill: rgb(var(--c)); }
.g-point { fill: rgb(var(--c)); stroke: rgb(var(--c)); }
.g-point.is-hollow { fill: rgb(var(--surface)); }
.g-label text { fill: rgb(var(--c)); }
</style>
