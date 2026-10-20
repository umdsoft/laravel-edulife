<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    soundWaveState,
    wavelengthFrom,
    period as calcPeriod,
    waveNumber,
    angularFrequency,
    advanceClock,
    speedOfSoundAir,
    WAVE_INSTRUMENTS,
} from '../physics/waves.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const frequency = ref(440);
const amplitude = ref(0.5);
const temperature = ref(20); // air temperature, °C
// v(t) = 343·√(T / 293.15 K) — 343 m/s at 20 °C
const soundSpeed = computed(() => speedOfSoundAir(temperature.value));

// λ is measured like in the lab: the microphone is moved along a steel tape
// until the oscilloscope trace is back in phase with the speaker (1 mm, σ = 0.3 mm).
const tape = createInstrument(WAVE_INSTRUMENTS.tape, 'SoundWaves');
const lastReading = ref(null);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

// Display scales: 1 m of air = 200 px; animation is slowed 500× so that
// 100–1000 Hz oscillations (0.2–2 Hz on screen) are visible. Both scales are
// applied to the exact solution, so λ on screen = (v/f)·PX_PER_M.
const PX_PER_M = 200;
const SLOW_MOTION = 500;
const X0 = 100; // speaker membrane (x = 0)

const wavelength = computed(() => wavelengthFrom(soundSpeed.value, frequency.value)); // m
const period = computed(() => calcPeriod(frequency.value)); // s
const waveParams = computed(() => ({ amplitude: 1, frequency: frequency.value, wavelength: wavelength.value }));

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const measure = () => {
    if (!Number.isFinite(wavelength.value)) return;
    const lambda = tape.read(wavelength.value, [frequency.value, temperature.value]);
    lastReading.value = lambda;
    emit('measurement', {
        name: `To'lqin uzunligi (f = ${frequency.value} Hz, t = ${temperature.value} °C)`,
        value: lambda,
        unit: 'm',
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
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const tau = clock.t / SLOW_MOTION; // physical time, s
    const state = (xPx) => soundWaveState((xPx - X0) / PX_PER_M, tau, waveParams.value);

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Speaker
    c.fillStyle = '#334155';
    c.beginPath();
    c.moveTo(50, centerY - 60);
    c.lineTo(100, centerY - 40);
    c.lineTo(100, centerY + 40);
    c.lineTo(50, centerY + 60);
    c.closePath();
    c.fill();
    c.fillRect(30, centerY - 40, 20, 80);

    // Membrane moves with the air at x = 0: s(0,t) = s₀ sin(−ωt)
    const vibOffset = state(X0).displacement * 5 * amplitude.value;
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(75 + vibOffset, centerY, 25, 0, Math.PI * 2);
    c.fill();

    // Air molecules: longitudinal displacement s(x,t) along x.
    // They bunch up where Δp > 0 (compression) automatically.
    const numMolecules = 40;
    for (let i = 0; i < numMolecules; i++) {
        const baseX = 120 + i * 18;
        const s = state(baseX);
        const x = baseX + amplitude.value * 8 * s.displacement;
        c.fillStyle = `rgba(59, 130, 246, ${0.5 + 0.4 * amplitude.value * s.pressure})`;
        for (let j = 0; j < 6; j++) {
            const baseY = centerY - 80 + j * 32;
            c.beginPath();
            c.arc(x, baseY, 3, 0, Math.PI * 2);
            c.fill();
        }
    }

    // Graphs: displacement s(x,t) (blue) and pressure Δp(x,t) (red, 90° shifted)
    const xEnd = w - 50;
    const graphAmp = amplitude.value * 70;
    c.lineWidth = 2;
    c.strokeStyle = 'rgba(96, 165, 250, 0.9)';
    c.beginPath();
    for (let x = 120; x < xEnd; x++) {
        const y = centerY - graphAmp * state(x).displacement;
        if (x === 120) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();
    c.strokeStyle = 'rgba(248, 113, 113, 0.9)';
    c.setLineDash([6, 4]);
    c.beginPath();
    for (let x = 120; x < xEnd; x++) {
        const y = centerY - graphAmp * state(x).pressure;
        if (x === 120) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();
    c.setLineDash([]);

    // Pressure bar: red = compression (Δp > 0), blue = rarefaction (Δp < 0)
    const barY = h - 60;
    for (let x = 120; x < xEnd; x += 10) {
        const p = state(x + 4).pressure;
        c.fillStyle = p > 0 ? `rgba(239, 68, 68, ${p * 0.6})` : `rgba(59, 130, 246, ${-p * 0.6})`;
        c.fillRect(x, barY, 8, 20);
    }

    // C / R labels at the exact extrema: Δp ∝ −cos(kx − ωt) is max at kx − ωt = π (mod 2π)
    const k = waveNumber(wavelength.value);
    const om = angularFrequency(frequency.value);
    if (Number.isFinite(k)) {
        c.font = '11px Inter';
        c.textAlign = 'center';
        const lamPx = wavelength.value * PX_PER_M;
        for (const [phase0, label, color] of [[Math.PI, 'C', '#f87171'], [0, 'R', '#60a5fa']]) {
            // x (m) where kx − ωτ = phase0 + 2πm
            const xm0 = (phase0 + om * tau) / k;
            let xPx = X0 + xm0 * PX_PER_M;
            xPx = 120 + ((((xPx - 120) % lamPx) + lamPx) % lamPx);
            c.fillStyle = color;
            for (; xPx < xEnd; xPx += lamPx) c.fillText(label, xPx, barY + 35);
        }
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('C = Siqilish, R = Siyraklanish   —  ko\'k: siljish s(x,t), qizil: bosim Δp(x,t)', 120, h - 12);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 230, 20, 210, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`Chastota: ${frequency.value} Hz`, w - 215, 45);
    c.fillText(`Amplituda: ${amplitude.value.toFixed(1)}`, w - 215, 65);
    c.fillStyle = '#3b82f6';
    c.fillText(`λ = v/f = ${(wavelength.value * 100).toFixed(1)} cm`, w - 215, 90);
    c.fillStyle = '#f59e0b';
    c.fillText(`v = ${soundSpeed.value.toFixed(1)} m/s (${temperature.value} °C)`, w - 215, 110);
    c.fillStyle = '#22c55e';
    c.fillText(`T = 1/f = ${(period.value * 1000).toFixed(2)} ms`, w - 215, 130);
    c.fillStyle = '#f472b6';
    c.fillText(`O'lchangan λ: ${lastReading.value === null ? '—' : `${lastReading.value.toFixed(3)} m`}`, w - 215, 150);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText(`Animatsiya ${SLOW_MOTION}× sekin, 1 m = ${PX_PER_M} px`, w - 215, 175);
};

watch([frequency, temperature], () => { lastReading.value = null; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="100" :max="1000" :step="10" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude.toFixed(1) }}</strong></label>
                <input type="range" v-model.number="amplitude" :min="0.2" :max="1" :step="0.1" />
            </div>
            <div class="control-group">
                <label>Havo harorati: <strong>{{ temperature }} °C</strong></label>
                <input type="range" v-model.number="temperature" :min="-20" :max="40" :step="1" />
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
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
