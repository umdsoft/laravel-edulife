<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const frequency = ref(440);
const amplitude = ref(0.5);
const soundSpeed = 343;

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const wavelength = computed(() => soundSpeed / frequency.value);
const period = computed(() => 1 / frequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    time.value += 0.016;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;

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

    // Speaker vibration
    const vibOffset = Math.sin(time.value * frequency.value * 0.5) * 5 * amplitude.value;
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(75 + vibOffset, centerY, 25, 0, Math.PI * 2);
    c.fill();

    // Air molecules (compression and rarefaction)
    const numMolecules = 40;
    for (let i = 0; i < numMolecules; i++) {
        for (let j = 0; j < 6; j++) {
            const baseX = 120 + i * 18;
            const baseY = centerY - 80 + j * 32;

            const phase = 2 * Math.PI * (baseX / (wavelength.value * 2) - frequency.value * time.value * 0.01);
            const displacement = amplitude.value * 10 * Math.sin(phase);

            const x = baseX + displacement;

            c.beginPath();
            c.arc(x, baseY, 3, 0, Math.PI * 2);
            c.fillStyle = `rgba(59, 130, 246, ${0.5 + Math.cos(phase) * 0.3})`;
            c.fill();
        }
    }

    // Wave visualization
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 120; x < w - 50; x++) {
        const phase = 2 * Math.PI * ((x - 120) / (wavelength.value * 0.5) - frequency.value * time.value * 0.02);
        const y = centerY + amplitude.value * 80 * Math.sin(phase);
        if (x === 120) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Pressure visualization (bar)
    for (let x = 120; x < w - 50; x += 10) {
        const phase = 2 * Math.PI * ((x - 120) / (wavelength.value * 0.5) - frequency.value * time.value * 0.02);
        const pressure = Math.sin(phase);

        const barH = 20;
        const y = h - 60;

        if (pressure > 0) {
            c.fillStyle = `rgba(239, 68, 68, ${pressure * 0.6})`;
            c.fillRect(x, y, 8, barH);
            c.fillStyle = 'rgba(239, 68, 68, 0.3)';
            c.fillText('C', x + 2, y + 35);
        } else {
            c.fillStyle = `rgba(59, 130, 246, ${-pressure * 0.6})`;
            c.fillRect(x, y, 8, barH);
        }
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('C = Siqilish, R = Siyraklanish', 120, h - 15);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`Chastota: ${frequency.value} Hz`, w - 205, 45);
    c.fillText(`Amplituda: ${amplitude.value.toFixed(1)}`, w - 205, 65);
    c.fillStyle = '#3b82f6';
    c.fillText(`λ = ${(wavelength.value * 100).toFixed(1)} cm`, w - 205, 90);
    c.fillStyle = '#f59e0b';
    c.fillText(`v = ${soundSpeed} m/s`, w - 205, 110);
    c.fillStyle = '#22c55e';
    c.fillText(`T = ${(period.value * 1000).toFixed(2)} ms`, w - 205, 130);
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
                <input type="range" v-model.number="frequency" :min="100" :max="1000" :step="50" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude.toFixed(1) }}</strong></label>
                <input type="range" v-model.number="amplitude" :min="0.2" :max="1" :step="0.1" />
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
    min-width: 150px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}
</style>
