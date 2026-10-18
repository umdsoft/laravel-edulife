<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    frameDt,
    springPeriod,
    springState,
    springUpwardCrossings,
} from '../physics/mechanics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const springK = ref(numberOr(props.config?.spring?.k?.default, 50)); // N/m
const mass = ref(numberOr(props.config?.mass?.default, 0.5)); // kg
const amplitude = ref(numberOr(props.config?.amplitude?.default, 0.1)); // m

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

const displacement = ref(0);
const velocity = ref(0);
const time = ref(0);
const period = ref(0);
const lastZeroCrossing = ref(0);
const crossingCount = ref(0);

const theoreticalPeriod = computed(() => springPeriod(springK.value, mass.value)); // T = 2π√(m/k)

const canvasConfig = computed(() => props.config?.canvas || { width: 800, height: 500 });

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const stopLoop = () => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    animationId.value = null;
    lastFrameTs = null;
};

const startLoop = () => {
    stopLoop();
    animationId.value = requestAnimationFrame(animate);
};

const start = () => {
    if (isRunning.value) return;
    displacement.value = amplitude.value;
    velocity.value = 0;
    time.value = 0;
    period.value = 0;
    crossingCount.value = 0;
    lastZeroCrossing.value = 0;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    displacement.value = amplitude.value;
    velocity.value = 0;
    time.value = 0;
    period.value = 0;
    crossingCount.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Davr (k=${springK.value}, m=${mass.value})`, value: period.value.toFixed(3), unit: 's' });
};

const animate = (ts) => {
    animationId.value = null;
    if (!isRunning.value || props.isPaused) {
        lastFrameTs = null;
        return;
    }
    const now = ts ?? performance.now();
    const dt = frameDt(lastFrameTs, now);
    lastFrameTs = now;

    const t0 = time.value;
    const t1 = t0 + dt;

    // Exact SHM: x(t) = A·cos(ωt), v(t) = −Aω·sin(ωt) (released from rest at x = A)
    const s = springState(t1, amplitude.value, springK.value, mass.value);
    displacement.value = s.x;
    velocity.value = s.v;
    time.value = t1;

    // Period from successive upward zero crossings (exact crossing instants)
    for (const tc of springUpwardCrossings(t0, t1, springK.value, mass.value)) {
        crossingCount.value++;
        if (crossingCount.value > 1) {
            period.value = tc - lastZeroCrossing.value;
        }
        lastZeroCrossing.value = tc;
    }

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

    // Ceiling
    c.fillStyle = '#334155';
    c.fillRect(0, 0, w, 30);

    // Spring drawing
    const springTop = 30;
    const equilibrium = 200;
    const dispScale = 500;
    const currentY = equilibrium + displacement.value * dispScale;

    // Spring coils
    const coils = 12;
    const coilWidth = 30;
    c.strokeStyle = '#94a3b8';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(w / 2, springTop);

    for (let i = 0; i <= coils; i++) {
        const y = springTop + (currentY - springTop) * (i / coils);
        const x = w / 2 + (i % 2 === 0 ? coilWidth : -coilWidth);
        c.lineTo(x, y);
    }
    c.lineTo(w / 2, currentY);
    c.stroke();

    // Mass
    const massSize = 50 + mass.value * 20;
    c.fillStyle = '#3b82f6';
    c.fillRect(w / 2 - massSize / 2, currentY, massSize, massSize);
    c.fillStyle = 'white';
    c.font = 'bold 12px Inter';
    c.textAlign = 'center';
    c.fillText(`${mass.value} kg`, w / 2, currentY + massSize / 2 + 4);

    // Equilibrium line
    c.strokeStyle = '#22c55e44';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(100, equilibrium);
    c.lineTo(w - 100, equilibrium);
    c.stroke();
    c.setLineDash([]);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 50, 200, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Bikrlik (k): ${springK.value} N/m`, 35, 75);
    c.fillText(`Massa: ${mass.value} kg`, 35, 95);
    c.fillText(`Amplituda: ${amplitude.value} m`, 35, 115);
    c.fillStyle = '#10b981';
    c.fillText(`Davr (T): ${period.value.toFixed(3)} s`, 35, 140);
    c.fillStyle = '#3b82f6';
    c.fillText(`Nazariy T: ${theoreticalPeriod.value.toFixed(3)} s`, 35, 160);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 180);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([springK, mass, amplitude], () => { if (!isRunning.value) { displacement.value = amplitude.value; draw(); } });
onMounted(() => { displacement.value = amplitude.value; initCanvas(); });
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Bikrlik (k): <strong>{{ springK }} N/m</strong></label>
                <input type="range" v-model.number="springK" :min="10" :max="200" :step="10" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.1" :max="2" :step="0.1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }} m</strong></label>
                <input type="range" v-model.number="amplitude" :min="0.05" :max="0.2" :step="0.01"
                    :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" :disabled="period === 0" class="btn measure">📏</button>
            </div>
        </div>
        <div class="formula">T = 2π√(m/k)</div>
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
    align-items: flex-end;
}

.btn {
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #10b981;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}

.btn.measure {
    background: #3b82f6;
    color: white;
}

.formula {
    font-family: serif;
    font-size: 1.25rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
