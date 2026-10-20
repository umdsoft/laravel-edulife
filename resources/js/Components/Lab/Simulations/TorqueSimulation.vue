<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    toRad,
    toDeg,
    frameDt,
    torque as torqueOf,
    leverArm as leverArmOf,
    rodInertia,
    rotationFromRest,
    MECH_INSTRUMENTS,
} from '../physics/mechanics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const rodLength = ref(2); // m
const force = ref(20); // N
const forceAngle = ref(90); // angle between the rod and the force, degrees
const pivotPos = ref(50); // % from left

// Model: uniform 1 kg rod rotating freely about the pivot in a horizontal plane
// (gravity does not produce a torque); the force stays attached to the rod end
// at a fixed angle, so τ and α are constant.
const ROD_MASS = 1; // kg
const MAX_ROTATION = Math.PI / 2;
const TARGET_DURATION = 3; // s of real time for the 90° turn (slow motion if faster)

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

// Measuring the torque like a real lab: the force is read on a force sensor
// (±50 N range, 0.05 N), the distance r from the axis to the point of application
// on the rod's metre scale (1 mm); M = F·r·sin θ (θ is set by the fixed angle
// bracket, not read). Each "O'lchash" is a new, reproducible reading.
const forceSensor = createInstrument(MECH_INSTRUMENTS.forceSensor, 'torque-force');
const rule = createInstrument(MECH_INSTRUMENTS.metreRule, 'torque-arm');
const lastReading = ref(null); // { F, r, M }

const rotation = ref(0); // rad
const angularVel = ref(0); // rad/s
const time = ref(0); // physical time, s

// r = distance from the pivot to the point of application (right end of the rod)
const momentArm = computed(() => rodLength.value * (1 - pivotPos.value / 100));
const angleRad = computed(() => toRad(forceAngle.value));
// τ = F·r·sin θ
const torque = computed(() => torqueOf(force.value, momentArm.value, angleRad.value));
// Lever arm d = r·sin θ (perpendicular distance from the axis to the line of action)
const leverArm = computed(() => leverArmOf(momentArm.value, angleRad.value));
// I = ML²/12 + M·d², d = distance from the rod's centre to the pivot (parallel-axis theorem)
const inertia = computed(() => rodInertia(ROD_MASS, rodLength.value, rodLength.value * (0.5 - pivotPos.value / 100)));
const angularAcc = computed(() => (inertia.value > 0 ? torque.value / inertia.value : 0)); // α = τ/I
// Physical time to turn 90° from rest: t = √(2φ/α)
const timeTo90 = computed(() => (angularAcc.value > 0 ? Math.sqrt((2 * MAX_ROTATION) / angularAcc.value) : Infinity));
// Playback rate (≤ 1): slow the animation down so the turn is visible; physics/time stay physical
const playbackRate = computed(() => (Number.isFinite(timeTo90.value) ? Math.min(1, timeTo90.value / TARGET_DURATION) : 1));

const canvasConfig = computed(() => ({ width: 800, height: 500 }));
const centerX = 400;
const centerY = 250;
const scale = 100;

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
    rotation.value = 0;
    angularVel.value = 0;
    time.value = 0;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    rotation.value = 0;
    angularVel.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    const F = forceSensor.read(force.value, [force.value, forceAngle.value, pivotPos.value]);
    const r = rule.read(momentArm.value, [pivotPos.value, rodLength.value]);
    const M = torqueOf(F, r, angleRad.value);
    lastReading.value = { F, r, M };
    emit('measurement', { name: 'Kuch (datchik)', value: F.toFixed(2), unit: 'N' });
    emit('measurement', { name: "O'qdan masofa (r)", value: r.toFixed(3), unit: 'm' });
    emit('measurement', { name: `Moment (F=${force.value}N, r=${momentArm.value.toFixed(2)}m)`, value: M.toFixed(3), unit: 'N·m' });
};

const animate = (ts) => {
    animationId.value = null;
    if (!isRunning.value || props.isPaused) {
        lastFrameTs = null;
        return;
    }
    const now = ts ?? performance.now();
    const dt = frameDt(lastFrameTs, now) * playbackRate.value;
    lastFrameTs = now;

    // Constant torque from rest: φ = ½αt², ω = αt
    let t = time.value + dt;
    if (t >= timeTo90.value) {
        t = timeTo90.value;
        isRunning.value = false;
    }
    const r = rotationFromRest(t, torque.value, inertia.value);
    time.value = t;
    rotation.value = isRunning.value ? r.angle : Math.sign(r.angle) * MAX_ROTATION;
    angularVel.value = r.omega;

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

    // Pivot point (fixed)
    const pivotX = centerX;
    const pivotY = centerY;

    c.save();
    c.translate(pivotX, pivotY);
    c.rotate(rotation.value);

    // Rod
    const leftEnd = -rodLength.value * scale * pivotPos.value / 100;
    const rightEnd = rodLength.value * scale * (1 - pivotPos.value / 100);

    c.fillStyle = '#64748b';
    c.fillRect(leftEnd, -10, rodLength.value * scale, 20);

    // Force arrow, applied exactly at the rod end (distance r from the pivot).
    // Direction makes the angle θ with the rod (+x in the rod frame), pointing
    // "down" so that the positive torque turns the rod clockwise on screen,
    // matching the positive canvas rotation used for the animation.
    const forceX = rightEnd;
    const forceY = 0;
    const fLen = force.value * 2;
    const fAngle = angleRad.value;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(forceX, forceY);
    c.lineTo(forceX + fLen * Math.cos(fAngle), forceY + fLen * Math.sin(fAngle));
    c.stroke();

    // Force label
    c.fillStyle = '#22c55e';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`F = ${force.value} N`, forceX + fLen / 2 * Math.cos(fAngle), forceY + fLen / 2 * Math.sin(fAngle) - 10);

    // Moment arm indicator
    c.setLineDash([5, 5]);
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(0, 0);
    c.lineTo(forceX, 0);
    c.stroke();
    c.setLineDash([]);

    c.restore();

    // Pivot point
    c.beginPath();
    c.arc(pivotX, pivotY, 12, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Rotation arc indicator: from the initial (horizontal) rod direction to the current one
    if (Math.abs(rotation.value) > 0.01) {
        c.beginPath();
        c.arc(pivotX, pivotY, 60, 0, rotation.value, rotation.value < 0);
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 3;
        c.stroke();
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 250, 235);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Kuch (F): ${force.value} N`, 35, 45);
    c.fillText(`Burchak (θ): ${forceAngle.value}°`, 35, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Masofa (r): ${momentArm.value.toFixed(2)} m`, 35, 90);
    c.fillText(`Yelka (d = r·sinθ): ${leverArm.value.toFixed(2)} m`, 35, 110);
    c.fillStyle = '#10b981';
    c.fillText(`Moment (τ): ${torque.value.toFixed(2)} N·m`, 35, 135);
    c.fillStyle = '#94a3b8';
    c.fillText(`Inersiya (I): ${inertia.value.toFixed(3)} kg·m²`, 35, 155);
    c.fillText(`Burchak tezlanish (ε = τ/I): ${angularAcc.value.toFixed(2)} rad/s²`, 35, 175);
    c.fillStyle = '#3b82f6';
    c.fillText(`Burilish: ${toDeg(rotation.value).toFixed(1)}°   t = ${time.value.toFixed(3)} s`, 35, 200);
    if (playbackRate.value < 1) {
        c.fillStyle = '#64748b';
        c.fillText(`(sekinlashtirilgan ×${playbackRate.value.toFixed(2)})`, 35, 218);
    }

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('τ = F × r × sin(θ) = F × d', 35, 243);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) startLoop(); });
watch([force, forceAngle, pivotPos, rodLength], () => {
    lastReading.value = null;
    if (!isRunning.value) draw();
});
onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Kuch: <strong>{{ force }} N</strong></label>
                <input type="range" v-model.number="force" :min="5" :max="50" :step="5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Burchak: <strong>{{ forceAngle }}°</strong></label>
                <input type="range" v-model.number="forceAngle" :min="30" :max="150" :step="15" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Pivot holati: <strong>{{ pivotPos }}%</strong></label>
                <input type="range" v-model.number="pivotPos" :min="20" :max="80" :step="10" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" class="btn measure">📏</button>
            </div>
        </div>
        <div class="readout">
            <template v-if="lastReading">
                Kuch datchigi: F = {{ lastReading.F.toFixed(2) }} N, chizg'ich: r = {{ lastReading.r.toFixed(3) }} m,
                M = F·r·sin θ = {{ lastReading.M.toFixed(3) }} N·m
            </template>
            <template v-else>"O'lchash": kuch datchigi (0,05 N) va chizg'ich (1 mm) ko'rsatkichlari.</template>
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
    min-width: 130px;
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
