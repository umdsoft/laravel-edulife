<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    resolveGravity,
    toRad,
    frameDt,
    inclineAcceleration,
    inclineForces,
    uniformAccelState,
    timeToTravel,
} from '../physics/mechanics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const angle = ref(numberOr(props.config?.angle?.default, 30));
const mass = ref(numberOr(props.config?.mass?.default, 1));
const friction = ref(numberOr(props.config?.friction?.default, 0.1)); // μ (μ_static = μ_kinetic)
const gravity = resolveGravity(props.config?.physics?.gravity);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

const PX_PER_M = 50;
const BLOCK_SIZE = 40; // px

const position = ref(0); // distance slid down the slope, m
const velocity = ref(0); // m/s
const time = ref(0); // s

const angleRad = computed(() => toRad(angle.value));
// a = g(sin θ − μ cos θ) when tan θ > μ, otherwise the block stays at rest
const acceleration = computed(() => inclineAcceleration(angleRad.value, friction.value, gravity));
const forces = computed(() => inclineForces(mass.value, angleRad.value, friction.value, gravity));

const canvasConfig = computed(() => props.config?.canvas || { width: 900, height: 600 });
const planeLength = computed(() => 400); // px
// Distance the block's centre can travel along the slope (m)
const travelLength = computed(() => (planeLength.value - BLOCK_SIZE) / PX_PER_M);

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
    position.value = 0;
    velocity.value = 0;
    time.value = 0;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    position.value = 0;
    velocity.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Tezlanish (θ=${angle.value}°)`, value: acceleration.value.toFixed(3), unit: 'm/s²' });
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

    // From rest: s = ½at², v = at
    const a = acceleration.value;
    let t = time.value + dt;
    let { x, v } = uniformAccelState(t, a);
    if (x >= travelLength.value) {
        t = timeToTravel(travelLength.value, a);
        x = travelLength.value;
        v = a * t; // = √(2aL)
        isRunning.value = false;
    }
    time.value = t;
    position.value = x;
    velocity.value = v;

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

    // Inclined plane
    const startX = 100, startY = h - 100;
    const endX = startX + planeLength.value * Math.cos(angleRad.value);
    const endY = startY - planeLength.value * Math.sin(angleRad.value);

    c.strokeStyle = '#64748b';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(startX, startY);
    c.lineTo(endX, endY);
    c.stroke();

    // Ground
    c.fillStyle = '#334155';
    c.fillRect(0, startY, w, 100);

    // Block on plane: starts at the TOP and slides DOWN the slope.
    // Contact point P = top − s·(cos θ, −sin θ); centre = P + (size/2)·n, n = (−sin θ, −cos θ)
    const blockSize = BLOCK_SIZE;
    const sPx = blockSize / 2 + position.value * PX_PER_M;
    const px = endX - sPx * Math.cos(angleRad.value);
    const py = endY + sPx * Math.sin(angleRad.value);
    const cx = px - (blockSize / 2) * Math.sin(angleRad.value);
    const cy = py - (blockSize / 2) * Math.cos(angleRad.value);

    c.save();
    c.translate(cx, cy);
    c.rotate(-angleRad.value);
    c.fillStyle = '#ef4444';
    c.fillRect(-blockSize / 2, -blockSize / 2, blockSize, blockSize);
    c.fillStyle = 'white';
    c.font = 'bold 11px Inter';
    c.textAlign = 'center';
    c.fillText(`${mass.value}kg`, 0, 4);
    c.restore();

    // Angle arc: between the ground (0 rad) and the slope (−θ in canvas coordinates)
    c.beginPath();
    c.arc(startX, startY, 50, -angleRad.value, 0, false);
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.stroke();
    c.fillStyle = '#f59e0b';
    c.font = '14px Inter';
    c.fillText(`${angle.value}°`, startX + 60, startY - 20);

    // Info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Burchak: ${angle.value}°`, 35, 45);
    c.fillText(`Massa: ${mass.value} kg`, 35, 65);
    c.fillText(`Ishqalanish: ${friction.value}`, 35, 85);
    c.fillStyle = '#94a3b8';
    c.fillText(`N = mg·cosθ: ${forces.value.normal.toFixed(2)} N`, 35, 105);
    c.fillText(`f (ishqalanish kuchi): ${forces.value.friction.toFixed(2)} N`, 35, 125);
    c.fillStyle = '#10b981';
    c.fillText(`Tezlanish: ${acceleration.value.toFixed(3)} m/s²`, 35, 150);
    c.fillStyle = '#3b82f6';
    c.fillText(`Tezlik: ${velocity.value.toFixed(2)} m/s`, 35, 170);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 190);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([angle, mass, friction], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Burchak: <strong>{{ angle }}°</strong></label>
                <input type="range" v-model.number="angle" :min="10" :max="60" :step="5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.5" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Ishqalanish (μ): <strong>{{ friction }}</strong></label>
                <input type="range" v-model.number="friction" :min="0" :max="0.5" :step="0.05" :disabled="isRunning" />
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
