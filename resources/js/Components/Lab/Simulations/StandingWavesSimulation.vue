<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    standingWaveDisplacement,
    standingWaveWavelength,
    stringNodePositions,
    stringAntinodePositions,
    stringTension,
    advanceClock,
    MELDE_STRING,
    WAVE_INSTRUMENTS,
} from '../physics/waves.js';
import { quantize } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Melde experiment: a string of length L = 1 m (both ends fixed) driven by a
// vibrator. The slider sets its fundamental f₁ = v/(2L) (i.e. the tension,
// T = μv² with μ = 1.0 g/m); the n-th harmonic has f_n = n·f₁, λ_n = 2L/n.
const frequency = ref(20); // fundamental f₁, Hz
const harmonicMode = ref(1);
const amplitude = ref(5); // antinode amplitude, mm (drawn ×10)

const STRING_LENGTH = MELDE_STRING.length; // m (drawn as 700 px)
const AMP_PX_PER_MM = 10; // vertical exaggeration of the drawing
const MAX_VISUAL_FREQ = 4; // Hz — faster modes are shown in slow motion

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

const harmonicFrequency = computed(() => harmonicMode.value * frequency.value); // f_n = n f₁
const wavelength = computed(() => standingWaveWavelength(harmonicMode.value, STRING_LENGTH)); // m
const waveSpeed = computed(() => wavelength.value * harmonicFrequency.value); // v = λ_n f_n = 2L f₁
const tension = computed(() => stringTension(waveSpeed.value, MELDE_STRING.mu)); // T = μv², N
const timeScale = computed(() =>
    (harmonicFrequency.value > MAX_VISUAL_FREQ ? MAX_VISUAL_FREQ / harmonicFrequency.value : 1));

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

// The generator frequency at which the n-th mode resonates, read on the
// frequency counter (0.1 Hz).
const measure = () => {
    emit('measurement', {
        name: `Garmonika chastotasi (n = ${harmonicMode.value})`,
        value: quantize(harmonicFrequency.value, WAVE_INSTRUMENTS.frequencyCounter.resolution),
        unit: 'Hz',
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

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const startX = 100;
    const endX = 800;
    const centerY = 200;
    const pxPerM = (endX - startX) / STRING_LENGTH;
    const n = harmonicMode.value;
    const t = clock.t * timeScale.value;
    const ampPx = amplitude.value * AMP_PX_PER_MM;
    const params = { n, length: STRING_LENGTH, amplitude: ampPx, frequency: harmonicFrequency.value };

    // Fixed ends
    c.fillStyle = '#475569';
    c.fillRect(startX - 15, centerY - 80, 15, 160);
    c.fillRect(endX, centerY - 80, 15, 160);

    // Equilibrium line
    c.strokeStyle = '#334155';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(startX, centerY);
    c.lineTo(endX, centerY);
    c.stroke();
    c.setLineDash([]);

    // Envelope ±A|sin(nπx/L)|
    c.strokeStyle = 'rgba(148, 163, 184, 0.35)';
    c.lineWidth = 1;
    for (const sgn of [1, -1]) {
        c.beginPath();
        for (let x = startX; x <= endX; x++) {
            const env = ampPx * Math.abs(Math.sin((n * Math.PI * (x - startX)) / (endX - startX)));
            const y = centerY - sgn * env;
            if (x === startX) c.moveTo(x, y);
            else c.lineTo(x, y);
        }
        c.stroke();
    }

    // Standing wave y(x,t) = A sin(nπx/L) cos(2π f_n t)
    c.beginPath();
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 3;
    for (let x = startX; x <= endX; x++) {
        const y = centerY - standingWaveDisplacement((x - startX) / pxPerM, t, params);
        if (x === startX) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Nodes and antinodes (computed positions)
    c.font = '11px Inter';
    c.textAlign = 'center';

    for (const xm of stringNodePositions(n, STRING_LENGTH)) {
        const nodeX = startX + xm * pxPerM;
        c.beginPath();
        c.arc(nodeX, centerY, 6, 0, Math.PI * 2);
        c.fillStyle = '#22c55e';
        c.fill();
        c.fillText('N', nodeX, centerY + 25);
    }

    for (const xm of stringAntinodePositions(n, STRING_LENGTH)) {
        const antiX = startX + xm * pxPerM;
        c.fillStyle = '#ef4444';
        c.beginPath();
        c.arc(antiX, centerY - ampPx, 6, 0, Math.PI * 2);
        c.fill();
        c.beginPath();
        c.arc(antiX, centerY + ampPx, 6, 0, Math.PI * 2);
        c.fill();
        c.fillText('D', antiX, centerY + ampPx + 20);
    }

    // Info panel (bottom)
    const py = 305;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, py, w - 40, 85);
    c.textAlign = 'left';
    c.font = '13px Inter';
    c.fillStyle = 'white';
    c.fillText(`Garmonika: n = ${n}`, 40, py + 25);
    c.fillText(`L = ${STRING_LENGTH.toFixed(3)} m, μ = ${(MELDE_STRING.mu * 1000).toFixed(1)} g/m`, 40, py + 47);
    c.fillText(`Amplituda: ${amplitude.value} mm`, 40, py + 69);

    c.fillStyle = '#f59e0b';
    c.fillText(`λₙ = 2L/n = ${wavelength.value.toFixed(3)} m`, 270, py + 25);
    c.fillText(`fₙ = n·f₁ = ${n} × ${frequency.value.toFixed(1)} = ${harmonicFrequency.value.toFixed(1)} Hz`, 270, py + 47);
    c.fillStyle = '#3b82f6';
    c.fillText(`v = 2L·f₁ = ${waveSpeed.value.toFixed(1)} m/s,  T = μv² = ${tension.value.toFixed(2)} N`, 270, py + 69);

    c.fillStyle = '#22c55e';
    c.fillText(`Tugunlar (N): ${n + 1}`, 660, py + 25);
    c.fillStyle = '#ef4444';
    c.fillText(`Do'ngliklar (D): ${n}`, 660, py + 47);
    c.fillStyle = '#64748b';
    c.font = '11px Inter';
    const slow = timeScale.value < 1 ? `, ${(1 / timeScale.value).toFixed(0)}× sekin` : '';
    c.fillText(`Vertikal 10× kattalashtirilgan${slow}`, 660, py + 69);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Garmonika: <strong>n = {{ harmonicMode }}</strong></label>
                <input type="range" v-model.number="harmonicMode" :min="1" :max="6" :step="1" />
            </div>
            <div class="control-group">
                <label>Asosiy chastota f₁: <strong>{{ frequency.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="5" :max="50" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }} mm</strong></label>
                <input type="range" v-model.number="amplitude" :min="2" :max="8" :step="1" />
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
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 130px;
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
