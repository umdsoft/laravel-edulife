<script setup>
/**
 * GraphBuilderDialog — the "Grafik" button's builder: ranges, functions, points, segments / vectors,
 * polylines (v–t, x–t, piecewise) and axis labels, with a live preview. Keys the builder has no
 * fields for (polygons, circles, labels, angles, ticks) are kept as they were and editable on the
 * "JSON" tab.
 *   <GraphBuilderDialog :show="open" :source="existingJsonOrNull" @save="(json) => …" @close="open = false" />
 */
import { computed, ref, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Alert from '@/Components/UI/Alert.vue';
import GraphView from './GraphView.vue';
import { ALLOWED_FUNCTIONS, exprError } from '@/Utils/graph/expr';
import { GRAPH_COLORS, blankGraphSpec, stringifyGraphSpec, validateGraphSpec } from '@/Utils/graph/spec';

const props = defineProps({
    show: Boolean,
    source: { type: String, default: null }, // JSON of the block being edited; null = new graph
});
const emit = defineEmits(['save', 'close']);

const COLOR_LABELS = { blue: "ko'k", red: 'qizil', green: 'yashil', orange: "to'q sariq", purple: 'binafsha', gray: 'kulrang' };
const EXTRA_KEYS = ['ticks', 'polygons', 'circles', 'labels', 'angles'];

const tab = ref('builder'); // builder | json
const m = ref(null);
const jsonText = ref('');
const jsonError = ref('');

const str = (v) => (v === undefined || v === null ? '' : String(v));
const num = (s) => {
    const t = String(s ?? '').trim().replace(',', '.').replace('−', '-');
    if (t === '') return undefined;
    const n = Number(t);
    return Number.isFinite(n) ? n : NaN;
};

function toModel(spec) {
    const s = spec || blankGraphSpec();
    const lines = [
        ...(s.segments || []).map((it) => ({ kind: 'segment', ...pair(it), label: str(it.label), dashed: !!it.dashed, color: it.color || '' })),
        ...(s.vectors || []).map((it) => ({ kind: 'vector', ...pair(it), label: str(it.label), dashed: false, color: it.color || '' })),
    ];
    const extra = {};
    for (const k of EXTRA_KEYS) if (k in s) extra[k] = s[k];
    return {
        x0: str(s.x?.[0]), x1: str(s.x?.[1]), y0: str(s.y?.[0]), y1: str(s.y?.[1]),
        grid: s.grid !== false, equal: !!s.equal, width: str(s.width),
        axisX: str(s.axes?.x), axisY: str(s.axes?.y),
        functions: (s.functions || []).map((f) => ({ expr: f.expr, label: str(f.label), d0: str(f.domain?.[0]), d1: str(f.domain?.[1]), dashed: !!f.dashed, color: f.color || '' })),
        points: (s.points || []).map((p) => ({ x: str(p.x), y: str(p.y), label: str(p.label), hollow: !!p.hollow, color: p.color || '' })),
        lines,
        polylines: (s.polylines || []).map((p) => ({ label: str(p.label), dashed: !!p.dashed, color: p.color || '', pts: p.points.map(([x, y]) => ({ x: str(x), y: str(y) })) })),
        extra,
    };
}
const pair = (it) => ({ x1: str(it.from?.[0]), y1: str(it.from?.[1]), x2: str(it.to?.[0]), y2: str(it.to?.[1]) });

function toSpec(model) {
    const spec = { x: [num(model.x0), num(model.x1)], y: [num(model.y0), num(model.y1)] };
    if (!model.grid) spec.grid = false;
    if (model.equal) spec.equal = true;
    if (model.extra.ticks !== undefined) spec.ticks = model.extra.ticks;
    if (model.axisX.trim() || model.axisY.trim()) {
        spec.axes = {};
        if (model.axisX.trim()) spec.axes.x = model.axisX.trim();
        if (model.axisY.trim()) spec.axes.y = model.axisY.trim();
    }
    if (model.width.trim()) spec.width = num(model.width);
    const opt = (o, it) => {
        if (it.label?.trim()) o.label = it.label.trim();
        if (it.dashed) o.dashed = true;
        if (it.color) o.color = it.color;
        return o;
    };
    const fns = model.functions.filter((f) => f.expr.trim()).map((f) => {
        const o = { expr: f.expr.trim() };
        if (f.d0.trim() !== '' || f.d1.trim() !== '') o.domain = [num(f.d0 || model.x0), num(f.d1 || model.x1)];
        return opt(o, f);
    });
    if (fns.length) spec.functions = fns;
    const polys = model.polylines.map((p) => opt({ points: p.pts.filter((q) => q.x !== '' || q.y !== '').map((q) => [num(q.x), num(q.y)]) }, p)).filter((p) => p.points.length);
    if (polys.length) spec.polylines = polys;
    const pts = model.points.filter((p) => p.x !== '' || p.y !== '').map((p) => {
        const o = opt({ x: num(p.x), y: num(p.y) }, p);
        if (p.hollow) o.hollow = true;
        return o;
    });
    if (pts.length) spec.points = pts;
    const seg = model.lines.filter((l) => l.kind === 'segment').map((l) => opt({ from: [num(l.x1), num(l.y1)], to: [num(l.x2), num(l.y2)] }, l));
    const vec = model.lines.filter((l) => l.kind === 'vector').map((l) => opt({ from: [num(l.x1), num(l.y1)], to: [num(l.x2), num(l.y2)] }, { ...l, dashed: false }));
    if (seg.length) spec.segments = seg;
    if (vec.length) spec.vectors = vec;
    for (const k of ['polygons', 'circles', 'labels', 'angles']) if (model.extra[k]?.length) spec[k] = model.extra[k];
    return spec;
}

watch(() => props.show, (open) => {
    if (!open) return;
    tab.value = 'builder';
    jsonError.value = '';
    let spec = null;
    if (props.source) {
        try {
            spec = JSON.parse(props.source);
        } catch {
            spec = null;
        }
        if (!spec || typeof spec !== 'object' || Array.isArray(spec)) {
            // unreadable block: open the JSON tab so the teacher can fix it
            m.value = toModel(null);
            jsonText.value = props.source;
            tab.value = 'json';
            jsonError.value = "Grafik JSON noto'g'ri yozilgan — tuzating yoki konstruktorda qaytadan chizing.";
            return;
        }
    }
    m.value = toModel(spec);
}, { immediate: true });

const spec = computed(() => (m.value ? toSpec(m.value) : null));
const errors = computed(() => (spec.value ? validateGraphSpec(spec.value) : []));
const json = computed(() => (spec.value ? stringifyGraphSpec(spec.value) : ''));

const switchTab = (to) => {
    if (to === 'json') {
        jsonText.value = json.value;
        jsonError.value = '';
    } else if (tab.value === 'json') {
        try {
            const parsed = JSON.parse(jsonText.value);
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) throw new Error('not an object');
            m.value = toModel(parsed);
            jsonError.value = '';
        } catch {
            jsonError.value = "JSON noto'g'ri — konstruktorga o'tib bo'lmaydi.";
            return;
        }
    }
    tab.value = to;
};

const jsonErrors = computed(() => {
    if (tab.value !== 'json') return [];
    try {
        return validateGraphSpec(JSON.parse(jsonText.value));
    } catch {
        return ["Grafik JSON noto'g'ri yozilgan"];
    }
});
const jsonSpec = computed(() => {
    if (tab.value !== 'json' || jsonErrors.value.length) return null;
    return JSON.parse(jsonText.value);
});

const currentErrors = computed(() => (tab.value === 'json' ? jsonErrors.value : errors.value));
const previewSpec = computed(() => (tab.value === 'json' ? jsonSpec.value : (errors.value.length ? null : spec.value)));

const save = () => {
    if (currentErrors.value.length) return;
    emit('save', tab.value === 'json' ? stringifyGraphSpec(JSON.parse(jsonText.value)) : json.value);
};

const addFunction = () => m.value.functions.push({ expr: '', label: '', d0: '', d1: '', dashed: false, color: '' });
const addPoint = () => m.value.points.push({ x: '', y: '', label: '', hollow: false, color: '' });
const addLine = (kind) => m.value.lines.push({ kind, x1: '0', y1: '0', x2: '', y2: '', label: '', dashed: false, color: '' });
const addPolyline = () => m.value.polylines.push({ label: '', dashed: false, color: '', pts: [{ x: '0', y: '0' }, { x: '', y: '' }] });

const field = 'focus-ring h-9 w-full min-w-0 rounded-sm border border-border-strong bg-surface px-2 text-sm text-fg placeholder:text-fg-subtle';
const small = 'focus-ring h-9 w-16 shrink-0 rounded-sm border border-border-strong bg-surface px-2 text-sm text-fg';
const sectionTitle = 'text-13 font-semibold text-fg';
</script>

<template>
    <Modal :show="show" title="Grafik" description="Oraliq, funksiyalar, nuqtalar va kesmalarni kiriting — grafik darhol chizilib boradi." size="4xl" @close="emit('close')">
        <div v-if="m" class="grid gap-5 lg:grid-cols-2">
            <div class="min-w-0 space-y-5">
                <div class="inline-flex rounded-sm bg-surface-2 p-0.5" role="tablist" aria-label="Grafik muharriri">
                    <button v-for="t in [['builder', 'Konstruktor'], ['json', 'JSON']]" :key="t[0]" type="button" role="tab" :aria-selected="tab === t[0]"
                        :class="['focus-ring rounded-sm px-3 py-1 text-13 font-medium', tab === t[0] ? 'bg-surface text-fg shadow-xs' : 'text-fg-muted hover:text-fg']"
                        @click="switchTab(t[0])">{{ t[1] }}</button>
                </div>

                <template v-if="tab === 'builder'">
                    <fieldset class="space-y-2">
                        <legend :class="sectionTitle">Oraliq va o'qlar</legend>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <label class="text-13 text-fg-muted">x min<input v-model="m.x0" :class="field" inputmode="decimal" /></label>
                            <label class="text-13 text-fg-muted">x max<input v-model="m.x1" :class="field" inputmode="decimal" /></label>
                            <label class="text-13 text-fg-muted">y min<input v-model="m.y0" :class="field" inputmode="decimal" /></label>
                            <label class="text-13 text-fg-muted">y max<input v-model="m.y1" :class="field" inputmode="decimal" /></label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-13 text-fg-muted">x o'qi nomi<input v-model="m.axisX" :class="field" maxlength="40" placeholder="x yoki t, s" /></label>
                            <label class="text-13 text-fg-muted">y o'qi nomi<input v-model="m.axisY" :class="field" maxlength="40" placeholder="y yoki v, m/s" /></label>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-fg">
                            <label class="inline-flex items-center gap-2"><input v-model="m.grid" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> To'r</label>
                            <label class="inline-flex items-center gap-2"><input v-model="m.equal" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> Teng masshtab (geometriya)</label>
                            <label class="inline-flex items-center gap-2">Kenglik, %<input v-model="m.width" :class="small" inputmode="numeric" placeholder="100" /></label>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-2">
                        <legend :class="sectionTitle">Funksiyalar <span class="font-normal text-fg-subtle">y = f(x)</span></legend>
                        <div v-for="(f, i) in m.functions" :key="`f${i}`" class="rounded-md border border-border p-2">
                            <div class="flex items-center gap-2">
                                <input v-model="f.expr" :class="[field, 'font-mono']" placeholder="x^2 - 2" :aria-label="`Funksiya ${i + 1}`" />
                                <IconButton :icon="TrashIcon" label="O'chirish" size="sm" @click="m.functions.splice(i, 1)" />
                            </div>
                            <p v-if="f.expr.trim() && exprError(f.expr)" class="mt-1 text-13 text-danger">{{ exprError(f.expr) }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-13 text-fg-muted">
                                <input v-model="f.label" :class="[small, 'w-24']" placeholder="nomi" maxlength="40" aria-label="Nomi" />
                                <span>x:</span>
                                <input v-model="f.d0" :class="small" placeholder="dan" inputmode="decimal" aria-label="Aniqlanish sohasi boshi" />
                                <input v-model="f.d1" :class="small" placeholder="gacha" inputmode="decimal" aria-label="Aniqlanish sohasi oxiri" />
                                <select v-model="f.color" :class="[small, 'w-28']" aria-label="Rang">
                                    <option value="">rang: avto</option>
                                    <option v-for="c in GRAPH_COLORS" :key="c" :value="c">{{ COLOR_LABELS[c] }}</option>
                                </select>
                                <label class="inline-flex items-center gap-1.5"><input v-model="f.dashed" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> punktir</label>
                            </div>
                        </div>
                        <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addFunction">Funksiya</Button>
                        <p class="text-xs text-fg-subtle">Mumkin: <code class="font-mono">+ - * / ^ ( ) |x|</code>, <code class="font-mono">pi</code>, <code class="font-mono">e</code>, {{ ALLOWED_FUNCTIONS.join(', ') }}. O'zgaruvchi — x (yoki t).</p>
                    </fieldset>

                    <fieldset class="space-y-2">
                        <legend :class="sectionTitle">Siniq chiziqlar <span class="font-normal text-fg-subtle">v–t, x–t, bo'lakli grafik</span></legend>
                        <div v-for="(p, i) in m.polylines" :key="`pl${i}`" class="rounded-md border border-border p-2">
                            <div class="flex flex-wrap items-center gap-2 text-13 text-fg-muted">
                                <input v-model="p.label" :class="[small, 'w-24']" placeholder="nomi" maxlength="40" aria-label="Nomi" />
                                <select v-model="p.color" :class="[small, 'w-28']" aria-label="Rang">
                                    <option value="">rang: avto</option>
                                    <option v-for="c in GRAPH_COLORS" :key="c" :value="c">{{ COLOR_LABELS[c] }}</option>
                                </select>
                                <label class="inline-flex items-center gap-1.5"><input v-model="p.dashed" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> punktir</label>
                                <IconButton :icon="TrashIcon" label="Chiziqni o'chirish" size="sm" class="ml-auto" @click="m.polylines.splice(i, 1)" />
                            </div>
                            <table class="mt-2 w-full text-13">
                                <thead><tr class="text-left text-fg-subtle"><th class="w-8 font-normal">#</th><th class="font-normal">x</th><th class="font-normal">y</th><th class="w-9" /></tr></thead>
                                <tbody>
                                    <tr v-for="(q, j) in p.pts" :key="j">
                                        <td class="text-fg-subtle">{{ j + 1 }}</td>
                                        <td class="pr-1"><input v-model="q.x" :class="field" inputmode="decimal" :aria-label="`${j + 1}-nuqta x`" /></td>
                                        <td class="pr-1"><input v-model="q.y" :class="field" inputmode="decimal" :aria-label="`${j + 1}-nuqta y`" /></td>
                                        <td><IconButton :icon="TrashIcon" label="Nuqtani o'chirish" size="sm" :disabled="p.pts.length <= 2" @click="p.pts.splice(j, 1)" /></td>
                                    </tr>
                                </tbody>
                            </table>
                            <Button size="sm" variant="ghost" :icon-left="PlusIcon" class="mt-1" @click="p.pts.push({ x: '', y: '' })">Nuqta qo'shish</Button>
                        </div>
                        <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addPolyline">Siniq chiziq</Button>
                    </fieldset>

                    <fieldset class="space-y-2">
                        <legend :class="sectionTitle">Nuqtalar</legend>
                        <div v-for="(p, i) in m.points" :key="`p${i}`" class="flex flex-wrap items-center gap-2 text-13 text-fg-muted">
                            <span>(</span><input v-model="p.x" :class="small" inputmode="decimal" placeholder="x" aria-label="x" />
                            <span>;</span><input v-model="p.y" :class="small" inputmode="decimal" placeholder="y" aria-label="y" /><span>)</span>
                            <input v-model="p.label" :class="[small, 'w-20']" placeholder="A" maxlength="40" aria-label="Nomi" />
                            <label class="inline-flex items-center gap-1.5"><input v-model="p.hollow" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> bo'sh</label>
                            <IconButton :icon="TrashIcon" label="O'chirish" size="sm" @click="m.points.splice(i, 1)" />
                        </div>
                        <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addPoint">Nuqta</Button>
                    </fieldset>

                    <fieldset class="space-y-2">
                        <legend :class="sectionTitle">Kesmalar va vektorlar</legend>
                        <div v-for="(l, i) in m.lines" :key="`l${i}`" class="flex flex-wrap items-center gap-2 text-13 text-fg-muted">
                            <select v-model="l.kind" :class="[small, 'w-24']" aria-label="Turi">
                                <option value="segment">kesma</option>
                                <option value="vector">vektor</option>
                            </select>
                            <span>(</span><input v-model="l.x1" :class="small" inputmode="decimal" aria-label="Boshi x" />
                            <span>;</span><input v-model="l.y1" :class="small" inputmode="decimal" aria-label="Boshi y" /><span>) →</span>
                            <span>(</span><input v-model="l.x2" :class="small" inputmode="decimal" aria-label="Oxiri x" />
                            <span>;</span><input v-model="l.y2" :class="small" inputmode="decimal" aria-label="Oxiri y" /><span>)</span>
                            <input v-model="l.label" :class="[small, 'w-20']" placeholder="nomi" maxlength="40" aria-label="Nomi" />
                            <label v-if="l.kind === 'segment'" class="inline-flex items-center gap-1.5"><input v-model="l.dashed" type="checkbox" class="h-4 w-4 rounded border-border-strong" /> punktir</label>
                            <IconButton :icon="TrashIcon" label="O'chirish" size="sm" @click="m.lines.splice(i, 1)" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addLine('segment')">Kesma</Button>
                            <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addLine('vector')">Vektor</Button>
                        </div>
                    </fieldset>
                    <p v-if="Object.keys(m.extra).length" class="text-13 text-fg-subtle">
                        Bu grafikda konstruktorda yo'q qismlar ham bor ({{ Object.keys(m.extra).join(', ') }}) — ular saqlanadi, «JSON» bo'limida tahrirlanadi.
                    </p>
                </template>

                <template v-else>
                    <label class="block text-13 text-fg-muted">
                        Grafik JSON (ko'pburchak, aylana, yozuv, burchak — shu yerda; namunalar: «Rasm va grafiklar» qo'llanmasi)
                        <textarea v-model="jsonText" rows="16" spellcheck="false"
                            class="focus-ring mt-1 w-full rounded-sm border border-border-strong bg-surface p-2 font-mono text-[13px] leading-5 text-fg" />
                    </label>
                    <Alert v-if="jsonError" variant="warning">{{ jsonError }}</Alert>
                </template>
            </div>

            <div class="order-first min-w-0 lg:sticky lg:top-0 lg:order-none lg:self-start">
                <p :class="sectionTitle">Ko'rinishi</p>
                <div class="mt-2 rounded-md border border-border bg-surface p-2">
                    <GraphView v-if="previewSpec" :spec="previewSpec" />
                    <p v-else class="px-2 py-8 text-center text-13 text-fg-subtle">Xatolar tuzatilgach, grafik shu yerda chiziladi.</p>
                </div>
                <ul v-if="currentErrors.length" class="mt-3 space-y-1 text-13 text-danger" aria-live="polite">
                    <li v-for="(e, i) in currentErrors.slice(0, 6)" :key="i">• {{ e }}</li>
                </ul>
            </div>
        </div>

        <template #footer>
            <Button variant="secondary" @click="emit('close')">Bekor qilish</Button>
            <Button :disabled="currentErrors.length > 0" @click="save">{{ source ? 'Grafikni yangilash' : "Grafikni qo'yish" }}</Button>
        </template>
    </Modal>
</template>
