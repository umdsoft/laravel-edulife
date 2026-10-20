<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    frameDt,
    momentum,
    kineticEnergy,
    collisionVelocities,
    collisionTime,
    gateTransitTime,
    speedFromGate,
    MECH_INSTRUMENTS,
} from '../physics/mechanics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Two carts on a 2 m air track (friction-free); masses 0.1–1 kg, speeds ≤ 1 m/s.
const mass1 = ref(numberOr(props.config?.mass1?.default, 0.4)); // kg
const mass2 = ref(numberOr(props.config?.mass2?.default, 0.2)); // kg
const v1 = ref(numberOr(props.config?.v1?.default, 0.5)); // m/s (right = +)
const v2 = ref(numberOr(props.config?.v2?.default, -0.3)); // m/s
const elasticity = ref(1); // coefficient of restitution: 1 = elastic (spring bumpers), 0 = perfectly inelastic (velcro)

// After the collision each cart's 10.0 cm flag passes a photogate; the 0.1 ms timer
// measures the transit time Δt and v = L/Δt (direction from the gate order).
const FLAG_LENGTH = 0.1; // m
const timer = createInstrument(MECH_INSTRUMENTS.timer, 'momentum');
const measured = ref(null); // { v1, v2, dt1, dt2, p } from the photogates

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

const PX_PER_M = 400; // the 2.0 m track spans x = 50 … 850 px
const X1_START = 150; // px (0.25 m)
const X2_START = 600; // px (1.375 m)
const radius = (m) => 18 + m * 15; // drawing radius, px

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
    measured.value = null;

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
    measured.value = null;
    draw();
};

/** Photogate speed of one cart: v = L/Δt with the seeded timer reading of Δt = L/|v|. */
const gateSpeed = (v, key) => {
    if (Math.abs(v) < 1e-12) return { v: 0, dt: Infinity };
    const dt = timer.read(gateTransitTime(FLAG_LENGTH, v), key);
    return { v: Math.sign(v) * speedFromGate(FLAG_LENGTH, dt), dt };
};

const readGates = () => {
    const key = [mass1.value, mass2.value, v1.value, v2.value, elasticity.value];
    const g1 = gateSpeed(afterCollision.v1, [...key, 1]);
    // Perfectly inelastic: the coupled carts pass the gate together (one reading)
    const g2 = elasticity.value === 0 ? g1 : gateSpeed(afterCollision.v2, [...key, 2]);
    measured.value = {
        v1: g1.v,
        v2: g2.v,
        dt1: g1.dt,
        dt2: g2.dt,
        p: momentum(mass1.value, g1.v) + momentum(mass2.value, g2.v),
    };
};

const measure = () => {
    if (!measured.value) return;
    const m = measured.value;
    emit('measurement', { name: "1-aravacha tezligi (to'qnashuvdan keyin)", value: m.v1.toFixed(4), unit: 'm/s' });
    emit('measurement', { name: "2-aravacha tezligi (to'qnashuvdan keyin)", value: m.v2.toFixed(4), unit: 'm/s' });
    emit('measurement', { name: 'Yakuniy impuls', value: m.p.toFixed(4), unit: 'kg·m/s' });
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
        if (!hasCollided.value) readGates();
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
        drawArrow(c, pos1.value, h / 2 - r1 - 20, vel1.value * 100, '#22c55e');
        drawArrow(c, pos2.value, h / 2 - r2 - 20, vel2.value * 100, '#22c55e');
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

    c.fillText(`Jism 1: m=${mass1.value}kg, v=${vel1.value.toFixed(3)}m/s`, 35, 45);
    c.fillText(`Jism 2: m=${mass2.value}kg, v=${vel2.value.toFixed(3)}m/s`, 35, 65);
    c.fillStyle = '#10b981';
    c.fillText(`Boshlang'ich p: ${initialMomentum.value.toFixed(4)} kg·m/s`, 35, 90);
    c.fillText(`Joriy p: ${(p1 + p2).toFixed(4)} kg·m/s`, 35, 110);
    c.fillStyle = '#93c5fd';
    c.fillText(`Boshlang'ich Ek: ${initialKE.value.toFixed(4)} J`, 35, 130);
    c.fillText(`Joriy Ek: ${ke.toFixed(4)} J`, 35, 150);
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
watch([mass1, mass2, v1, v2, elasticity], () => { if (!isRunning.value) reset(); });
onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Massa 1: <strong>{{ mass1 }} kg</strong></label>
                <input type="range" v-model.number="mass1" :min="0.1" :max="1" :step="0.1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Tezlik 1: <strong>{{ v1 }} m/s</strong></label>
                <input type="range" v-model.number="v1" :min="0" :max="1" :step="0.05" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Massa 2: <strong>{{ mass2 }} kg</strong></label>
                <input type="range" v-model.number="mass2" :min="0.1" :max="1" :step="0.1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Tezlik 2: <strong>{{ v2 }} m/s</strong></label>
                <input type="range" v-model.number="v2" :min="-1" :max="0" :step="0.05" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Elastiklik: <strong>{{ elasticity === 1 ? 'Elastik' : 'Noelastik' }}</strong></label>
                <input type="range" v-model.number="elasticity" :min="0" :max="1" :step="1" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" :disabled="!measured" class="btn measure">📏</button>
            </div>
        </div>
        <div class="readout">
            <template v-if="measured">
                Fotodarvozalar (bayroqcha 10,0 cm, taymer 0,1 ms):
                v₁' = {{ measured.v1.toFixed(4) }} m/s, v₂' = {{ measured.v2.toFixed(4) }} m/s,
                p' = m₁v₁' + m₂v₂' = {{ measured.p.toFixed(4) }} kg·m/s
            </template>
            <template v-else>Fotodarvozalar to'qnashuvdan keyingi tezliklarni o'lchaydi.</template>
        </div>
    </div>
</template>

<style scoped>
.readout {
    font-size: 0.8rem;
    color: #94a3b8;
    font-variant-numeric: tabular-nums;
}

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
