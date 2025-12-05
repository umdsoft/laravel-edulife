<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const freq1 = ref(440);
const freq2 = ref(444);
const amplitude = ref(0.5);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);
const time = ref(0);

const beatFrequency = computed(() => Math.abs(freq2.value - freq1.value));
const avgFrequency = computed(() => (freq1.value + freq2.value) / 2);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    if (!isRunning.value) return;
    time.value += 0.016;
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

    const graphW = w - 100;
    const centerY1 = 100;
    const centerY2 = 200;
    const centerY3 = 350;

    // Wave 1
    c.strokeStyle = '#ef4444';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < graphW; x++) {
        const t = x / 200 + time.value;
        const y = centerY1 + amplitude.value * 40 * Math.sin(2 * Math.PI * freq1.value * t * 0.01);
        if (x === 0) c.moveTo(50 + x, y);
        else c.lineTo(50 + x, y);
    }
    c.stroke();
    c.fillStyle = '#ef4444';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`f₁ = ${freq1.value} Hz`, 60, centerY1 - 50);

    // Wave 2
    c.strokeStyle = '#3b82f6';
    c.beginPath();
    for (let x = 0; x < graphW; x++) {
        const t = x / 200 + time.value;
        const y = centerY2 + amplitude.value * 40 * Math.sin(2 * Math.PI * freq2.value * t * 0.01);
        if (x === 0) c.moveTo(50 + x, y);
        else c.lineTo(50 + x, y);
    }
    c.stroke();
    c.fillStyle = '#3b82f6';
    c.fillText(`f₂ = ${freq2.value} Hz`, 60, centerY2 - 50);

    // Plus sign
    c.fillStyle = '#f59e0b';
    c.font = '24px Inter';
    c.textAlign = 'center';
    c.fillText('+', 25, (centerY1 + centerY2) / 2 + 8);

    // Resultant wave (beats)
    c.strokeStyle = '#22c55e';
    c.lineWidth = 3;
    c.beginPath();
    for (let x = 0; x < graphW; x++) {
        const t = x / 200 + time.value;
        const y1 = amplitude.value * 40 * Math.sin(2 * Math.PI * freq1.value * t * 0.01);
        const y2 = amplitude.value * 40 * Math.sin(2 * Math.PI * freq2.value * t * 0.01);
        const y = centerY3 + y1 + y2;
        if (x === 0) c.moveTo(50 + x, y);
        else c.lineTo(50 + x, y);
    }
    c.stroke();

    // Beat envelope
    c.strokeStyle = 'rgba(249, 115, 22, 0.5)';
    c.lineWidth = 2;
    c.setLineDash([5, 5]);
    c.beginPath();
    for (let x = 0; x < graphW; x++) {
        const t = x / 200 + time.value;
        const envelope = 2 * amplitude.value * 40 * Math.abs(Math.cos(Math.PI * beatFrequency.value * t * 0.01));
        const y = centerY3 - envelope;
        if (x === 0) c.moveTo(50 + x, y);
        else c.lineTo(50 + x, y);
    }
    c.stroke();
    c.beginPath();
    for (let x = 0; x < graphW; x++) {
        const t = x / 200 + time.value;
        const envelope = 2 * amplitude.value * 40 * Math.abs(Math.cos(Math.PI * beatFrequency.value * t * 0.01));
        const y = centerY3 + envelope;
        if (x === 0) c.moveTo(50 + x, y);
        else c.lineTo(50 + x, y);
    }
    c.stroke();
    c.setLineDash([]);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 100);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`f₁ = ${freq1.value} Hz`, w - 205, 45);
    c.fillText(`f₂ = ${freq2.value} Hz`, w - 205, 65);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.fillText(`Urishlar: ${beatFrequency.value} Hz`, w - 205, 90);
    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('fᵤ = |f₁ - f₂|', w - 205, 110);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>f₁: <strong>{{ freq1 }} Hz</strong></label>
                <input type="range" v-model.number="freq1" :min="400" :max="500" :step="2" />
            </div>
            <div class="control-group">
                <label>f₂: <strong>{{ freq2 }} Hz</strong></label>
                <input type="range" v-model.number="freq2" :min="400" :max="500" :step="2" />
            </div>
            <div class="beat-display">
                <span>Urishlar:</span>
                <strong>{{ beatFrequency }} Hz</strong>
            </div>
            <button @click="isRunning = !isRunning" class="btn">{{ isRunning ? '⏸️' : '▶️' }}</button>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 120px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.beat-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #0f172a;
    border-radius: 8px;
}

.beat-display span {
    font-size: 0.75rem;
    color: #94a3b8;
}

.beat-display strong {
    font-size: 1.25rem;
    color: #f59e0b;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
}
</style>
