<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const sourceVelocity = ref(0);
const observerVelocity = ref(0);
const sourceFrequency = ref(440);
const soundSpeed = 343;

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const observedFrequency = computed(() => {
    const v = soundSpeed;
    const vs = sourceVelocity.value;
    const vo = observerVelocity.value;
    return sourceFrequency.value * (v + vo) / (v - vs);
});

const frequencyShift = computed(() => observedFrequency.value - sourceFrequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Kuzatilgan chastota', value: observedFrequency.value.toFixed(1), unit: 'Hz' });
};

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

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Road
    c.fillStyle = '#334155';
    c.fillRect(0, h / 2 - 30, w, 60);
    c.strokeStyle = '#f59e0b';
    c.setLineDash([30, 20]);
    c.beginPath();
    c.moveTo(0, h / 2);
    c.lineTo(w, h / 2);
    c.stroke();
    c.setLineDash([]);

    // Source position (car/ambulance)
    const sourceX = 200 + sourceVelocity.value * 2;

    // Sound waves from source
    const numWaves = 8;
    for (let i = 0; i < numWaves; i++) {
        const waveTime = (time.value * 2 + i * 0.5) % 4;
        const baseRadius = waveTime * 80;

        // Waves are compressed in front, expanded behind
        const frontRadius = baseRadius - sourceVelocity.value * waveTime * 0.3;
        const backRadius = baseRadius + sourceVelocity.value * waveTime * 0.3;

        const alpha = Math.max(0, 1 - waveTime / 4);
        c.strokeStyle = `rgba(59, 130, 246, ${alpha})`;
        c.lineWidth = 2;

        // Front waves (compressed)
        c.beginPath();
        c.arc(sourceX + frontRadius * 0.3, h / 2, frontRadius, -Math.PI / 2, Math.PI / 2);
        c.stroke();

        // Back waves (expanded)
        c.beginPath();
        c.arc(sourceX - backRadius * 0.3, h / 2, backRadius, Math.PI / 2, -Math.PI / 2);
        c.stroke();
    }

    // Source (car)
    c.fillStyle = '#ef4444';
    c.fillRect(sourceX - 30, h / 2 - 15, 60, 30);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('🔊', sourceX, h / 2 + 4);

    // Observer
    const obsX = 700 + observerVelocity.value;
    c.fillStyle = '#22c55e';
    c.beginPath();
    c.arc(obsX, h / 2, 20, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = 'white';
    c.fillText('👂', obsX, h / 2 + 5);

    // Frequency visualization
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(w / 2 - 150, 20, 300, 80);

    // Source frequency bar
    c.fillStyle = '#ef4444';
    c.fillRect(w / 2 - 140, 40, 120, 20);
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText(`Manba: ${sourceFrequency.value} Hz`, w / 2 - 135, 55);

    // Observed frequency bar
    const obsBarWidth = (observedFrequency.value / sourceFrequency.value) * 120;
    c.fillStyle = frequencyShift.value > 0 ? '#3b82f6' : '#f59e0b';
    c.fillRect(w / 2 - 140, 70, obsBarWidth, 20);
    c.fillText(`Kuzatilgan: ${observedFrequency.value.toFixed(0)} Hz`, w / 2 - 135, 85);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Manba tezligi: ${sourceVelocity.value} m/s`, 35, 45);
    c.fillText(`Kuzatuvchi tezligi: ${observerVelocity.value} m/s`, 35, 65);
    c.fillText(`Tovush tezligi: ${soundSpeed} m/s`, 35, 85);
    c.fillStyle = frequencyShift.value > 0 ? '#3b82f6' : '#f59e0b';
    c.fillText(`Δf = ${frequencyShift.value > 0 ? '+' : ''}${frequencyShift.value.toFixed(1)} Hz`, 35, 110);
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText(frequencyShift.value > 0 ? '↑ Yuqoriroq ton' : '↓ Pastroq ton', 35, 130);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Manba tezligi: <strong>{{ sourceVelocity }} m/s</strong></label>
                <input type="range" v-model.number="sourceVelocity" :min="-50" :max="100" :step="10" />
            </div>
            <div class="control-group">
                <label>Kuzatuvchi tezligi: <strong>{{ observerVelocity }} m/s</strong></label>
                <input type="range" v-model.number="observerVelocity" :min="-50" :max="50" :step="10" />
            </div>
            <div class="control-group">
                <label>Manba chastotasi: <strong>{{ sourceFrequency }} Hz</strong></label>
                <input type="range" v-model.number="sourceFrequency" :min="200" :max="800" :step="20" />
            </div>
            <button @click="measure" class="btn">📏</button>
        </div>
        <div class="formula">f' = f × (v + vₒ) / (v - vₛ)</div>
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
    font-size: 0.75rem;
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

.formula {
    font-family: serif;
    font-size: 1.1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
