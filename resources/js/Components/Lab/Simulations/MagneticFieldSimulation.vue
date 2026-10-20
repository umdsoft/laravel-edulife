<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    barMagnetPoles, poleField, traceFieldLine, barMagnetAxialField, dipoleAxialField, teslameterReading, teslameterResolution,
} from '../physics/magnetism.js';
import { createRng, seedFrom, formatReading } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetStrength = ref(50);
const showFieldLines = ref(true);

const canvas = ref(null);
const ctx = ref(null);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

// Geometry: the 200 px bar represents a 10 cm magnet → 0.5 mm per pixel.
const MAGNET_W = 200;
const MAGNET_H = 60;
const M_PER_PX = 0.10 / MAGNET_W;
// Effective poles sit slightly inside the ends of a real bar magnet (≈ 0.85·length apart).
const POLE_SEPARATION = 0.85 * 0.10; // m
// Magnetic moment at 100 % ≈ 2 A·m² (typical 10 cm alnico/ceramic bar magnet).
const MAX_MOMENT = 2.0; // A·m²

const moment = computed(() => (magnetStrength.value / 100) * MAX_MOMENT);
// N pole on the left (red), S pole on the right (blue), centred at the origin.
const poles = computed(() => barMagnetPoles({ moment: moment.value, poleSeparation: POLE_SEPARATION, northOnLeft: true }));

/** Field at a canvas pixel (screen frame, y down) — SI tesla. */
const fieldAtPx = (px, py) => {
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    return poleField((px - w / 2) * M_PER_PX, (py - h / 2) * M_PER_PX, poles.value);
};

const formatB = (B) => (B >= 1e-3 ? `${(B * 1e3).toFixed(2)} mT` : `${(B * 1e6).toFixed(0)} µT`);

// Teslameter (axial Hall probe) on the magnet axis, beyond the S end, at distance r from
// the magnet centre. Exact field of the two-pole model; reading: 20/200/2000 mT
// auto-range, σ = 0.1 %, zeroed before use (Earth's field compensated).
const probeDistance = ref(10); // cm from the magnet centre
const probeField = computed(() => barMagnetAxialField(moment.value, POLE_SEPARATION, probeDistance.value / 100)); // T
const probeDipole = computed(() => dipoleAxialField(moment.value, probeDistance.value / 100)); // T (formula r ≫ ℓ)
const lastReading = ref(null); // mT
const readCounts = new Map();
const measure = () => {
    const key = seedFrom('MagneticFieldSimulation', magnetStrength.value, probeDistance.value);
    const k = readCounts.get(key) ?? 0;
    readCounts.set(key, k + 1);
    const value = teslameterReading(probeField.value * 1000, createRng(seedFrom(key, k)));
    lastReading.value = value;
    emit('measurement', { name: `Magnit induksiya (o'qda, r = ${probeDistance.value} sm)`, value, unit: 'mT' });
};
const fmtMilliTesla = (mT) => formatReading(mT, teslameterResolution(mT));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const drawFieldLines = (c, w, h) => {
    const cx = w / 2;
    const cy = h / 2;
    const nX = cx - (POLE_SEPARATION / 2) / M_PER_PX;
    const sX = cx + (POLE_SEPARATION / 2) / M_PER_PX;
    const field = (x, y) => fieldAtPx(x, y);
    const stop = (x, y) => Math.hypot(x - sX, y - cy) < 6 || x < -40 || x > w + 40 || y < -40 || y > h + 40;
    const numLines = 18;
    for (let i = 0; i < numLines; i++) {
        // Start on a small circle round the N pole and follow B (lines leave N, enter S).
        const a = (i / numLines) * 2 * Math.PI + Math.PI / numLines;
        const pts = traceFieldLine(field, nX + 6 * Math.cos(a), cy + 6 * Math.sin(a), { step: 4, maxSteps: 900, stop });
        c.strokeStyle = `hsla(${200 + (i % 9) * 10}, 70%, 60%, 0.6)`;
        c.lineWidth = 1.6;
        c.beginPath();
        pts.forEach(([x, y], k) => (k === 0 ? c.moveTo(x, y) : c.lineTo(x, y)));
        c.stroke();

        // Direction arrow on the first visible point outside the magnet body
        const outside = pts.filter(([x, y]) => Math.abs(x - cx) > MAGNET_W / 2 + 10 || Math.abs(y - cy) > MAGNET_H / 2 + 10);
        const p = outside[Math.floor(outside.length / 2)];
        if (p) {
            const f = field(p[0], p[1]);
            const ang = Math.atan2(f.By, f.Bx);
            c.save();
            c.translate(p[0], p[1]);
            c.rotate(ang);
            c.fillStyle = 'rgba(148, 197, 255, 0.85)';
            c.beginPath();
            c.moveTo(6, 0);
            c.lineTo(-4, -4);
            c.lineTo(-4, 4);
            c.closePath();
            c.fill();
            c.restore();
        }
    }
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const centerX = w / 2;
    const centerY = h / 2;
    const magnetW = MAGNET_W;
    const magnetH = MAGNET_H;

    // Field lines traced from the computed field B(x, y)
    if (showFieldLines.value) drawFieldLines(c, w, h);

    // Magnet
    // North pole (red)
    c.fillStyle = '#ef4444';
    c.fillRect(centerX - magnetW / 2, centerY - magnetH / 2, magnetW / 2, magnetH);
    c.fillStyle = 'white';
    c.font = 'bold 24px Inter';
    c.textAlign = 'center';
    c.fillText('N', centerX - magnetW / 4, centerY + 8);

    // South pole (blue)
    c.fillStyle = '#3b82f6';
    c.fillRect(centerX, centerY - magnetH / 2, magnetW / 2, magnetH);
    c.fillStyle = 'white';
    c.fillText('S', centerX + magnetW / 4, centerY + 8);

    // Magnet border
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.strokeRect(centerX - magnetW / 2, centerY - magnetH / 2, magnetW, magnetH);

    // Compass needles animation
    const compasses = [
        { x: centerX - magnetW / 2 - 80, y: centerY },
        { x: centerX + magnetW / 2 + 80, y: centerY },
        { x: centerX, y: centerY - 120 },
        { x: centerX, y: centerY + 120 },
    ];

    compasses.forEach((comp) => {
        // The needle's north end points along the local field B.
        const f = fieldAtPx(comp.x, comp.y);
        const angle = Math.atan2(f.By, f.Bx);
        comp.B = f.B;

        c.save();
        c.translate(comp.x, comp.y);
        c.rotate(angle);

        // Compass circle
        c.beginPath();
        c.arc(0, 0, 20, 0, Math.PI * 2);
        c.fillStyle = '#1e293b';
        c.fill();
        c.strokeStyle = '#475569';
        c.lineWidth = 2;
        c.stroke();

        // Needle
        c.beginPath();
        c.moveTo(-12, 0);
        c.lineTo(12, 0);
        c.strokeStyle = '#ef4444';
        c.lineWidth = 3;
        c.stroke();

        // Arrow head
        c.beginPath();
        c.moveTo(12, 0);
        c.lineTo(6, -4);
        c.lineTo(6, 4);
        c.closePath();
        c.fillStyle = '#ef4444';
        c.fill();

        c.restore();

        c.fillStyle = '#cbd5e1';
        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText(formatB(comp.B), comp.x, comp.y + 36);
    });

    // Hall probe on the axis (right of the magnet, beyond S) — 1 px = 0.5 mm
    const probeX = centerX + (probeDistance.value / 100) / M_PER_PX;
    c.fillStyle = '#22c55e';
    c.fillRect(probeX - 2, centerY - 4, 4, 8);
    c.fillRect(probeX - 1, centerY + 4, 2, 40);
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText(`zond, r = ${probeDistance.value} sm`, probeX, centerY + 58);

    // Probe readout
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 250, h - 92, 230, 72);
    c.fillStyle = '#22c55e';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Teslametr: ${lastReading.value === null ? "— (O'lchash)" : `${fmtMilliTesla(lastReading.value)} mT`}`, w - 238, h - 70);
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText(`Dipol formulasi: ${(probeDipole.value * 1e3).toFixed(3)} mT`, w - 238, h - 50);
    c.fillText('(formula faqat r ≫ magnit uzunligi)', w - 238, h - 32);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Magnit kuchi: ${magnetStrength.value}%`, 35, 45);
    c.fillText(`Magnit momenti: m = ${moment.value.toFixed(2)} A·m²`, 35, 63);
    c.fillText('🔴 N - Shimoliy qutb', 35, 83);
    c.fillText('🔵 S - Janubiy qutb', 35, 101);
    c.fillStyle = '#94a3b8';
    c.fillText('Tashqarida chiziqlar: N → S', 35, 121);
    c.fillText('Kompas N uchi B bo\'ylab', 35, 135);
};

watch([magnetStrength, probeDistance], () => { lastReading.value = null; });
watch([magnetStrength, showFieldLines, probeDistance, lastReading], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Magnit kuchi: <strong>{{ magnetStrength }}%</strong></label>
                <input type="range" v-model.number="magnetStrength" :min="10" :max="100" :step="10" />
            </div>
            <div class="control-group">
                <label>Zond masofasi r: <strong>{{ probeDistance }} sm</strong></label>
                <input type="range" v-model.number="probeDistance" :min="6" :max="18" :step="1" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlarini ko'rsatish</span>
            </label>
            <button @click="measure" class="btn" title="Teslametr bilan o'lchash">📏 O'lchash</button>
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
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
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

.control-group strong {
    color: white;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
    cursor: pointer;
}

.checkbox input {
    width: 18px;
    height: 18px;
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
