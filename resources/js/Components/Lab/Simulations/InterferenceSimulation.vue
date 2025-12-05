<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const wavelength = ref(600); // nm
const slitDistance = ref(0.5); // mm
const screenDistance = ref(1); // m

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const fringeSpacing = computed(() => (wavelength.value * 1e-9 * screenDistance.value) / (slitDistance.value * 1e-3) * 1000); // mm

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Interferensiya oralig\'i', value: fringeSpacing.value.toFixed(3), unit: 'mm' });
};

const wavelengthToColor = (wl) => {
    if (wl >= 380 && wl < 440) return `rgb(${Math.round((440 - wl) / (440 - 380) * 255)}, 0, 255)`;
    if (wl >= 440 && wl < 490) return `rgb(0, ${Math.round((wl - 440) / (490 - 440) * 255)}, 255)`;
    if (wl >= 490 && wl < 510) return `rgb(0, 255, ${Math.round((510 - wl) / (510 - 490) * 255)})`;
    if (wl >= 510 && wl < 580) return `rgb(${Math.round((wl - 510) / (580 - 510) * 255)}, 255, 0)`;
    if (wl >= 580 && wl < 645) return `rgb(255, ${Math.round((645 - wl) / (645 - 580) * 255)}, 0)`;
    if (wl >= 645 && wl <= 780) return 'rgb(255, 0, 0)';
    return 'white';
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

    const lightColor = wavelengthToColor(wavelength.value);

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

    // Slits
    c.fillStyle = 'transparent';

    // Wave animation from slits
    const slitY1 = h / 2 - 12;
    const slitY2 = h / 2 + 12;

    for (let r = 0; r < 300; r += 30) {
        const radius = r + (time.value * 50) % 30;
        const alpha = Math.max(0, 1 - radius / 300);

        c.strokeStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${alpha * 0.5})`;
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

    // Interference pattern
    const numFringes = Math.floor(200 / (fringeSpacing.value * 20));
    for (let y = 50; y < h - 50; y += 2) {
        const yFromCenter = y - h / 2;
        const phase = (yFromCenter / (fringeSpacing.value * 20)) * Math.PI * 2;
        const intensity = Math.cos(phase) ** 2;

        c.fillStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${intensity})`;
        c.fillRect(screenX, y, 20, 2);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 280, 20, 170, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`To'lqin uzunligi: ${wavelength.value} nm`, w - 265, 45);
    c.fillText(`Tirqishlar oralig'i: ${slitDistance.value} mm`, w - 265, 65);
    c.fillText(`Ekran masofasi: ${screenDistance.value} m`, w - 265, 85);
    c.fillStyle = '#10b981';
    c.fillText(`Δy = ${fringeSpacing.value.toFixed(3)} mm`, w - 265, 110);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('Δy = λL/d', w - 265, 140);
};

watch([wavelength, slitDistance, screenDistance], () => { draw(); });
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
