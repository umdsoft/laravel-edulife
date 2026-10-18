<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    frameDt,
    momentum,
    kineticEnergy,
    collisionVelocities,
    collisionTime,
} from '../physics/mechanics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const mass1 = ref(2);
const mass2 = ref(1);
const v1 = ref(5);
const v2 = ref(-3);
const elasticity = ref(1); // coefficient of restitution: 1 = elastic, 0 = perfectly inelastic

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

const PX_PER_M = 30;
const X1_START = 150; // px
const X2_START = 600; // px
const radius = (m) => 25 + m * 5; // drawing radius, px

const pos1 = ref(X1_START);
const pos2 = ref(X2_START);
const vel1 = ref(0);
const vel2 = ref(0);
const hasCollided = ref(false);
const time = ref(0);

// Pre-computed exact collision: contact time and post-collision velocities
let collisionAt = Infinity;
let afterCollision = { v1: 0, v2: 0 };

const initialMomentum = computed(() => momentum(mass1.value, v1.value) + momentum(mass2.value, v2.value));
const initialKE = computed(() => kineticEnergy(mass1.value, v1.value) + kineticEnergy(mass2.value, v2.value));

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

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
    pos1.value = X1_START;
    pos2.value = X2_START;
    vel1.value = v1.value;
    vel2.value = v2.value;
    hasCollided.value = false;
    time.value = 0;

    // Contact when centre distance = r1 + r2: t_c = gap / (u1 − u2)
    collisionAt = collisionTime(
        X1_START, X2_START,
        v1.value * PX_PER_M, v2.value * PX_PER_M,
        radius(mass1.value) + radius(mass2.value),
    );
    afterCollision = collisionVelocities(mass1.value, mass2.value, v1.value, v2.value, elasticity.value);

    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    pos1.value = X1_START;
    pos2.value = X2_START;
    vel1.value = 0;
    vel2.value = 0;
    hasCollided.value = false;
    time.value = 0;
    draw();
};

const measure = () => {
    const p = momentum(mass1.value, vel1.value) + momentum(mass2.value, vel2.value);
    emit('measurement', { name: 'Yakuniy impuls', value: p.toFixed(3), unit: 'kg·m/s' });
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
    const t = time.value + dt;
    time.value = t;

    // Piecewise-exact uniform motion before / after the instantaneous collision
    const tc = collisionAt;
    if (t < tc) {
        pos1.value = X1_START + v1.value * PX_PER_M * t;
        pos2.value = X2_START + v2.value * PX_PER_M * t;
        vel1.value = v1.value;
        vel2.value = v2.value;
    } else {
        hasCollided.value = true;
        const c1 = X1_START + v1.value * PX_PER_M * tc;
        const c2 = X2_START + v2.value * PX_PER_M * tc;
        vel1.value = afterCollision.v1;
        vel2.value = afterCollision.v2;
        pos1.value = c1 + vel1.value * PX_PER_M * (t - tc);
        pos2.value = c2 + vel2.value * PX_PER_M * (t - tc);
    }

    // Stop when a body leaves the track, or when nothing moves any more
    const outOfBounds = pos1.value < 50 || pos1.value > 850 || pos2.value < 50 || pos2.value > 850;
    const atRest = Math.abs(vel1.value) < 1e-9 && Math.abs(vel2.value) < 1e-9;
    if (outOfBounds || atRest) isRunning.value = false;

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

    // Track
    c.fillStyle = '#1e293b';
    c.fillRect(30, h / 2 - 30, w - 60, 60);

    // Ball 1
    const r1 = radius(mass1.value);
    c.beginPath();
    c.arc(pos1.value, h / 2, r1, 0, Math.PI * 2);
    c.fillStyle = '#3b82f6';
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 12px Inter';
    c.textAlign = 'center';
    c.fillText(`${mass1.value}kg`, pos1.value, h / 2 + 4);

    // Ball 2
    const r2 = radius(mass2.value);
    c.beginPath();
    c.arc(pos2.value, h / 2, r2, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();
    c.fillStyle = 'white';
    c.fillText(`${mass2.value}kg`, pos2.value, h / 2 + 4);

    // Velocity arrows
    if (isRunning.value || !hasCollided.value) {
        drawArrow(c, pos1.value, h / 2 - r1 - 20, vel1.value * 10, '#22c55e');
        drawArrow(c, pos2.value, h / 2 - r2 - 20, vel2.value * 10, '#22c55e');
    }

    // Info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 260, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    const p1 = momentum(mass1.value, vel1.value);
    const p2 = momentum(mass2.value, vel2.value);
    const ke = kineticEnergy(mass1.value, vel1.value) + kineticEnergy(mass2.value, vel2.value);

    c.fillText(`Jism 1: m=${mass1.value}kg, v=${vel1.value.toFixed(2)}m/s`, 35, 45);
    c.fillText(`Jism 2: m=${mass2.value}kg, v=${vel2.value.toFixed(2)}m/s`, 35, 65);
    c.fillStyle = '#10b981';
    c.fillText(`Boshlang'ich p: ${initialMomentum.value.toFixed(2)} kg·m/s`, 35, 90);
    c.fillText(`Joriy p: ${(p1 + p2).toFixed(2)} kg·m/s`, 35, 110);
    c.fillStyle = '#93c5fd';
    c.fillText(`Boshlang'ich Ek: ${initialKE.value.toFixed(2)} J`, 35, 130);
    c.fillText(`Joriy Ek: ${ke.toFixed(2)} J`, 35, 150);
    c.fillStyle = hasCollided.value ? '#f59e0b' : '#64748b';
    c.fillText(hasCollided.value ? '💥 To\'qnashuv bo\'ldi!' : 'Kutilmoqda...', 35, 175);
};

const drawArrow = (c, x, y, len, color) => {
    if (Math.abs(len) < 5) return;
    c.strokeStyle = color;
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(x, y);
    c.lineTo(x + len, y);
    c.stroke();

    c.fillStyle = color;
    c.beginPath();
    const dir = len > 0 ? 1 : -1;
    c.moveTo(x + len, y);
    c.lineTo(x + len - dir * 8, y - 5);
    c.lineTo(x + len - dir * 8, y + 5);
    c.closePath();
    c.fill();
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([mass1, mass2, v1, v2, elasticity], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Massa 1: <strong>{{ mass1 }} kg</strong></label>
                <input type="range" v-model.number="mass1" :min="1" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Tezlik 1: <strong>{{ v1 }} m/s</strong></label>
                <input type="range" v-model.number="v1" :min="0" :max="10" :step="1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Massa 2: <strong>{{ mass2 }} kg</strong></label>
                <input type="range" v-model.number="mass2" :min="1" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Tezlik 2: <strong>{{ v2 }} m/s</strong></label>
                <input type="range" v-model.number="v2" :min="-10" :max="0" :step="1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Elastiklik: <strong>{{ elasticity === 1 ? 'Elastik' : 'Noelastik' }}</strong></label>
                <input type="range" v-model.number="elasticity" :min="0" :max="1" :step="1" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
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
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 120px;
}

.control-group label {
    font-size: 0.75rem;
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
