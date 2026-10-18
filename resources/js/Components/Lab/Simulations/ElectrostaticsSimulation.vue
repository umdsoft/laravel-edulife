<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    coulombForce, formatSI, fieldLinePaths, equipotentialSegments, defaultPotentialLevels,
} from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const charge1 = ref(5); // microcoulombs (slider unit) → ×1e-6 C
const charge2 = ref(-3);
const distanceCm = ref(300); // centre-to-centre separation r [cm]
const showFieldLines = ref(true);
const showEquipotential = ref(false);

const canvas = ref(null);
const ctx = ref(null);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));
const PX_PER_M = 100; // canvas scale: 100 px = 1 m

const pos1 = computed(() => ({ x: canvasConfig.value.width / 2 - (distanceCm.value / 100) * PX_PER_M / 2, y: 250 }));
const pos2 = computed(() => ({ x: canvasConfig.value.width / 2 + (distanceCm.value / 100) * PX_PER_M / 2, y: 250 }));

// Charges in SI (C, m). Screen y grows downward, which is just a mirrored frame:
// vector directions computed here map 1:1 back to the canvas.
const chargesSI = computed(() => [
    { q: charge1.value * 1e-6, x: pos1.value.x / PX_PER_M, y: pos1.value.y / PX_PER_M },
    { q: charge2.value * 1e-6, x: pos2.value.x / PX_PER_M, y: pos2.value.y / PX_PER_M },
]);
const distance = computed(() => distanceCm.value / 100); // meters
// Coulomb force on q₁ due to q₂ (vector): F = k·q₁q₂·(r₁ − r₂)/|r₁ − r₂|³, k = 8.9875e9
const forceOn1 = computed(() => {
    const [a, b] = chargesSI.value;
    return coulombForce(a.q, a, b.q, b);
});
const force = computed(() => forceOn1.value.magnitude); // N
const isAttractive = computed(() => forceOn1.value.attractive);
const isRepulsive = computed(() => forceOn1.value.repulsive);

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

// ── canvas helpers (geometry comes from physics/electricity.js, in metres) ──
const drawArrow = (c, x, y, dx, dy, color) => {
    const len = Math.hypot(dx, dy);
    if (len < 1) return;
    const ux = dx / len;
    const uy = dy / len;
    c.strokeStyle = color;
    c.fillStyle = color;
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(x, y);
    c.lineTo(x + dx - 8 * ux, y + dy - 8 * uy);
    c.stroke();
    c.beginPath();
    c.moveTo(x + dx, y + dy);
    c.lineTo(x + dx - 10 * ux - 6 * uy, y + dy - 10 * uy + 6 * ux);
    c.lineTo(x + dx - 10 * ux + 6 * uy, y + dy - 10 * uy - 6 * ux);
    c.closePath();
    c.fill();
};

const drawFieldLines = (c, charges, w, h, scale, { linesForMax, alpha }) => {
    const bounds = { xMin: 0, xMax: w / scale, yMin: 0, yMax: h / scale };
    fieldLinePaths(charges, bounds, { linesForMax }).forEach((path) => {
        const pts = path.points;
        if (pts.length < 2) return;
        const color = path.direction > 0 ? `rgba(239, 68, 68, ${alpha})` : `rgba(59, 130, 246, ${alpha})`;
        c.strokeStyle = color;
        c.lineWidth = 1.5;
        c.beginPath();
        c.moveTo(pts[0].x * scale, pts[0].y * scale);
        for (let k = 1; k < pts.length; k++) c.lineTo(pts[k].x * scale, pts[k].y * scale);
        c.stroke();
        // Arrowhead in the direction of E (lines traced against E point the other way)
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
        c.moveTo(ax + 6 * dx, ay + 6 * dy);
        c.lineTo(ax - 5 * dx - 4 * dy, ay - 5 * dy + 4 * dx);
        c.lineTo(ax - 5 * dx + 4 * dy, ay - 5 * dy - 4 * dx);
        c.closePath();
        c.fill();
    });
};

const drawEquipotentials = (c, charges, w, h, scale, color) => {
    const bounds = { xMin: 0, xMax: w / scale, yMin: 0, yMax: h / scale };
    c.strokeStyle = color;
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

const measure = () => {
    const f = formatSI(force.value, 'N', 4);
    emit('measurement', {
        name: `Kulon kuchi (q₁=${charge1.value} µC, q₂=${charge2.value} µC, r=${distanceCm.value} cm)`,
        value: Number.isFinite(force.value) ? f.value : '0',
        unit: f.unit,
    });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Equipotential lines V = Σkq/r = const (true superposition, not circles per charge)
    if (showEquipotential.value) drawEquipotentials(c, chargesSI.value, w, h, PX_PER_M, 'rgba(139, 92, 246, 0.45)');

    // Electric field lines traced along E = Σ E_i (start on +, end on −)
    if (showFieldLines.value) drawFieldLines(c, chargesSI.value, w, h, PX_PER_M, { linesForMax: 12, alpha: 0.45 });

    // Force arrows (Newton's 3rd law: equal magnitude, opposite direction), log length scale
    const F = force.value;
    if (F > 0 && Number.isFinite(F)) {
        const len = Math.max(15, Math.min(90, 20 + 15 * Math.log10(F / 1e-4)));
        const ux = forceOn1.value.fx / F;
        const uy = forceOn1.value.fy / F;
        const color = isAttractive.value ? '#22c55e' : '#ef4444';
        const r1 = 22 + Math.abs(charge1.value) * 2;
        const r2 = 22 + Math.abs(charge2.value) * 2;
        drawArrow(c, pos1.value.x + r1 * ux, pos1.value.y + r1 * uy, len * ux, len * uy, color);
        drawArrow(c, pos2.value.x - r2 * ux, pos2.value.y - r2 * uy, -len * ux, -len * uy, color);
    }

    // Charges
    drawCharge(c, pos1.value.x, pos1.value.y, charge1.value);
    drawCharge(c, pos2.value.x, pos2.value.y, charge2.value);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 210, 140);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`q₁ = ${charge1.value} µC`, 35, 45);
    c.fillText(`q₂ = ${charge2.value} µC`, 35, 65);
    c.fillText(`r = ${distanceCm.value} cm = ${distance.value.toFixed(2)} m`, 35, 85);
    c.fillStyle = isAttractive.value ? '#22c55e' : (isRepulsive.value ? '#ef4444' : '#94a3b8');
    c.fillText(`F = ${formatSI(F, 'N', 4).text}`, 35, 110);
    c.fillText(isAttractive.value ? '↔ Tortishish' : (isRepulsive.value ? '↔ Itarishish' : 'Kuch yo\'q (q = 0)'), 35, 130);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('F = k|q₁q₂|/r²,  k = 8.99·10⁹ N·m²/C²', 35, 150);
};

const drawCharge = (c, x, y, q) => {
    const radius = 20 + Math.abs(q) * 2;
    c.beginPath();
    c.arc(x, y, radius, 0, Math.PI * 2);
    c.fillStyle = q > 0 ? '#ef4444' : (q < 0 ? '#3b82f6' : '#64748b');
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText(q > 0 ? '+' : (q < 0 ? '−' : '0'), x, y + 6);
};

watch([charge1, charge2, distanceCm, showFieldLines, showEquipotential], () => {
    draw();
    emit('update:state', {
        ...(props.state || {}),
        charge1: charge1.value,
        charge2: charge2.value,
        distance: distance.value,
        force: force.value,
        attractive: isAttractive.value,
    });
});
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>q₁: <strong :style="{ color: charge1 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge1 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge1" :min="-10" :max="10" :step="1" />
            </div>
            <div class="control-group">
                <label>q₂: <strong :style="{ color: charge2 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge2 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge2" :min="-10" :max="10" :step="1" />
            </div>
            <div class="control-group">
                <label>Masofa r: <strong>{{ distanceCm }} cm</strong></label>
                <input type="range" v-model.number="distanceCm" :min="100" :max="600" :step="10" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlari</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" v-model="showEquipotential" />
                <span>Ekvipotensial</span>
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
    min-width: 120px;
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
