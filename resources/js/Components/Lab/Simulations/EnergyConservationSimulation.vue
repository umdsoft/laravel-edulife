<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { resolveGravity, frameDt, dropWithResistance } from '../physics/mechanics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const mass = ref(1); // kg
const height = ref(3); // m
// Resistance as a fraction k of the weight: F_r = k·m·g (0 = ideal, energy fully conserved)
const friction = ref(0);
const gravity = resolveGravity(props.config?.physics?.gravity);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

const time = ref(0);

// Closed-form state: s = ½at², v = at with a = g(1 − k); Q = k·m·g·s
const drop = computed(() => dropWithResistance(time.value, height.value, mass.value, gravity, friction.value));
const position = computed(() => drop.value.s); // distance fallen, m
const velocity = computed(() => drop.value.v); // m/s

const initialPE = computed(() => mass.value * gravity * height.value);
const currentPE = computed(() => drop.value.pe);
const currentKE = computed(() => drop.value.ke);
const heatQ = computed(() => drop.value.heat);
// Mechanical energy KE + PE (decreases only by the heat Q produced by the resistance)
const totalE = computed(() => drop.value.mechanical);

const canvasConfig = computed(() => ({ width: 900, height: 600 }));
const scale = 40;
const BALL_R = 20;

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
    time.value = 0;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: 'Jami energiya', value: totalE.value.toFixed(2), unit: 'J' });
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

    const tEnd = drop.value.tEnd;
    time.value = Math.min(time.value + dt, tEnd);
    if (time.value >= tEnd) isRunning.value = false;

    draw();
    if (isRunning.value) animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Ground
    c.fillStyle = '#334155';
    c.fillRect(0, h - 50, w, 50);

    // Height reference
    const groundY = h - 50;
    const topY = groundY - height.value * scale;

    // Height scale
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(100, topY);
    c.lineTo(100, groundY);
    c.stroke();

    for (let i = 0; i <= height.value; i++) {
        const y = groundY - i * scale;
        c.beginPath();
        c.moveTo(90, y);
        c.lineTo(100, y);
        c.stroke();
        c.fillStyle = '#94a3b8';
        c.font = '11px Inter';
        c.textAlign = 'right';
        c.fillText(`${i}m`, 85, y + 4);
    }

    // Ball: its bottom is at the current height (h − s), so it rests on the ground at the end
    const ballY = topY + position.value * scale - BALL_R;
    c.beginPath();
    c.arc(200, ballY, BALL_R, 0, Math.PI * 2);
    c.fillStyle = '#3b82f6';
    c.fill();

    // Energy bars
    const barX = 400;
    const barW = 200;
    const barH = 30;
    const maxE = initialPE.value;

    // PE bar
    c.fillStyle = '#10b981';
    c.fillRect(barX, 100, barW * (currentPE.value / maxE), barH);
    c.strokeStyle = '#064e3b';
    c.strokeRect(barX, 100, barW, barH);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`PE: ${currentPE.value.toFixed(2)} J`, barX + barW + 20, 120);

    // KE bar
    c.fillStyle = '#ef4444';
    c.fillRect(barX, 150, barW * (currentKE.value / maxE), barH);
    c.strokeStyle = '#7f1d1d';
    c.strokeRect(barX, 150, barW, barH);
    c.fillText(`KE: ${currentKE.value.toFixed(2)} J`, barX + barW + 20, 170);

    // Total (mechanical) bar
    c.fillStyle = '#f59e0b';
    c.fillRect(barX, 200, barW * (totalE.value / maxE), barH);
    c.strokeStyle = '#78350f';
    c.strokeRect(barX, 200, barW, barH);
    c.fillText(`Total: ${totalE.value.toFixed(2)} J`, barX + barW + 20, 220);

    // Stacked energy bar: PE + KE + Q = mgh
    c.fillStyle = '#10b981';
    c.fillRect(barX, 270, barW * (currentPE.value / maxE), 50);
    c.fillStyle = '#ef4444';
    c.fillRect(barX + barW * (currentPE.value / maxE), 270, barW * (currentKE.value / maxE), 50);
    c.fillStyle = '#a855f7';
    c.fillRect(barX + barW * ((currentPE.value + currentKE.value) / maxE), 270, barW * (heatQ.value / maxE), 50);
    c.strokeStyle = '#475569';
    c.strokeRect(barX, 270, barW, 50);

    // Labels
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.fillText(
        friction.value > 0
            ? 'KE + PE + Q = mgh = const (Q — ishqalanish issiqligi)'
            : 'KE + PE = const (energiya saqlanishi)',
        barX,
        340,
    );

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 240, 20, 220, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Massa: ${mass.value} kg`, w - 225, 45);
    c.fillText(`Balandlik: ${height.value} m`, w - 225, 65);
    c.fillText(`Tezlik: ${velocity.value.toFixed(2)} m/s`, w - 225, 85);
    c.fillStyle = '#10b981';
    c.fillText(`PE: ${currentPE.value.toFixed(2)} J`, w - 225, 110);
    c.fillStyle = '#ef4444';
    c.fillText(`KE: ${currentKE.value.toFixed(2)} J`, w - 225, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Jami E: ${totalE.value.toFixed(2)} J`, w - 225, 150);
    c.fillStyle = '#a855f7';
    c.fillText(`Issiqlik (Q): ${heatQ.value.toFixed(2)} J`, w - 225, 170);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, w - 225, 190);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([mass, height, friction], () => {
    if (!isRunning.value) {
        time.value = 0; // new parameters → ball back at the release height
        draw();
    }
});
onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.5" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Balandlik: <strong>{{ height }} m</strong></label>
                <input type="range" v-model.number="height" :min="1" :max="10" :step="1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Ishqalanish: <strong>{{ (friction * 100).toFixed(0) }}%</strong></label>
                <input type="range" v-model.number="friction" :min="0" :max="0.3" :step="0.05" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Tushirish</button>
                <button @click="reset" class="btn reset">🔄</button>
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
</style>
