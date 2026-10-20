<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    rpmToFrequency, angularFrequency, generatorPeakEMF, rmsFromPeak, OSCILLOSCOPE,
} from '../physics/magnetism.js';
import { createRng, seedFrom, reading } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const rotationSpeed = ref(60); // rpm
const coilTurns = ref(100);
const magnetStrength = ref(50); // % → B = 0.5 T at 50 %

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);

// Coil 8 cm × 10 cm in a uniform field between the poles.
const COIL_AREA = 0.08 * 0.10; // m²
const fieldB = computed(() => magnetStrength.value / 100); // T

const time = ref(0); // elapsed rotation time [s]
let lastTs = null;
// Phase is kept continuous when the rpm slider changes: θ(t) = θ0 + ω·(t − t0).
let phaseOffset = 0;
let phaseT0 = 0;

const frequency = computed(() => rpmToFrequency(rotationSpeed.value));
const omega = computed(() => angularFrequency(frequency.value));
const angle = computed(() => phaseOffset + omega.value * (time.value - phaseT0));
const genParams = computed(() => ({ turns: coilTurns.value, field: fieldB.value, area: COIL_AREA, omega: omega.value }));
// ε0 = N·B·A·ω ;  ε(t) = ε0·sin(ωt) ;  ε_rms = ε0/√2
const maxEMF = computed(() => generatorPeakEMF(genParams.value));
const rmsEMF = computed(() => rmsFromPeak(maxEMF.value));
const currentEMF = computed(() => maxEMF.value * Math.sin(angle.value));
// Fixed oscilloscope scale: peak EMF at the largest settings (N = 200, 120 rpm)
const EMF_SCALE = generatorPeakEMF({ turns: 200, field: 0.5, area: COIL_AREA, omega: angularFrequency(rpmToFrequency(120)) });
const GRAPH_SPAN = 4; // s of history shown on the scope

// Oscilloscope automatic measurements (Vamp, Vrms, Freq): 0.01 V / 0.001 Hz digits,
// σ = 0.5 % (vertical) / 0.05 % (crank-speed fluctuation); reproducible per set-up.
const lastReading = ref(null);
const readCounts = new Map();
const measure = () => {
    const key = seedFrom('GeneratorSimulation', rotationSpeed.value, coilTurns.value, magnetStrength.value);
    const k = readCounts.get(key) ?? 0;
    readCounts.set(key, k + 1);
    const rng = createRng(seedFrom(key, k));
    const amp = reading(maxEMF.value, OSCILLOSCOPE.voltage, rng);
    const f = reading(frequency.value, OSCILLOSCOPE.frequency, rng);
    // the scope derives Vrms from the same captured trace (sinusoid: Vamp/√2)
    const rms = reading(amp / Math.SQRT2, { resolution: OSCILLOSCOPE.voltage.resolution });
    lastReading.value = { amp, rms, f };
    const tag = `(n=${rotationSpeed.value} ayl/min, N=${coilTurns.value})`;
    emit('measurement', { name: `EYuK amplitudasi ${tag}`, value: amp, unit: 'V' });
    emit('measurement', { name: `Ta'sir etuvchi EYuK ${tag}`, value: rms, unit: 'V' });
    emit('measurement', { name: `Chastota ${tag}`, value: f, unit: 'Hz' });
};
watch([rotationSpeed, coilTurns, magnetStrength], () => { lastReading.value = null; });

watch(rotationSpeed, (rpm, oldRpm) => {
    const oldOmega = angularFrequency(rpmToFrequency(oldRpm));
    phaseOffset += oldOmega * (time.value - phaseT0);
    phaseT0 = time.value;
});

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (isRunning.value && !props.isPaused) time.value += dt;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const genX = 200;
    const genY = 200;

    // Magnets
    c.fillStyle = '#ef4444';
    c.fillRect(genX - 100, genY - 80, 40, 160);
    c.fillStyle = '#3b82f6';
    c.fillRect(genX + 60, genY - 80, 40, 160);
    c.fillStyle = 'white';
    c.font = 'bold 20px Inter';
    c.textAlign = 'center';
    c.fillText('N', genX - 80, genY + 7);
    c.fillText('S', genX + 80, genY + 7);

    // Rotating coil: axis vertical (in the screen), B horizontal N → S.
    // θ = ωt is the angle between the coil normal and B, so the face is seen
    // foreshortened to width 80·|sin θ| (edge-on at θ = 0: Φ max, ε = 0).
    const projected = 40 * Math.sin(angle.value);
    c.strokeStyle = projected >= 0 ? '#f59e0b' : '#b45309';
    c.lineWidth = 4;
    c.strokeRect(genX - Math.abs(projected), genY - 50, Math.max(1, 2 * Math.abs(projected)), 100);

    // Field direction
    c.strokeStyle = 'rgba(148, 163, 184, 0.35)';
    c.lineWidth = 1;
    for (let k = -2; k <= 2; k++) {
        c.beginPath();
        c.moveTo(genX - 58, genY + k * 28);
        c.lineTo(genX + 58, genY + k * 28);
        c.stroke();
    }
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('B →', genX, genY - 62);

    // Axis
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(genX, genY, 10, 0, Math.PI * 2);
    c.fill();

    // Slip rings
    c.save();
    c.translate(genX, genY + 120);
    c.fillStyle = '#f59e0b';
    c.fillRect(-25, 0, 20, 30);
    c.fillRect(5, 0, 20, 30);
    c.restore();

    // Brushes
    c.fillStyle = '#64748b';
    c.fillRect(genX - 30, genY + 155, 25, 20);
    c.fillRect(genX + 5, genY + 155, 25, 20);

    // AC output wave
    const graphX = 420;
    const graphY = 50;
    const graphW = 450;
    const graphH = 180;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 30, graphY + graphH / 2);
    c.lineTo(graphX + graphW - 10, graphY + graphH / 2);
    c.moveTo(graphX + 30, graphY + 10);
    c.lineTo(graphX + 30, graphY + graphH - 10);
    c.stroke();

    // AC wave: scrolling scope of ε(τ) for τ ∈ [t − span, t] (the newest value at the right)
    const plotW = graphW - 40;
    c.strokeStyle = '#22c55e';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x <= plotW; x++) {
        const tau = time.value - GRAPH_SPAN * (1 - x / plotW);
        const th = phaseOffset + omega.value * (tau - phaseT0);
        const emf = maxEMF.value * Math.sin(th);
        const y = graphY + graphH / 2 - (emf / EMF_SCALE) * 70;
        if (x === 0) c.moveTo(graphX + 30 + x, y);
        else c.lineTo(graphX + 30 + x, y);
    }
    c.stroke();

    // Current point (lies on the curve at τ = t)
    const currentX = graphX + 30 + plotW;
    const currentY = graphY + graphH / 2 - (currentEMF.value / EMF_SCALE) * 70;
    c.beginPath();
    c.arc(currentX, currentY, 6, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Labels
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('t', graphX + graphW - 20, graphY + graphH / 2 + 15);
    c.fillText('ε', graphX + 20, graphY + 20);
    const peakY = (maxEMF.value / EMF_SCALE) * 70;
    c.fillText('+ε₀', graphX + 15, graphY + graphH / 2 - peakY + 4);
    c.fillText('-ε₀', graphX + 15, graphY + graphH / 2 + peakY + 4);
    c.fillText(`${GRAPH_SPAN} s`, graphX + graphW - 30, graphY + graphH - 8);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 15, 450, 140);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    const col2 = graphX + 230;
    c.fillText(`Aylanish: ${rotationSpeed.value} RPM`, graphX + 15, graphY + graphH + 40);
    c.fillText(`Chastota: f = n/60 = ${frequency.value.toFixed(2)} Hz`, graphX + 15, graphY + graphH + 60);
    c.fillText(`ω = 2πf = ${omega.value.toFixed(2)} rad/s`, graphX + 15, graphY + graphH + 80);
    c.fillText(`Davr: T = ${(1 / frequency.value).toFixed(2)} s`, graphX + 15, graphY + graphH + 100);
    c.fillText(`O'ramlar: N = ${coilTurns.value}`, col2, graphY + graphH + 40);
    c.fillText(`B = ${fieldB.value.toFixed(2)} T, A = ${(COIL_AREA * 1e4).toFixed(0)} sm²`, col2, graphY + graphH + 60);
    c.fillStyle = '#22c55e';
    c.fillText(`ε₀ = NBAω = ${maxEMF.value.toFixed(2)} V`, col2, graphY + graphH + 80);
    c.fillText(`ε_ef = ε₀/√2 = ${rmsEMF.value.toFixed(2)} V`, col2, graphY + graphH + 100);
    c.fillStyle = '#f59e0b';
    c.fillText(`ε(t) = ${currentEMF.value.toFixed(2)} V`, graphX + 15, graphY + graphH + 130);
    c.fillStyle = '#38bdf8';
    c.font = '11px Inter';
    const r = lastReading.value;
    c.fillText(r ? `Ossillograf: ${r.amp.toFixed(2)} V, ${r.rms.toFixed(2)} V_ef, ${r.f.toFixed(3)} Hz` : "Ossillograf: — (O'lchash)", col2, graphY + graphH + 130);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Aylanish: <strong>{{ rotationSpeed }} RPM</strong></label>
                <input type="range" v-model.number="rotationSpeed" :min="30" :max="120" :step="10" />
            </div>
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="50" :max="200" :step="25" />
            </div>
            <button @click="isRunning = !isRunning" class="btn">
                {{ isRunning ? '⏸️' : '▶️' }}
            </button>
            <button @click="measure" class="btn" title="Ossillograf bilan o'lchash">📏 O'lchash</button>
        </div>
        <div class="formula">ε = ε₀ sin(ωt) = NBAω sin(ωt), ω = 2πn/60</div>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 140px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
}

.formula {
    font-family: serif;
    font-size: 1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
