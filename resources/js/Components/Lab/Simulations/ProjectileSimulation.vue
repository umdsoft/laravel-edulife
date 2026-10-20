<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    resolveGravity,
    toRad,
    frameDt,
    projectileState,
    projectileFlightTime,
    projectileRange,
    projectileMaxHeight,
    MECH_INSTRUMENTS,
} from '../physics/mechanics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Table-top ball launcher (spring "mini launcher", v₀ ≈ 2–6 m/s, ranges ≤ 4 m)
const velocity = ref(numberOr(props.config?.velocity?.default, 5)); // m/s
const angle = ref(numberOr(props.config?.angle?.default, 45)); // degrees above horizontal
const height = ref(numberOr(props.config?.height?.default, 0)); // launch height, m
const gravity = resolveGravity(props.config?.physics?.gravity);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

// Physical state in metres (origin at the launch point's foot, y up)
const posX = ref(0);
const posY = ref(0);
const time = ref(0);
const trajectory = ref([]); // [{ x, y }] in metres
const maxHeight = ref(0);
const range = ref(0); // measured range (tape measure reading), m
// The landing point is marked on carbon paper and measured with a steel tape (1 mm)
const tape = createInstrument(MECH_INSTRUMENTS.tapeMeasure, 'projectile');
const landed = ref(false);

const angleRad = computed(() => toRad(angle.value));
// Exact results including the launch height h0:
//   T = (v0 sinθ + √(v0² sin²θ + 2gh0))/g,  R = v0 cosθ·T,  H = h0 + (v0 sinθ)²/2g
const flightTime = computed(() => projectileFlightTime(velocity.value, angleRad.value, height.value, gravity));
const theoreticalRange = computed(() => projectileRange(velocity.value, angleRad.value, height.value, gravity));
const theoreticalMaxHeight = computed(() => projectileMaxHeight(velocity.value, angleRad.value, height.value, gravity));
const apexTime = computed(() => Math.max(0, (velocity.value * Math.sin(angleRad.value)) / gravity));

const canvasConfig = computed(() => ({ width: 900, height: 500 }));
const ORIGIN_X = 50; // px
const GROUND_H = 50; // px
// px per metre: 300 at most (table-top distances), reduced so the whole trajectory fits
const scale = computed(() => {
    const { width, height: h } = canvasConfig.value;
    const fitX = theoreticalRange.value > 0 ? (width - 2 * ORIGIN_X) / theoreticalRange.value : Infinity;
    const fitY = theoreticalMaxHeight.value > 0 ? (h - GROUND_H - 40) / theoreticalMaxHeight.value : Infinity;
    return Math.min(300, fitX, fitY);
});
const toScreenX = (x) => ORIGIN_X + x * scale.value;
const toScreenY = (y) => canvasConfig.value.height - GROUND_H - y * scale.value;

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
    posX.value = 0;
    posY.value = height.value;
    time.value = 0;
    trajectory.value = [{ x: 0, y: height.value }];
    maxHeight.value = height.value;
    range.value = 0;
    landed.value = false;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    posX.value = 0;
    posY.value = height.value;
    trajectory.value = [];
    maxHeight.value = 0;
    range.value = 0;
    landed.value = false;
    time.value = 0;
    draw();
};

const measure = () => {
    if (!landed.value) return;
    emit('measurement', { name: `Uzoqlik (v=${velocity.value}, θ=${angle.value}°)`, value: range.value.toFixed(3), unit: 'm' });
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

    // Closed-form kinematics: x = v0 cosθ·t, y = h0 + v0 sinθ·t − ½gt²
    const T = flightTime.value;
    const t = Math.min(time.value + dt, T);
    const s = projectileState(t, velocity.value, angleRad.value, height.value, gravity);
    time.value = t;
    posX.value = s.x;
    posY.value = Math.max(0, s.y);
    maxHeight.value = t >= apexTime.value ? theoreticalMaxHeight.value : Math.max(maxHeight.value, posY.value);
    trajectory.value.push({ x: posX.value, y: posY.value });

    if (t >= T) {
        posY.value = 0;
        posX.value = theoreticalRange.value;
        // Tape-measure reading of the landing mark (1 mm, seeded scatter)
        range.value = tape.read(theoreticalRange.value, [velocity.value, angle.value, height.value]);
        maxHeight.value = theoreticalMaxHeight.value;
        landed.value = true;
        isRunning.value = false;
    }

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

    // Grid every 0.5 m with distance labels on the ground (metres, not pixels)
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    const gridStep = scale.value >= 60 ? 0.5 : 1;
    c.font = '10px Inter';
    c.textAlign = 'center';
    for (let i = 0; toScreenX(i * gridStep) < w; i++) {
        const x = toScreenX(i * gridStep);
        c.beginPath(); c.moveTo(x, 0); c.lineTo(x, h - 50); c.stroke();
        c.fillStyle = '#94a3b8';
        c.fillText(`${(i * gridStep).toFixed(1)} m`, x, h - 35);
    }
    c.textAlign = 'left';

    // Launch platform (initial height h0)
    if (height.value > 0) {
        c.fillStyle = '#475569';
        c.fillRect(ORIGIN_X - 15, toScreenY(height.value), 15, height.value * scale.value);
    }

    // Trajectory
    if (trajectory.value.length > 1) {
        c.beginPath();
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 2;
        c.setLineDash([5, 3]);
        trajectory.value.forEach((p, i) => {
            if (i === 0) c.moveTo(toScreenX(p.x), toScreenY(p.y));
            else c.lineTo(toScreenX(p.x), toScreenY(p.y));
        });
        c.stroke();
        c.setLineDash([]);
    }

    // Projectile
    c.beginPath();
    c.arc(toScreenX(posX.value), toScreenY(posY.value), 10, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Initial velocity vector
    if (!isRunning.value && trajectory.value.length === 0) {
        const arrowLen = velocity.value * 15;
        const y0 = toScreenY(height.value);
        c.strokeStyle = '#22c55e';
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(ORIGIN_X, y0);
        c.lineTo(ORIGIN_X + arrowLen * Math.cos(angleRad.value), y0 - arrowLen * Math.sin(angleRad.value));
        c.stroke();
    }

    // Info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Tezlik: ${velocity.value} m/s`, 35, 45);
    c.fillText(`Burchak: ${angle.value}°`, 35, 65);
    c.fillText(`Boshlang'ich h: ${height.value} m`, 35, 85);
    c.fillStyle = '#10b981';
    c.fillText(`Masofa (ruletka): ${range.value.toFixed(3)} m`, 35, 110);
    c.fillText(`Max h: ${maxHeight.value.toFixed(3)} m`, 35, 130);
    c.fillStyle = '#3b82f6';
    c.fillText(`Nazariy R: ${theoreticalRange.value.toFixed(3)} m`, 35, 155);
    c.fillText(`Nazariy H: ${theoreticalMaxHeight.value.toFixed(3)} m`, 35, 175);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 195);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([velocity, angle, height], () => {
    if (!isRunning.value) {
        // New launch parameters: clear the previous flight so the drawing and the
        // measured range always belong to the current v, θ, h0
        reset();
    }
});
onMounted(() => { posY.value = height.value; initCanvas(); });
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tezlik: <strong>{{ velocity }} m/s</strong></label>
                <input type="range" v-model.number="velocity" :min="config?.velocity?.min ?? 1"
                    :max="config?.velocity?.max ?? 8" :step="config?.velocity?.step ?? 0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Burchak: <strong>{{ angle }}°</strong></label>
                <input type="range" v-model.number="angle" :min="config?.angle?.min ?? 0" :max="config?.angle?.max ?? 80"
                    :step="config?.angle?.step ?? 5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Balandlik: <strong>{{ height }} m</strong></label>
                <input type="range" v-model.number="height" :min="config?.height?.min ?? 0"
                    :max="config?.height?.max ?? 1.5" :step="config?.height?.step ?? 0.1" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🚀</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" :disabled="!landed" class="btn measure">📏</button>
            </div>
        </div>
        <div class="readout">
            Ruletka (1 mm): uchish uzoqligi L = {{ landed ? range.toFixed(3) + ' m' : '—' }}
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
