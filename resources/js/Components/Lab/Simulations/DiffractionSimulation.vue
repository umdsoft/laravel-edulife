<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const wavelength = ref(600);
const slitWidth = ref(0.1);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const firstMinimum = computed(() => Math.asin(wavelength.value * 1e-9 / (slitWidth.value * 1e-3)) * 180 / Math.PI);

const wavelengthToColor = (wl) => {
    if (wl >= 380 && wl < 440) return `rgb(${Math.round((440 - wl) / (440 - 380) * 255)}, 0, 255)`;
    if (wl >= 440 && wl < 490) return `rgb(0, ${Math.round((wl - 440) / (490 - 440) * 255)}, 255)`;
    if (wl >= 490 && wl < 510) return `rgb(0, 255, ${Math.round((510 - wl) / (510 - 490) * 255)})`;
    if (wl >= 510 && wl < 580) return `rgb(${Math.round((wl - 510) / (580 - 510) * 255)}, 255, 0)`;
    if (wl >= 580 && wl < 645) return `rgb(255, ${Math.round((645 - wl) / (645 - 580) * 255)}, 0)`;
    if (wl >= 645 && wl <= 780) return 'rgb(255, 0, 0)';
    return 'white';
};

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
    c.arc(50, h / 2, 20, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();

    // Incoming plane wave
    for (let i = 0; i < 5; i++) {
        const x = 100 + ((time.value * 50 + i * 30) % 150);
        c.strokeStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${0.5 - i * 0.1})`;
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

    // Huygen's wavelets from slit
    const slitY = h / 2;
    const numWavelets = 5;
    for (let i = 0; i < numWavelets; i++) {
        const waveY = slitY - 20 + i * 10;
        for (let r = 0; r < 5; r++) {
            const radius = ((time.value * 50 + r * 40) % 200);
            const alpha = Math.max(0, 0.3 - radius / 300);
            c.strokeStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${alpha})`;
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

    // Diffraction pattern on screen
    const beta = Math.PI * slitWidth.value * 1e-3 / (wavelength.value * 1e-9);
    for (let y = 50; y < h - 50; y++) {
        const angle = (y - h / 2) / 300;
        const sinc = angle === 0 ? 1 : Math.sin(beta * angle) / (beta * angle);
        const intensity = sinc * sinc;

        c.fillStyle = `rgba(${lightColor.match(/\d+/g).join(',')}, ${intensity})`;
        c.fillRect(screenX, y, 30, 1);
    }

    // Angle markers
    if (firstMinimum.value < 90) {
        const minAngle = firstMinimum.value * Math.PI / 180;
        const minY1 = h / 2 - 300 * Math.tan(minAngle);
        const minY2 = h / 2 + 300 * Math.tan(minAngle);

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
        c.textAlign = 'left';
        c.fillText(`θ₁ = ${firstMinimum.value.toFixed(1)}°`, screenX + 40, minY1);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`To'lqin uzunligi: ${wavelength.value} nm`, 35, 45);
    c.fillText(`Tirqish kengligi: ${slitWidth.value} mm`, 35, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Birinchi minimum: ${firstMinimum.value.toFixed(1)}°`, 35, 90);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('sin θ = λ/a', 35, 120);
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
