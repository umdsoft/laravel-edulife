<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    wavelengthFrom,
    twoSourceField,
    nodalLineCount,
    advanceClock,
    roundTo,
} from '../physics/waves.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const frequency = ref(5); // Hz
const separation = ref(200); // px
const amplitude = ref(1);

const WAVE_SPEED = 400; // px/s (ripple tank)
const CELL = 4; // px per field sample
const AMP_MAX = 2; // slider maximum, used to normalise brightness

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };
let fieldCanvas = null;
let fieldCtx = null;
let fieldImage = null;

const wavelength = computed(() => wavelengthFrom(WAVE_SPEED, frequency.value)); // λ = v/f, px
const nodalLines = computed(() => nodalLineCount(separation.value, wavelength.value));

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => {
    if (!canvas.value) return;
    ctx.value = canvas.value.getContext('2d');
    const { width, height } = canvasConfig.value;
    fieldCanvas = document.createElement('canvas');
    fieldCanvas.width = Math.ceil(width / CELL);
    fieldCanvas.height = Math.ceil(height / CELL);
    fieldCtx = fieldCanvas.getContext('2d');
    fieldImage = fieldCtx.createImageData(fieldCanvas.width, fieldCanvas.height);
    animationId.value = requestAnimationFrame(animate);
};

const measure = () => {
    if (!Number.isFinite(wavelength.value)) return;
    emit('measurement', { name: 'To\'lqin uzunligi', value: roundTo(wavelength.value, 1), unit: 'px' });
};

const animate = (nowMs) => {
    clock = advanceClock(clock, nowMs, !props.isPaused);
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value || !fieldImage) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const t = clock.t;
    const lam = wavelength.value;

    const source1 = { x: w / 2 - separation.value / 2, y: h / 2 };
    const source2 = { x: w / 2 + separation.value / 2, y: h / 2 };
    const params = { s1: source1, s2: source2, amplitude: amplitude.value, frequency: frequency.value, wavelength: lam };

    // Superposition y = A sin(k r₁ − ωt) + A sin(k r₂ − ωt), sampled on a grid
    const data = fieldImage.data;
    const gw = fieldCanvas.width;
    const gh = fieldCanvas.height;
    const norm = 2 * AMP_MAX;
    for (let j = 0; j < gh; j++) {
        const y = (j + 0.5) * CELL;
        for (let i = 0; i < gw; i++) {
            const x = (i + 0.5) * CELL;
            const u = twoSourceField(x, y, t, params) / norm; // −1…1
            const s = Math.sign(u) * Math.sqrt(Math.abs(u));
            const k = (j * gw + i) * 4;
            // trough → dark navy, 0 → slate, crest → cyan
            if (s >= 0) {
                data[k] = 30 + s * 26;
                data[k + 1] = 41 + s * 148;
                data[k + 2] = 59 + s * 189;
            } else {
                data[k] = 30 + s * 28;
                data[k + 1] = 41 + s * 35;
                data[k + 2] = 59 + s * 40;
            }
            data[k + 3] = 255;
        }
    }
    fieldCtx.putImageData(fieldImage, 0, 0);
    c.imageSmoothingEnabled = true;
    c.drawImage(fieldCanvas, 0, 0, gw * CELL, gh * CELL);

    // Nodal lines: r₁ − r₂ = (m + ½)λ → hyperbolas with foci at the sources
    const d = separation.value;
    const cx = w / 2;
    const cy = h / 2;
    c.strokeStyle = 'rgba(255, 255, 255, 0.35)';
    c.lineWidth = 1;
    c.setLineDash([4, 4]);
    for (let m = 0; (m + 0.5) * lam < d; m++) {
        const a = ((m + 0.5) * lam) / 2; // semi-transverse axis
        const b = Math.sqrt((d / 2) ** 2 - a * a);
        for (const side of [1, -1]) {
            c.beginPath();
            for (let p = -4; p <= 4; p += 0.05) {
                const x = cx + side * a * Math.cosh(p);
                const y = cy + b * Math.sinh(p);
                if (p === -4) c.moveTo(x, y);
                else c.lineTo(x, y);
            }
            c.stroke();
        }
    }
    c.setLineDash([]);

    // Sources
    c.fillStyle = '#fbbf24';
    c.beginPath();
    c.arc(source1.x, source1.y, 8, 0, Math.PI * 2);
    c.fill();
    c.beginPath();
    c.arc(source2.x, source2.y, 8, 0, Math.PI * 2);
    c.fill();

    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'left';
    c.fillText('Manba 1', source1.x - 25, source1.y - 15);
    c.fillText('Manba 2', source2.x - 25, source2.y - 15);

    // Info
    c.fillStyle = 'rgba(15, 23, 42, 0.85)';
    c.fillRect(10, 10, 255, 92);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText(`λ = v/f = ${lam.toFixed(1)} px   (v = ${WAVE_SPEED} px/s)`, 20, 30);
    c.fillText(`d = ${d} px,  d/λ = ${(d / lam).toFixed(2)}`, 20, 48);
    c.fillText(`Tugun chiziqlari: ${nodalLines.value}`, 20, 66);
    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('Max: Δr = mλ    Min (- - -): Δr = (m+½)λ', 20, 88);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="1" :max="10" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Manbalar orasi: <strong>{{ separation }} px</strong></label>
                <input type="range" v-model.number="separation" :min="50" :max="400" :step="10" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }}</strong></label>
                <input type="range" v-model.number="amplitude" :min="0.2" :max="2" :step="0.1" />
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
