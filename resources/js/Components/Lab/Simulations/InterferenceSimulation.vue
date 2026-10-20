<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    NM, MM, fringeSpacing as youngFringeSpacing, doubleSlitIntensity, screenSinTheta, wavelengthToRgb,
    fringeCountForSpan, OPTICS_INSTRUMENTS,
} from '../physics/optics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const wavelength = ref(600); // nm
const slitDistance = ref(0.5); // mm (slit separation d)
const screenDistance = ref(1); // m (L)
const SLIT_WIDTH_MM = 0.02; // width a of each slit (single-slit envelope), mm

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

// Δy = λL/d, in mm
const fringeSpacing = computed(() => youngFringeSpacing(wavelength.value * NM, screenDistance.value, slitDistance.value * MM) / MM);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));
const PX_PER_MM = 20; // screen scale: 20 px = 1 mm on the screen

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

// Number of spacings measured across (all inside the central single-slit envelope).
const fringeCount = computed(() => fringeCountForSpan(slitDistance.value * MM, SLIT_WIDTH_MM * MM));

// As in a real lab the spacing is not read directly: the cross-hair of a measuring
// microscope (0.01 mm, σ = 0.01 mm per setting) is set on the centres of the bright
// fringes −N/2 and +N/2, and Δy = (x₊ − x₋)/N. Readings are seeded from the set-up.
const micrometer = createInstrument(OPTICS_INSTRUMENTS.fringeMicrometer, 'InterferenceSimulation');
const lastSpan = ref(null);

const measure = () => {
    const N = fringeCount.value;
    const key = [wavelength.value, slitDistance.value, screenDistance.value];
    const half = (N / 2) * fringeSpacing.value; // mm
    const left = micrometer.read(-half, [...key, 'chap']);
    const right = micrometer.read(half, [...key, 'ong']);
    const span = right - left;
    lastSpan.value = { N, span, spacing: span / N };
    emit('measurement', { name: `${N} ta oraliq kengligi (mikroskop)`, value: span.toFixed(2), unit: 'mm' });
    emit('measurement', { name: 'Interferensiya oralig\'i', value: (span / N).toFixed(3), unit: 'mm' });
};

const animate = () => {
    time.value += 0.02;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    const rgb = wavelengthToRgb(wavelength.value * NM).join(',');
    const lightColor = `rgb(${rgb})`;

    // Light source
    c.beginPath();
    c.arc(50, h / 2, 15, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('Yorug\'lik', 50, h / 2 + 35);

    // Barrier with slits
    const barrierX = 200;
    c.fillStyle = '#1e293b';
    c.fillRect(barrierX - 5, 0, 10, h / 2 - 20);
    c.fillRect(barrierX - 5, h / 2 + 20, 10, h / 2);
    c.fillRect(barrierX - 5, h / 2 - 5, 10, 10); // middle block

    // Wave animation from slits (schematic)
    const slitY1 = h / 2 - 12;
    const slitY2 = h / 2 + 12;

    for (let r = 0; r < 300; r += 30) {
        const radius = r + (time.value * 50) % 30;
        const alpha = Math.max(0, 1 - radius / 300);

        c.strokeStyle = `rgba(${rgb}, ${alpha * 0.5})`;
        c.lineWidth = 2;

        c.beginPath();
        c.arc(barrierX + 10, slitY1, radius, -Math.PI / 2, Math.PI / 2);
        c.stroke();

        c.beginPath();
        c.arc(barrierX + 10, slitY2, radius, -Math.PI / 2, Math.PI / 2);
        c.stroke();
    }

    // Screen with interference pattern
    const screenX = w - 100;
    c.fillStyle = '#1e293b';
    c.fillRect(screenX, 50, 20, h - 100);

    // Exact Fraunhofer intensity I/I0 = cos²(πd sinθ/λ)·(sinβ/β)², β = πa sinθ/λ,
    // sinθ = y/√(y²+L²); sampled every pixel so the 4-px fringes of the extreme
    // settings are not aliased.
    const wl = wavelength.value * NM;
    const d = slitDistance.value * MM;
    const L = screenDistance.value;
    const a = SLIT_WIDTH_MM * MM;
    const plotX = screenX + 25;
    const plotW = w - plotX - 5;
    c.strokeStyle = `rgba(${rgb}, 0.9)`;
    c.lineWidth = 1;
    c.beginPath();
    for (let y = 50; y < h - 50; y++) {
        const yScreen = ((y + 0.5 - h / 2) / PX_PER_MM) * MM; // m
        const intensity = doubleSlitIntensity(screenSinTheta(yScreen, L), wl, d, a);

        c.fillStyle = `rgba(${rgb}, ${intensity})`;
        c.fillRect(screenX, y, 20, 1);

        // intensity profile next to the screen
        const px = plotX + intensity * plotW;
        if (y === 50) c.moveTo(px, y); else c.lineTo(px, y);
    }
    c.stroke();

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 300, 20, 190, 160);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`To'lqin uzunligi: ${wavelength.value} nm`, w - 285, 45);
    c.fillText(`Tirqishlar oralig'i: ${slitDistance.value} mm`, w - 285, 65);
    c.fillText(`Ekran masofasi: ${screenDistance.value} m`, w - 285, 85);
    c.fillText(`Tirqish kengligi: ${SLIT_WIDTH_MM} mm`, w - 285, 105);
    c.fillStyle = '#10b981';
    c.fillText(`Δy = ${fringeSpacing.value.toFixed(3)} mm`, w - 285, 130);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('Δy = λL/d', w - 285, 150);
    c.fillText('Ekran: 1 mm = 20 px', w - 285, 168);
};

watch([wavelength, slitDistance, screenDistance], () => { lastSpan.value = null; draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>To'lqin uzunligi: <strong>{{ wavelength }} nm</strong></label>
                <input type="range" v-model.number="wavelength" :min="400" :max="700" :step="10" />
            </div>
            <div class="control-group">
                <label>Tirqish oralig'i: <strong>{{ slitDistance }} mm</strong></label>
                <input type="range" v-model.number="slitDistance" :min="0.1" :max="1" :step="0.1" />
            </div>
            <div class="control-group">
                <label>Ekran masofasi: <strong>{{ screenDistance }} m</strong></label>
                <input type="range" v-model.number="screenDistance" :min="0.5" :max="2" :step="0.1" />
            </div>
            <button @click="measure" class="btn measure">📏</button>
            <div v-if="lastSpan" class="control-group">
                <label>Mikroskop: <strong>{{ lastSpan.N }}·Δy = {{ lastSpan.span.toFixed(2) }} mm</strong></label>
                <label>Δy = <strong>{{ lastSpan.spacing.toFixed(3) }} mm</strong></label>
            </div>
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
    font-weight: 600;
    cursor: pointer;
    background: #3b82f6;
    color: white;
}
</style>
