<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { NM, MM, toDeg, singleSlitMinimumAngle, singleSlitIntensity, screenSinTheta, wavelengthToRgb } from '../physics/optics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const wavelength = ref(600); // nm
const slitWidth = ref(0.1); // mm
const SCREEN_DISTANCE = 1; // L, m (slit → screen)
const PX_PER_MM = 8; // screen scale: 8 px = 1 mm on the screen

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

// First minimum a·sinθ = λ (null if λ > a, i.e. no minimum exists)
const firstMinimumRad = computed(() => singleSlitMinimumAngle(1, slitWidth.value * MM, wavelength.value * NM));
const firstMinimum = computed(() => (firstMinimumRad.value === null ? null : toDeg(firstMinimumRad.value))); // degrees
// Position of the first minimum on the screen y1 = L·tanθ1 (mm); central maximum width = 2·y1
const firstMinimumY = computed(() => (firstMinimumRad.value === null ? null : (SCREEN_DISTANCE * Math.tan(firstMinimumRad.value)) / MM));

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    if (firstMinimum.value === null) return;
    emit('measurement', { name: `Birinchi minimum burchagi (λ=${wavelength.value} nm, a=${slitWidth.value.toFixed(2)} mm)`, value: firstMinimum.value.toFixed(4), unit: '°' });
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
    c.arc(50, h / 2, 20, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();

    // Incoming plane wave (schematic)
    for (let i = 0; i < 5; i++) {
        const x = 100 + ((time.value * 50 + i * 30) % 150);
        c.strokeStyle = `rgba(${rgb}, ${0.5 - i * 0.1})`;
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(x, h / 2 - 100);
        c.lineTo(x, h / 2 + 100);
        c.stroke();
    }

    // Barrier with slit
    c.fillStyle = '#1e293b';
    c.fillRect(250, 0, 20, h / 2 - 30);
    c.fillRect(250, h / 2 + 30, 20, h / 2);

    // Huygens wavelets from slit (schematic)
    const slitY = h / 2;
    const numWavelets = 5;
    for (let i = 0; i < numWavelets; i++) {
        const waveY = slitY - 20 + i * 10;
        for (let r = 0; r < 5; r++) {
            const radius = ((time.value * 50 + r * 40) % 200);
            const alpha = Math.max(0, 0.3 - radius / 300);
            c.strokeStyle = `rgba(${rgb}, ${alpha})`;
            c.lineWidth = 2;
            c.beginPath();
            c.arc(270, waveY, radius, -Math.PI / 2, Math.PI / 2);
            c.stroke();
        }
    }

    // Screen
    const screenX = w - 150;
    c.fillStyle = '#1e293b';
    c.fillRect(screenX, 50, 30, h - 100);

    // Diffraction pattern: I/I0 = (sinβ/β)², β = πa·sinθ/λ, sinθ = y/√(y²+L²),
    // y = physical height on a screen L = 1 m away (8 px per mm).
    const a = slitWidth.value * MM;
    const wl = wavelength.value * NM;
    const plotX = screenX + 40;
    const plotW = w - plotX - 10;
    c.strokeStyle = `rgba(${rgb}, 0.9)`;
    c.lineWidth = 1;
    c.beginPath();
    for (let y = 50; y < h - 50; y++) {
        const yScreen = ((y + 0.5 - h / 2) / PX_PER_MM) * MM;
        const intensity = singleSlitIntensity(screenSinTheta(yScreen, SCREEN_DISTANCE), a, wl);

        c.fillStyle = `rgba(${rgb}, ${intensity})`;
        c.fillRect(screenX, y, 30, 1);

        const px = plotX + intensity * plotW;
        if (y === 50) c.moveTo(px, y); else c.lineTo(px, y);
    }
    c.stroke();

    // First-minimum markers at the computed screen positions ±y1
    // (the vertical scale is magnified relative to the slit–screen distance)
    if (firstMinimumY.value !== null) {
        const minY1 = h / 2 - firstMinimumY.value * PX_PER_MM;
        const minY2 = h / 2 + firstMinimumY.value * PX_PER_MM;

        c.strokeStyle = '#f59e0b';
        c.setLineDash([5, 5]);
        c.lineWidth = 1;
        c.beginPath();
        c.moveTo(270, h / 2);
        c.lineTo(screenX, minY1);
        c.moveTo(270, h / 2);
        c.lineTo(screenX, minY2);
        c.stroke();
        c.setLineDash([]);

        c.fillStyle = '#f59e0b';
        c.font = '11px Inter';
        c.textAlign = 'right';
        c.fillText(`θ₁ = ${firstMinimum.value.toFixed(3)}°`, screenX - 6, minY1 - 4);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 230, 165);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`To'lqin uzunligi: ${wavelength.value} nm`, 35, 45);
    c.fillText(`Tirqish kengligi: ${slitWidth.value} mm`, 35, 65);
    c.fillText(`Ekrangacha masofa: ${SCREEN_DISTANCE} m`, 35, 85);
    if (firstMinimum.value !== null) {
        c.fillStyle = '#f59e0b';
        c.fillText(`Birinchi minimum: ${firstMinimum.value.toFixed(3)}°`, 35, 110);
        c.fillText(`Markaziy maksimum: 2y₁ = ${(2 * firstMinimumY.value).toFixed(2)} mm`, 35, 130);
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('a·sin θ = mλ,  I = I₀(sin β/β)²', 35, 155);
    c.fillText('β = πa·sin θ/λ', 35, 172);
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
                <input type="range" v-model.number="wavelength" :min="400" :max="700" :step="20" />
            </div>
            <div class="control-group">
                <label>Tirqish kengligi: <strong>{{ slitWidth }} mm</strong></label>
                <input type="range" v-model.number="slitWidth" :min="0.05" :max="0.3" :step="0.01" />
            </div>
            <button @click="measure" class="btn">📏 O'lchash</button>
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
