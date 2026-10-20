<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    electricField, electricPotential, formatSI, fieldLinePaths, equipotentialSegments, defaultPotentialLevels,
    ELECTRIC_METERS, createSigFigMeter,
} from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const charge1 = ref(10); // microcoulombs (slider unit) → ×1e-6 C
const charge2 = ref(-10);
const showFieldLines = ref(true);
const showEquipotential = ref(true);

const canvas = ref(null);
const ctx = ref(null);

const pos1 = ref({ x: 300, y: 250 });
const pos2 = ref({ x: 500, y: 250 });
// Probe point where E and V are measured (click on the canvas to move it)
const probe = ref({ x: 400, y: 150 });

const canvasConfig = computed(() => ({ width: 800, height: 500 }));
const PX_PER_M = 100; // canvas scale: 100 px = 1 m (charges are 2 m apart)

const chargesSI = computed(() => [
    { q: charge1.value * 1e-6, x: pos1.value.x / PX_PER_M, y: pos1.value.y / PX_PER_M },
    { q: charge2.value * 1e-6, x: pos2.value.x / PX_PER_M, y: pos2.value.y / PX_PER_M },
]);
// Superposition at the probe: E = Σ k·qᵢ·r̂ᵢ/rᵢ², V = Σ k·qᵢ/rᵢ
const probeField = computed(() => electricField(chargesSI.value, probe.value.x / PX_PER_M, probe.value.y / PX_PER_M));
const probePotential = computed(() => electricPotential(chargesSI.value, probe.value.x / PX_PER_M, probe.value.y / PX_PER_M));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

// Field meter on the probe: 4 significant digits, ±0.1 % scatter.
const fieldMeter = createSigFigMeter(ELECTRIC_METERS.fieldMeter, 'electric-field');
const measure = () => {
    const E = probeField.value.magnitude;
    if (!Number.isFinite(E)) return; // probe exactly on a point charge: E is singular
    const xm = probe.value.x / PX_PER_M;
    const ym = probe.value.y / PX_PER_M;
    const r = fieldMeter.read(E, [charge1.value, charge2.value, xm, ym]);
    emit('measurement', {
        name: `Maydon kuchlanganligi (x=${xm.toFixed(2)} m, y=${ym.toFixed(2)} m)`,
        value: r.text,
        unit: r.unit,
    });
};

// The probe stands on the nodes of a 10 cm coordinate grid (like the marked
// field-mapping board of a real lab): a click snaps to the nearest node.
const PROBE_GRID_M = 0.1;
const snap = (px) => Math.round(px / (PROBE_GRID_M * PX_PER_M)) * PROBE_GRID_M * PX_PER_M;
const onCanvasClick = (ev) => {
    if (!canvas.value) return;
    const rect = canvas.value.getBoundingClientRect();
    const sx = canvas.value.width / rect.width;
    const sy = canvas.value.height / rect.height;
    probe.value = { x: snap((ev.clientX - rect.left) * sx), y: snap((ev.clientY - rect.top) * sy) };
};

// ── canvas helpers (geometry comes from physics/electricity.js, in metres) ──
const drawFieldLines = (c, charges, w, h, scale) => {
    const bounds = { xMin: 0, xMax: w / scale, yMin: 0, yMax: h / scale };
    fieldLinePaths(charges, bounds, { linesForMax: 16 }).forEach((path) => {
        const pts = path.points;
        if (pts.length < 2) return;
        const color = path.direction > 0 ? 'rgba(239, 68, 68, 0.5)' : 'rgba(59, 130, 246, 0.5)';
        c.strokeStyle = color;
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(pts[0].x * scale, pts[0].y * scale);
        for (let k = 1; k < pts.length; k++) c.lineTo(pts[k].x * scale, pts[k].y * scale);
        c.stroke();
        // Arrowhead along E (from + to −)
        const m = Math.floor(pts.length / 2);
        if (m < 1 || m + 1 >= pts.length) return;
        let dx = (pts[m + 1].x - pts[m - 1].x) * path.direction;
        let dy = (pts[m + 1].y - pts[m - 1].y) * path.direction;
        const l = Math.hypot(dx, dy) || 1;
        dx /= l;
        dy /= l;
        const ax = pts[m].x * scale;
        const ay = pts[m].y * scale;
        c.fillStyle = color;
        c.beginPath();
        c.moveTo(ax + 7 * dx, ay + 7 * dy);
        c.lineTo(ax - 5 * dx - 5 * dy, ay - 5 * dy + 5 * dx);
        c.lineTo(ax - 5 * dx + 5 * dy, ay - 5 * dy - 5 * dx);
        c.closePath();
        c.fill();
    });
};

const drawEquipotentials = (c, charges, w, h, scale) => {
    const bounds = { xMin: 0, xMax: w / scale, yMin: 0, yMax: h / scale };
    c.strokeStyle = 'rgba(139, 92, 246, 0.45)';
    c.lineWidth = 1;
    c.setLineDash([5, 5]);
    equipotentialSegments(charges, bounds, defaultPotentialLevels(charges), 0.1).forEach(({ segments }) => {
        c.beginPath();
        segments.forEach(([x1, y1, x2, y2]) => {
            c.moveTo(x1 * scale, y1 * scale);
            c.lineTo(x2 * scale, y2 * scale);
        });
        c.stroke();
    });
    c.setLineDash([]);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Grid
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let x = 0; x < w; x += 50) { c.beginPath(); c.moveTo(x, 0); c.lineTo(x, h); c.stroke(); }
    for (let y = 0; y < h; y += 50) { c.beginPath(); c.moveTo(0, y); c.lineTo(w, y); c.stroke(); }

    // Equipotential lines: contours of the superposed potential V = Σkq/r
    if (showEquipotential.value) drawEquipotentials(c, chargesSI.value, w, h, PX_PER_M);

    // Electric field lines: curves tangent to E = ΣEᵢ, leaving + and ending on −
    if (showFieldLines.value) drawFieldLines(c, chargesSI.value, w, h, PX_PER_M);

    // Charges
    drawCharge(c, pos1.value.x, pos1.value.y, charge1.value);
    drawCharge(c, pos2.value.x, pos2.value.y, charge2.value);

    // Probe with E vector (log length scale)
    const E = probeField.value;
    c.fillStyle = '#facc15';
    c.beginPath();
    c.arc(probe.value.x, probe.value.y, 4, 0, Math.PI * 2);
    c.fill();
    if (Number.isFinite(E.magnitude) && E.magnitude > 0) {
        const len = Math.max(15, Math.min(70, 10 * Math.log10(E.magnitude)));
        const ux = E.ex / E.magnitude;
        const uy = E.ey / E.magnitude;
        const tx = probe.value.x + len * ux;
        const ty = probe.value.y + len * uy;
        c.strokeStyle = '#facc15';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(probe.value.x, probe.value.y);
        c.lineTo(tx, ty);
        c.stroke();
        c.beginPath();
        c.moveTo(tx + 6 * ux, ty + 6 * uy);
        c.lineTo(tx - 4 * ux - 5 * uy, ty - 4 * uy + 5 * ux);
        c.lineTo(tx - 4 * ux + 5 * uy, ty - 4 * uy - 5 * ux);
        c.closePath();
        c.fill();
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 250, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`q₁ = ${charge1.value} µC`, 35, 45);
    c.fillText(`q₂ = ${charge2.value} µC`, 35, 65);
    c.fillStyle = '#facc15';
    c.fillText(`Nuqtada: E = ${formatSI(E.magnitude, 'V/m', 4).text}`, 35, 90);
    c.fillText(`φ = ${formatSI(probePotential.value, 'V', 4).text}`, 35, 110);
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText('E = Σ kqᵢ/rᵢ²,  φ = Σ kqᵢ/rᵢ  (1 katak = 0,5 m)', 35, 135);
    c.fillText('Nuqtani tanlash uchun maydonga bosing', 35, 155);
};

const drawCharge = (c, x, y, q) => {
    const radius = 25;
    c.beginPath();
    c.arc(x, y, radius, 0, Math.PI * 2);
    c.fillStyle = q > 0 ? '#ef4444' : (q < 0 ? '#3b82f6' : '#64748b');
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 18px Inter';
    c.textAlign = 'center';
    c.fillText(q > 0 ? '+' : (q < 0 ? '−' : '0'), x, y + 6);
};

watch([charge1, charge2, showFieldLines, showEquipotential, probe], () => {
    draw();
    emit('update:state', {
        ...(props.state || {}),
        charge1: charge1.value,
        charge2: charge2.value,
        probe: { x: probe.value.x / PX_PER_M, y: probe.value.y / PX_PER_M },
        fieldStrength: probeField.value.magnitude,
        potential: probePotential.value,
    });
});
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas"
            @click="onCanvasClick" />
        <div class="controls">
            <div class="control-group">
                <label>Zaryad 1: <strong :style="{ color: charge1 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge1 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge1" :min="-20" :max="20" :step="2" />
            </div>
            <div class="control-group">
                <label>Zaryad 2: <strong :style="{ color: charge2 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge2 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge2" :min="-20" :max="20" :step="2" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlari</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" v-model="showEquipotential" />
                <span>Ekvipotensial sirtlar</span>
            </label>
            <button @click="measure" class="btn">📏</button>
        </div>
    </div>
</template>

<style scoped>
.simulation {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.canvas {
    border-radius: 12px;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
    cursor: crosshair;
}

.controls {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 150px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.85rem;
    cursor: pointer;
}

.checkbox input {
    width: 16px;
    height: 16px;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
