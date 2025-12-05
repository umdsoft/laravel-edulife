<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const slitSeparation = ref(0.1);
const wavelength = ref(550);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const wavelengthToColor = (wl) => {
    if (wl >= 380 && wl < 440) return `rgb(${Math.round((440 - wl) / (440 - 380) * 255)}, 0, 255)`;
    if (wl >= 440 && wl < 490) return `rgb(0, ${Math.round((wl - 440) / (490 - 440) * 255)}, 255)`;
    if (wl >= 490 && wl < 510) return `rgb(0, 255, ${Math.round((510 - wl) / (510 - 490) * 255)})`;
    if (wl >= 510 && wl < 580) return `rgb(${Math.round((wl - 510) / (580 - 510) * 255)}, 255, 0)`;
    if (wl >= 580 && wl < 645) return `rgb(255, ${Math.round((645 - wl) / (645 - 580) * 255)}, 0)`;
    if (wl >= 645 && wl <= 780) return 'rgb(255, 0, 0)';
    return 'white';
};

const fringeSpacing = computed(() => wavelength.value * 1e-9 / (slitSeparation.value * 1e-3) * 1000);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

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

    const lightColor = wavelengthToColor(wavelength.value);

    // Light source
    c.beginPath();
    c.arc(50, h / 2, 15, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();

    // Plane waves
    for (let i = 0; i < 4; i++) {
        const x = 80 + ((time.value * 30 + i * 25) % 100);
        c.strokeStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${0.5 - i * 0.1})`;
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(x, h / 2 - 80);
        c.lineTo(x, h / 2 + 80);
        c.stroke();
    }

    // Double slit barrier
    const slitX = 200;
    const slitY1 = h / 2 - 30;
    const slitY2 = h / 2 + 30;

    c.fillStyle = '#1e293b';
    c.fillRect(slitX - 10, 50, 20, slitY1 - 50 - 10);
    c.fillRect(slitX - 10, slitY1 + 10, 20, slitY2 - slitY1 - 20);
    c.fillRect(slitX - 10, slitY2 + 10, 20, h - 50 - slitY2 - 10);

    // Circular waves from slits
    const numWaves = 8;
    for (let i = 0; i < numWaves; i++) {
        const radius = ((time.value * 40 + i * 30) % 300);
        const alpha = Math.max(0, 0.4 - radius / 400);

        c.strokeStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${alpha})`;
        c.lineWidth = 2;

        // From slit 1
        c.beginPath();
        c.arc(slitX + 10, slitY1, radius, -Math.PI / 2, Math.PI / 2);
        c.stroke();

        // From slit 2
        c.beginPath();
        c.arc(slitX + 10, slitY2, radius, -Math.PI / 2, Math.PI / 2);
        c.stroke();
    }

    // Screen
    const screenX = w - 150;
    c.fillStyle = '#1e293b';
    c.fillRect(screenX, 50, 30, h - 100);

    // Interference pattern
    const d = slitSeparation.value * 1e-3;
    const lambda = wavelength.value * 1e-9;

    for (let y = 50; y < h - 50; y++) {
        const screenY = (y - h / 2) / 300;
        const pathDiff = d * screenY;
        const phase = (2 * Math.PI * pathDiff) / lambda;
        const intensity = Math.pow(Math.cos(phase * 50), 2);

        c.fillStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${intensity})`;
        c.fillRect(screenX, y, 30, 1);
    }

    // Central maximum label
    c.fillStyle = '#f59e0b';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('Markaziy max', screenX + 40, h / 2);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`λ = ${wavelength.value} nm`, 35, 45);
    c.fillText(`d = ${slitSeparation.value} mm`, 35, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Δy ∝ λ/d`, 35, 90);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('Konstruktiv: d·sinθ = nλ', 35, 115);
    c.fillText('Destruktiv: d·sinθ = (n+½)λ', 35, 130);
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
</style>
