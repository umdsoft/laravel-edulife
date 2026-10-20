<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    normalizedResponse,
    normalizedPhaseLag,
    resonancePeakFrequency,
    qualityFactor,
    isInResonanceBand,
    advanceClock,
    RESONANCE_STATIC_AMPLITUDE,
    WAVE_INSTRUMENTS,
} from '../physics/waves.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Driven damped oscillator m x'' + b x' + k x = F₀ sin(ωt), damping ratio ζ = b/(2mω₀).
// Steady state: x(t) = A_st·R(r,ζ)·sin(ωt − δ), r = f/f₀,
//   R = 1/√((1 − r²)² + (2ζr)²),  δ = atan2(2ζr, 1 − r²).
const naturalFreq = ref(2);
const drivingFreq = ref(2);
const damping = ref(0.1); // ζ

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

// Static deflection A_st = F₀/k = 10 mm; the mass is drawn at 1 px = 1 mm.
const A_ST_MM = RESONANCE_STATIC_AMPLITUDE * 1000;
const PX_PER_UNIT = A_ST_MM; // px per A_static
// Amplitude read by a displacement sensor (0.1 mm, σ = 0.03 mm)
const sensor = createInstrument(WAVE_INSTRUMENTS.displacementSensor, 'Resonance');
const lastAmplitudeMm = ref(null);
const RESPONSE_MAX = 10.5; // graph top (peak at ζ = 0.05 is 10.01)
const F_AXIS_MAX = 4; // Hz

const frequencyRatio = computed(() => drivingFreq.value / naturalFreq.value);
const theoreticalAmp = computed(() => normalizedResponse(frequencyRatio.value, damping.value));
const phaseLag = computed(() => normalizedPhaseLag(frequencyRatio.value, damping.value));
// Resonance = inside the half-power band (A ≥ A_max/√2), not merely f ≈ f₀
const isResonance = computed(() => isInResonanceBand(frequencyRatio.value, damping.value));
const peakFreq = computed(() => resonancePeakFrequency(naturalFreq.value, damping.value));
const qFactor = computed(() => qualityFactor(damping.value));

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

// Steady-state amplitude A = A_st·R read by the sensor; reported as A/A_st.
const measure = () => {
    if (!Number.isFinite(theoreticalAmp.value)) return;
    const aM = sensor.read(theoreticalAmp.value * RESONANCE_STATIC_AMPLITUDE,
        [naturalFreq.value, drivingFreq.value, damping.value]);
    const aMm = Number((aM * 1000).toFixed(1));
    lastAmplitudeMm.value = aMm;
    emit('measurement', {
        name: `Amplituda (f = ${drivingFreq.value.toFixed(1)} Hz, A = ${aMm.toFixed(1)} mm)`,
        value: Number((aMm / A_ST_MM).toFixed(4)),
        unit: 'A/Aₛₜ',
    });
};

const animate = (nowMs) => {
    clock = advanceClock(clock, nowMs, !props.isPaused);
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const t = clock.t;
    const omegaT = 2 * Math.PI * drivingFreq.value * t;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, canvasConfig.value.height);

    // Spring system (displacement lags the force by δ)
    const baseX = 150;
    const baseY = 50;
    const massY = 200 + PX_PER_UNIT * theoreticalAmp.value * Math.sin(omegaT - phaseLag.value);

    c.fillStyle = '#475569';
    c.fillRect(baseX - 30, baseY - 10, 60, 20);

    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    const springTop = baseY + 10;
    const springBottom = massY - 25;
    const numCoils = 10;
    for (let i = 0; i <= numCoils * 4; i++) {
        const s = i / (numCoils * 4);
        const y = springTop + s * (springBottom - springTop);
        const x = baseX + Math.sin(i * Math.PI / 2) * 15;
        if (i === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    c.fillStyle = isResonance.value ? '#ef4444' : '#3b82f6';
    c.fillRect(baseX - 25, massY - 25, 50, 50);

    // Driving force F(t) = F₀ sin(ωt)
    c.fillStyle = '#22c55e';
    const forceY = 385;
    c.beginPath();
    c.arc(baseX, forceY + 20 * Math.sin(omegaT), 12, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('F(t)', baseX, forceY + 50);

    // Frequency response curve
    const graphX = 400;
    const graphY = 50;
    const graphW = 450;
    const graphH = 250;
    const x0 = graphX + 50;
    const y0 = graphY + graphH - 30; // A = 0
    const plotW = graphW - 70;
    const plotH = graphH - 50;
    const fx = (f) => x0 + (f / F_AXIS_MAX) * plotW;
    const ay = (a) => y0 - (Math.min(a, RESPONSE_MAX) / RESPONSE_MAX) * plotH;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes: A vertical, f horizontal (bottom)
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(x0, graphY + 20);
    c.lineTo(x0, y0);
    c.lineTo(graphX + graphW - 20, y0);
    c.stroke();

    // A_static = 1 reference
    c.strokeStyle = 'rgba(148, 163, 184, 0.3)';
    c.lineWidth = 1;
    c.setLineDash([2, 4]);
    c.beginPath();
    c.moveTo(x0, ay(1));
    c.lineTo(graphX + graphW - 20, ay(1));
    c.stroke();
    c.setLineDash([]);

    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    const N = 400;
    for (let i = 0; i <= N; i++) {
        const f = (i / N) * F_AXIS_MAX;
        const a = normalizedResponse(f / naturalFreq.value, damping.value);
        if (i === 0) c.moveTo(fx(f), ay(a));
        else c.lineTo(fx(f), ay(a));
    }
    c.stroke();

    // Current point
    c.beginPath();
    c.arc(fx(drivingFreq.value), ay(theoreticalAmp.value), 8, 0, Math.PI * 2);
    c.fillStyle = isResonance.value ? '#ef4444' : '#22c55e';
    c.fill();

    // f₀ marker
    const resX = fx(naturalFreq.value);
    c.strokeStyle = '#f59e0b';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(resX, y0);
    c.lineTo(resX, graphY + 30);
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText('f₀', resX, graphY + graphH - 10);

    // Labels
    c.fillStyle = 'white';
    c.textAlign = 'left';
    c.fillText('A/Aₛₜ', graphX + 8, graphY + 30);
    c.textAlign = 'center';
    c.fillText('f, Hz', graphX + graphW - 30, graphY + graphH - 10);
    c.fillStyle = '#94a3b8';
    c.fillText(`${RESPONSE_MAX}`, x0 - 18, graphY + 24);
    c.fillText('1', x0 - 10, ay(1) + 4);

    // Info panel
    const iy = graphY + graphH + 20;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, iy, graphW, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tabiiy chastota f₀: ${naturalFreq.value.toFixed(1)} Hz`, graphX + 15, iy + 25);
    c.fillText(`Majburlovchi f: ${drivingFreq.value.toFixed(1)} Hz`, graphX + 15, iy + 45);
    c.fillText(`So'nish ζ: ${damping.value.toFixed(2)}   (Q = 1/2ζ = ${qFactor.value.toFixed(1)})`, graphX + 15, iy + 65);
    c.fillText(`Amplituda A/Aₛₜ = ${theoreticalAmp.value.toFixed(2)}`, graphX + 235, iy + 25);
    c.fillText(`Faza kechikishi δ = ${(phaseLag.value * 180 / Math.PI).toFixed(0)}°`, graphX + 235, iy + 45);
    c.fillStyle = '#f472b6';
    c.fillText(`Datchik: A = ${lastAmplitudeMm.value === null ? '—' : `${lastAmplitudeMm.value.toFixed(1)} mm`} (Aₛₜ = ${A_ST_MM} mm)`, graphX + 235, iy + 85);
    c.fillStyle = '#ffffff';
    c.fillText(`Cho'qqi: f₀√(1−2ζ²) = ${peakFreq.value.toFixed(2)} Hz`, graphX + 235, iy + 65);
    c.fillStyle = isResonance.value ? '#ef4444' : '#94a3b8';
    c.font = 'bold 12px Inter';
    c.fillText(isResonance.value ? '⚠️ REZONANS! (A ≥ Aₘₐₓ/√2)' : 'Normal holat', graphX + 15, iy + 95);
};

watch([naturalFreq, drivingFreq, damping], () => { lastAmplitudeMm.value = null; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tabiiy f₀: <strong>{{ naturalFreq.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="naturalFreq" :min="1" :max="4" :step="0.2" />
            </div>
            <div class="control-group">
                <label>Majburlovchi f: <strong>{{ drivingFreq.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="drivingFreq" :min="0.5" :max="4" :step="0.1" />
            </div>
            <div class="control-group">
                <label>So'nish: <strong>{{ damping.toFixed(2) }}</strong></label>
                <input type="range" v-model.number="damping" :min="0.05" :max="0.5" :step="0.05" />
            </div>
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
}
</style>
