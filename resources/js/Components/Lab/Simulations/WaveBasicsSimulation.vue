<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const amplitude = ref(50);
const frequency = ref(1);
const wavelength = ref(100);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);
const isRunning = ref(true);

const waveSpeed = computed(() => wavelength.value * frequency.value);
const period = computed(() => 1 / frequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'To\'lqin tezligi', value: waveSpeed.value.toFixed(1), unit: 'px/s' });
};

const animate = () => {
    if (isRunning.value && !props.isPaused) {
        time.value += 0.016;
    }
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

    // Grid
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let y = 50; y < h; y += 50) {
        c.beginPath();
        c.moveTo(0, y);
        c.lineTo(w, y);
        c.stroke();
    }

    // Center line
    c.strokeStyle = '#334155';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(0, centerY);
    c.lineTo(w, centerY);
    c.stroke();
    c.setLineDash([]);

    // Wave
    c.beginPath();
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 3;

    for (let x = 0; x < w; x++) {
        const phase = 2 * Math.PI * (x / wavelength.value - frequency.value * time.value);
        const y = centerY - amplitude.value * Math.sin(phase);

        if (x === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Wave particles
    for (let x = 0; x < w; x += 30) {
        const phase = 2 * Math.PI * (x / wavelength.value - frequency.value * time.value);
        const y = centerY - amplitude.value * Math.sin(phase);

        c.beginPath();
        c.arc(x, y, 5, 0, Math.PI * 2);
        c.fillStyle = '#60a5fa';
        c.fill();
    }

    // Wavelength indicator
    const startX = 100;
    const phase1 = 2 * Math.PI * (startX / wavelength.value - frequency.value * time.value);
    const y1 = centerY - amplitude.value * Math.sin(phase1);

    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.setLineDash([3, 3]);
    c.beginPath();
    c.moveTo(startX, y1);
    c.lineTo(startX, centerY + 80);
    c.moveTo(startX + wavelength.value, y1);
    c.lineTo(startX + wavelength.value, centerY + 80);
    c.stroke();

    c.beginPath();
    c.moveTo(startX, centerY + 70);
    c.lineTo(startX + wavelength.value, centerY + 70);
    c.stroke();
    c.setLineDash([]);

    c.fillStyle = '#f59e0b';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`λ = ${wavelength.value} px`, startX + wavelength.value / 2, centerY + 90);

    // Amplitude indicator
    c.strokeStyle = '#22c55e';
    c.setLineDash([3, 3]);
    c.beginPath();
    c.moveTo(50, centerY);
    c.lineTo(50, centerY - amplitude.value);
    c.stroke();
    c.setLineDash([]);

    c.fillStyle = '#22c55e';
    c.textAlign = 'left';
    c.fillText(`A = ${amplitude.value} px`, 60, centerY - amplitude.value / 2);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Amplituda (A): ${amplitude.value} px`, w - 205, 45);
    c.fillText(`Chastota (f): ${frequency.value.toFixed(1)} Hz`, w - 205, 65);
    c.fillText(`To'lqin uzunligi (λ): ${wavelength.value} px`, w - 205, 85);
    c.fillStyle = '#10b981';
    c.fillText(`Tezlik (v): ${waveSpeed.value.toFixed(1)} px/s`, w - 205, 110);
    c.fillStyle = '#f59e0b';
    c.fillText(`Davr (T): ${period.value.toFixed(2)} s`, w - 205, 130);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('v = λf', w - 205, 145);
};

watch([amplitude, frequency, wavelength], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }} px</strong></label>
                <input type="range" v-model.number="amplitude" :min="10" :max="100" :step="5" />
            </div>
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="0.5" :max="3" :step="0.1" />
            </div>
            <div class="control-group">
                <label>To'lqin uzunligi: <strong>{{ wavelength }} px</strong></label>
                <input type="range" v-model.number="wavelength" :min="50" :max="200" :step="10" />
            </div>
            <div class="buttons">
                <button @click="isRunning = !isRunning" class="btn">{{ isRunning ? '⏸️' : '▶️' }}</button>
                <button @click="measure" class="btn measure">📏</button>
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
    min-width: 140px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    background: #475569;
    color: white;
}

.btn.measure {
    background: #3b82f6;
}
</style>
