<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    beatFrequency as calcBeatFrequency,
    beatPeriod as calcBeatPeriod,
    beatSum,
    beatEnvelope,
    advanceClock,
    roundTo,
} from '../physics/waves.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const freq1 = ref(440);
const freq2 = ref(444);
const amplitude = ref(0.5);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

// The time axis is REAL time (s). The window scrolls at 1/10 of real speed so
// the beat envelope can be followed by eye.
const TIME_SCALE = 0.1;
const PX_AMP = 40; // px per unit amplitude

const beatFrequency = computed(() => calcBeatFrequency(freq1.value, freq2.value)); // |f₁ − f₂|
const beatPeriod = computed(() => calcBeatPeriod(freq1.value, freq2.value));
const avgFrequency = computed(() => (freq1.value + freq2.value) / 2);
// Bottom window shows ≈3 beats (bounded), top windows ≈10 carrier cycles
const sumWindow = computed(() => (beatFrequency.value > 0 ? Math.min(Math.max(3 / beatFrequency.value, 0.03), 1.5) : 0.05));
const carrierWindow = computed(() => 10 / avgFrequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const measure = () => {
    emit('measurement', { name: 'Urishlar chastotasi', value: roundTo(beatFrequency.value, 2), unit: 'Hz' });
};

const animate = (nowMs) => {
    clock = advanceClock(clock, nowMs, isRunning.value && !props.isPaused);
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const fmtTime = (s) => (s >= 1 ? `${s.toFixed(2)} s` : `${(s * 1000).toFixed(s < 0.1 ? 1 : 0)} ms`);

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const f1 = freq1.value;
    const f2 = freq2.value;
    const A = amplitude.value;

    // Window start: snapped to a whole number of mean-carrier periods, so the
    // fast carrier stays still on screen while the slow relative phase (and the
    // beat envelope) drifts — exactly what the ear hears as beats.
    const tau = clock.t * TIME_SCALE;
    const t0 = Math.floor(tau * avgFrequency.value) / avgFrequency.value;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const graphW = w - 100;
    const centerY1 = 100;
    const centerY2 = 200;
    const centerY3 = 350;

    const plot = (centerY, win, fn, width = graphW) => {
        c.beginPath();
        for (let x = 0; x <= width; x++) {
            const y = centerY - PX_AMP * fn(t0 + (x / width) * win);
            if (x === 0) c.moveTo(50 + x, y);
            else c.lineTo(50 + x, y);
        }
        c.stroke();
    };

    // Wave 1 and wave 2 — same short window (kept clear of the info panel)
    const topW = graphW - 240;
    c.lineWidth = 2;
    c.strokeStyle = '#ef4444';
    plot(centerY1, carrierWindow.value, (t) => A * Math.sin(2 * Math.PI * f1 * t), topW);
    c.fillStyle = '#ef4444';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`f₁ = ${f1} Hz`, 60, centerY1 - 50);

    c.strokeStyle = '#3b82f6';
    plot(centerY2, carrierWindow.value, (t) => A * Math.sin(2 * Math.PI * f2 * t), topW);
    c.fillStyle = '#3b82f6';
    c.fillText(`f₂ = ${f2} Hz`, 60, centerY2 - 50);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText(`Oyna: ${fmtTime(carrierWindow.value)}`, 50 + topW - 80, centerY2 - 50);

    c.fillStyle = '#f59e0b';
    c.font = '24px Inter';
    c.textAlign = 'center';
    c.fillText('+', 25, (centerY1 + centerY2) / 2 + 8);

    // Resultant y = A sin(2πf₁t) + A sin(2πf₂t) = 2A cos(πΔf t) sin(2π f̄ t)
    const win = sumWindow.value;
    const cyclesPerPx = (avgFrequency.value * win) / graphW;
    c.strokeStyle = '#22c55e';
    if (cyclesPerPx < 0.25) {
        c.lineWidth = 2;
        plot(centerY3, win, (t) => beatSum(t, f1, f2, A));
    } else {
        // Carrier too dense to resolve on screen → its extent is ±envelope
        c.fillStyle = 'rgba(34, 197, 94, 0.55)';
        c.beginPath();
        for (let x = 0; x <= graphW; x++) {
            const e = beatEnvelope(t0 + (x / graphW) * win, f1, f2, A);
            if (x === 0) c.moveTo(50 + x, centerY3 - PX_AMP * e);
            else c.lineTo(50 + x, centerY3 - PX_AMP * e);
        }
        for (let x = graphW; x >= 0; x--) {
            const e = beatEnvelope(t0 + (x / graphW) * win, f1, f2, A);
            c.lineTo(50 + x, centerY3 + PX_AMP * e);
        }
        c.closePath();
        c.fill();
    }

    // Beat envelope ±2A|cos(πΔf t)|
    c.strokeStyle = 'rgba(249, 115, 22, 0.8)';
    c.lineWidth = 2;
    c.setLineDash([5, 5]);
    plot(centerY3, win, (t) => beatEnvelope(t, f1, f2, A));
    plot(centerY3, win, (t) => -beatEnvelope(t, f1, f2, A));
    c.setLineDash([]);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.textAlign = 'left';
    c.fillText(`Oyna: ${fmtTime(win)}  (vaqt ${Math.round(1 / TIME_SCALE)}× sekin siljiydi)`, 60, h - 12);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`f₁ = ${f1} Hz,  f₂ = ${f2} Hz`, w - 205, 45);
    c.fillText(`f̄ = (f₁+f₂)/2 = ${avgFrequency.value.toFixed(1)} Hz`, w - 205, 65);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.fillText(`Urishlar: ${beatFrequency.value} Hz`, w - 205, 90);
    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText(`fᵤ = |f₁ − f₂|,  Tᵤ = ${Number.isFinite(beatPeriod.value) ? fmtTime(beatPeriod.value) : '∞'}`, w - 205, 110);
    c.fillText('y = 2A cos(πΔf·t) sin(2πf̄·t)', w - 205, 128);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>f₁: <strong>{{ freq1 }} Hz</strong></label>
                <input type="range" v-model.number="freq1" :min="400" :max="500" :step="2" />
            </div>
            <div class="control-group">
                <label>f₂: <strong>{{ freq2 }} Hz</strong></label>
                <input type="range" v-model.number="freq2" :min="400" :max="500" :step="2" />
            </div>
            <div class="beat-display">
                <span>Urishlar:</span>
                <strong>{{ beatFrequency }} Hz</strong>
            </div>
            <button @click="isRunning = !isRunning" class="btn">{{ isRunning ? '⏸️' : '▶️' }}</button>
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
    align-items: center;
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

.control-group strong {
    color: white;
}

.beat-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #0f172a;
    border-radius: 8px;
}

.beat-display span {
    font-size: 0.75rem;
    color: #94a3b8;
}

.beat-display strong {
    font-size: 1.25rem;
    color: #f59e0b;
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
</style>
