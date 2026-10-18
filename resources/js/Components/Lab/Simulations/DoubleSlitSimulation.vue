<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    fringeSpacing as calcFringeSpacing,
    doubleSlitIntensity,
    brightFringePosition,
    wavelengthToRgb,
    advanceClock,
    roundTo,
} from '../physics/waves.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const slitSeparation = ref(0.1); // d, mm
const wavelength = ref(550); // λ, nm

const SCREEN_DISTANCE = 1; // L, m
const SLIT_WIDTH = 0.02e-3; // a, m (single-slit envelope)
const SCREEN_HALF_MM = 20; // screen shows y ∈ [−20, 20] mm

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

const lambdaM = computed(() => wavelength.value * 1e-9);
const dM = computed(() => slitSeparation.value * 1e-3);
// Δy = λL/d (m)
const fringeSpacing = computed(() => calcFringeSpacing(lambdaM.value, SCREEN_DISTANCE, dM.value));
const slitParams = computed(() => ({
    wavelength: lambdaM.value,
    slitSeparation: dM.value,
    screenDistance: SCREEN_DISTANCE,
    slitWidth: SLIT_WIDTH,
}));
const rgb = computed(() => wavelengthToRgb(wavelength.value));
const rgba = (a) => `rgba(${rgb.value.join(',')}, ${a})`;

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const measure = () => {
    if (!Number.isFinite(fringeSpacing.value)) return;
    emit('measurement', { name: 'Polosalar oralig\'i Δy', value: roundTo(fringeSpacing.value * 1000, 3), unit: 'mm' });
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
    const t = clock.t;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Schematic wavefronts: spacing proportional to λ, speed 40 px/s
    const lamVis = wavelength.value * 0.06; // px
    const vVis = 40;
    const shift = (vVis * t) % lamVis;

    c.beginPath();
    c.arc(50, h / 2, 15, 0, Math.PI * 2);
    c.fillStyle = rgba(1);
    c.fill();

    c.lineWidth = 2;
    for (let x = 80 + shift; x < 180; x += lamVis) {
        c.strokeStyle = rgba(Math.max(0, 0.5 - (x - 80) / 250));
        c.beginPath();
        c.moveTo(x, h / 2 - 80);
        c.lineTo(x, h / 2 + 80);
        c.stroke();
    }

    // Double slit barrier (schematic slit spacing)
    const slitX = 200;
    const slitY1 = h / 2 - 30;
    const slitY2 = h / 2 + 30;

    c.fillStyle = '#1e293b';
    c.fillRect(slitX - 10, 50, 20, slitY1 - 50 - 10);
    c.fillRect(slitX - 10, slitY1 + 10, 20, slitY2 - slitY1 - 20);
    c.fillRect(slitX - 10, slitY2 + 10, 20, h - 50 - slitY2 - 10);

    // Circular waves from slits (Huygens)
    const screenX = w - 150;
    const maxR = screenX - slitX - 10;
    for (let r = shift; r < maxR; r += lamVis) {
        c.strokeStyle = rgba(Math.max(0, 0.4 - r / (maxR * 1.1)));
        c.beginPath();
        c.arc(slitX + 10, slitY1, r, -Math.PI / 2, Math.PI / 2);
        c.stroke();
        c.beginPath();
        c.arc(slitX + 10, slitY2, r, -Math.PI / 2, Math.PI / 2);
        c.stroke();
    }

    // Screen with the exact intensity I(y) = cos²(πd sinθ/λ)·sinc²(πa sinθ/λ)
    const top = 50;
    const bottom = h - 50;
    const pxPerMm = (bottom - top) / (2 * SCREEN_HALF_MM);
    const yOf = (yM) => h / 2 - yM * 1000 * pxPerMm;
    c.fillStyle = '#1e293b';
    c.fillRect(screenX, top, 30, bottom - top);

    const graphX = screenX + 35;
    const graphW = 100;
    c.strokeStyle = rgba(0.9);
    c.lineWidth = 1.5;
    c.beginPath();
    for (let y = top; y < bottom; y++) {
        const yM = ((h / 2 - y) / pxPerMm) * 1e-3;
        const I = doubleSlitIntensity(yM, slitParams.value);
        c.fillStyle = rgba(I);
        c.fillRect(screenX, y, 30, 1);
        if (y === top) c.moveTo(graphX + I * graphW, y);
        else c.lineTo(graphX + I * graphW, y);
    }
    c.stroke();

    // Order markers m = 0, ±1, ±2 (exact positions L·tan(asin(mλ/d)))
    c.font = '10px Inter';
    c.textAlign = 'right';
    c.fillStyle = '#f59e0b';
    for (let m = -2; m <= 2; m++) {
        const yM = brightFringePosition(m, lambdaM.value, SCREEN_DISTANCE, dM.value);
        if (!Number.isFinite(yM) || Math.abs(yM * 1000) > SCREEN_HALF_MM) continue;
        const yy = yOf(yM);
        c.fillRect(screenX - 8, yy - 0.5, 6, 1);
        c.fillText(m === 0 ? 'm=0' : `m=${m}`, screenX - 10, yy + 3);
    }

    c.fillStyle = '#f59e0b';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('Markaziy max', screenX + 15, top - 10);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText(`±${SCREEN_HALF_MM} mm`, screenX + 15, bottom + 15);
    c.fillText('I(y)', graphX + graphW / 2, bottom + 15);

    // Info panel (right of the barrier so it does not hide the slits)
    const px = 235;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(px, 20, 220, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`λ = ${wavelength.value} nm`, px + 15, 45);
    c.fillText(`d = ${slitSeparation.value} mm,  L = ${SCREEN_DISTANCE} m`, px + 15, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Δy = λL/d = ${(fringeSpacing.value * 1000).toFixed(2)} mm`, px + 15, 90);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('Konstruktiv: d·sinθ = mλ', px + 15, 115);
    c.fillText('Destruktiv: d·sinθ = (m+½)λ', px + 15, 132);
    c.fillText('I = I₀cos²(πd·sinθ/λ)', px + 15, 152);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>To'lqin uzunligi: <strong>{{ wavelength }} nm</strong></label>
                <input type="range" v-model.number="wavelength" :min="400" :max="700" :step="25" />
            </div>
            <div class="control-group">
                <label>Tirqishlar oralig'i: <strong>{{ slitSeparation }} mm</strong></label>
                <input type="range" v-model.number="slitSeparation" :min="0.05" :max="0.3" :step="0.025" />
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
    min-width: 180px;
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
