<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    frameDt,
    newtonAcceleration,
    uniformAccelState,
    timeToTravel,
    accelerationFromTiming,
    MECH_INSTRUMENTS,
} from '../physics/mechanics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Parameters (SI): mass kg, applied force N, friction force N
const mass = ref(numberOr(props.config?.mass?.default, 2));
const force = ref(numberOr(props.config?.force?.default, 10));
const friction = ref(numberOr(props.config?.friction?.default, 0));

// State
const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

// Apparatus (table-top track): the block starts at rest at x = 0, a photogate at
// x = GATE_DISTANCE = 1.000 m stops the electronic timer (started at the release);
// the track ends at 1.2 m. a = 2s/t² from the timer reading.
const GATE_DISTANCE = 1.0; // m
const TRACK_LENGTH = 1.2; // m
const START_PX = 50; // block's left edge at x = 0 m
const timer = createInstrument(MECH_INSTRUMENTS.timer, 'newton-second');
const gateTime = ref(0); // timer reading, s (0 = gate not reached yet)
const measuredAcceleration = ref(0); // 2s/t², m/s²
const started = ref(false);

// Physics
const position = ref(START_PX); // px
const velocity = ref(0); // m/s
const acceleration = ref(0); // m/s²
const time = ref(0); // s

// Computed: a = (F − f)/m if F > f, otherwise the block stays at rest (static friction)
const theoreticalAcceleration = computed(() => newtonAcceleration(force.value, mass.value, friction.value));
const canvasConfig = computed(() => props.config?.canvas || { width: 900, height: 500 });
// px per metre so that the 1.2 m track fills the canvas
const PX_PER_M = computed(() => Math.max(1, (canvasConfig.value.width - 80 - START_PX) / TRACK_LENGTH));
const trackLength = computed(() => TRACK_LENGTH);
// The block stays at rest when F ≤ f (static friction)
const stuck = computed(() => started.value && theoreticalAcceleration.value === 0);
const canMeasure = computed(() => gateTime.value > 0 || stuck.value);

// Methods
const initCanvas = () => {
    if (!canvas.value) return;
    ctx.value = canvas.value.getContext('2d');
    draw();
};

const stopLoop = () => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    animationId.value = null;
    lastFrameTs = null;
};

const startLoop = () => {
    stopLoop();
    animationId.value = requestAnimationFrame(animate);
};

const clearRun = () => {
    position.value = START_PX;
    velocity.value = 0;
    time.value = 0;
    gateTime.value = 0;
    measuredAcceleration.value = 0;
};

const start = () => {
    if (isRunning.value) return;
    clearRun();
    started.value = true;
    acceleration.value = theoreticalAcceleration.value;
    isRunning.value = acceleration.value > 0; // F ≤ f: nothing moves
    if (isRunning.value) startLoop();
    else draw();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();
    clearRun();
    started.value = false;
    draw();
};

const measure = () => {
    if (!canMeasure.value) return;
    emit('measurement', {
        name: `Tezlanish (F=${force.value}N, m=${mass.value}kg)`,
        value: (stuck.value ? 0 : measuredAcceleration.value).toFixed(3),
        unit: 'm/s²',
    });
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

    // Constant acceleration from rest: x = ½at², v = at
    const a = theoreticalAcceleration.value;
    acceleration.value = a;
    let t = time.value + dt;
    let { x, v } = uniformAccelState(t, a);

    // Photogate at s = 1 m: timer reading → a = 2s/t²
    if (gateTime.value === 0 && x >= GATE_DISTANCE) {
        gateTime.value = timer.read(timeToTravel(GATE_DISTANCE, a), [force.value, mass.value, friction.value]);
        measuredAcceleration.value = accelerationFromTiming(GATE_DISTANCE, gateTime.value);
    }

    if (x >= trackLength.value) {
        // Stop exactly at the end of the track
        t = timeToTravel(trackLength.value, a);
        x = trackLength.value;
        v = a * t;
        isRunning.value = false;
    }

    time.value = t;
    velocity.value = v;
    position.value = START_PX + x * PX_PER_M.value;

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
    c.fillRect(0, h - 60, w, 60);

    // Grid lines
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let x = 0; x < w; x += 50) {
        c.beginPath();
        c.moveTo(x, 0);
        c.lineTo(x, h - 60);
        c.stroke();
    }

    // Photogate at s = 1 m (measured from the block's front edge start position)
    const gateX = START_PX + 60 + GATE_DISTANCE * PX_PER_M.value;
    c.fillStyle = '#f59e0b';
    c.fillRect(gateX - 3, h - 60 - 110, 6, 110);
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('fotodarvoza (1,00 m)', gateX, h - 60 - 118);

    // Block
    const blockSize = 60;
    const blockX = position.value;
    const blockY = h - 60 - blockSize;

    c.fillStyle = '#3b82f6';
    c.fillRect(blockX, blockY, blockSize, blockSize);
    c.strokeStyle = '#60a5fa';
    c.lineWidth = 2;
    c.strokeRect(blockX, blockY, blockSize, blockSize);

    // Mass label on block
    c.fillStyle = 'white';
    c.font = 'bold 14px Inter';
    c.textAlign = 'center';
    c.fillText(`${mass.value} kg`, blockX + blockSize / 2, blockY + blockSize / 2 + 5);

    // Force arrow
    if (force.value > 0) {
        const arrowLen = force.value * 4;
        c.strokeStyle = '#22c55e';
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(blockX + blockSize + 10, blockY + blockSize / 2);
        c.lineTo(blockX + blockSize + 10 + arrowLen, blockY + blockSize / 2);
        c.stroke();

        // Arrow head
        c.fillStyle = '#22c55e';
        c.beginPath();
        c.moveTo(blockX + blockSize + 10 + arrowLen, blockY + blockSize / 2);
        c.lineTo(blockX + blockSize + arrowLen, blockY + blockSize / 2 - 8);
        c.lineTo(blockX + blockSize + arrowLen, blockY + blockSize / 2 + 8);
        c.closePath();
        c.fill();

        c.fillStyle = '#22c55e';
        c.font = '12px Inter';
        c.fillText(`F = ${force.value} N`, blockX + blockSize + 10 + arrowLen / 2, blockY + blockSize / 2 - 15);
    }

    // Measurements
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 140);
    c.strokeStyle = '#334155';
    c.strokeRect(20, 20, 200, 140);

    c.textAlign = 'left';
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.fillText(`Massa (m): ${mass.value} kg`, 35, 45);
    c.fillText(`Kuch (F): ${force.value} N`, 35, 65);
    c.fillText(`Ishqalanish: ${friction.value} N`, 35, 85);

    c.fillStyle = '#10b981';
    c.fillText(`t (1,00 m): ${gateTime.value > 0 ? gateTime.value.toFixed(4) + ' s' : '—'}`, 35, 103);
    c.fillText(`a = 2s/t²: ${stuck.value ? "0 (qo'zg'almadi)" : measuredAcceleration.value.toFixed(3) + ' m/s²'}`, 35, 117);
    c.fillStyle = '#3b82f6';
    c.fillText(`Tezlik (v): ${velocity.value.toFixed(2)} m/s`, 35, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 150);

    // Formula
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 180, 20, 160, 50);
    c.fillStyle = '#94a3b8';
    c.font = '16px serif';
    c.textAlign = 'center';
    c.fillText('F = m × a', w - 100, 52);
};

watch(() => props.isPaused, (paused) => {
    if (!paused && isRunning.value) startLoop();
});

watch([mass, force, friction], () => {
    if (!isRunning.value) {
        clearRun();
        started.value = false;
        draw();
    }
    // Keep the rest of the shared state (e.g. the student's conclusion) intact
    emit('update:state', { ...props.state, mass: mass.value, force: force.value, friction: friction.value });
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
                <input type="range" v-model.number="mass" :min="config?.mass?.min ?? 0.5" :max="config?.mass?.max ?? 5"
                    :step="config?.mass?.step ?? 0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Kuch: <strong>{{ force }} N</strong></label>
                <input type="range" v-model.number="force" :min="config?.force?.min ?? 1" :max="config?.force?.max ?? 20"
                    :step="config?.force?.step ?? 1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Ishqalanish: <strong>{{ friction }} N</strong></label>
                <input type="range" v-model.number="friction" :min="config?.friction?.min ?? 0"
                    :max="config?.friction?.max ?? 10" :step="config?.friction?.step ?? 0.5" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Boshlash</button>
                <button @click="reset" class="btn reset">🔄 Qayta</button>
                <button @click="measure" :disabled="!canMeasure" class="btn measure">📏
                    O'lchash</button>
            </div>
        </div>
        <div class="readout">
            Fotodarvoza taymeri (s = 1,00 m): t = {{ gateTime > 0 ? gateTime.toFixed(4) + ' s' : '—' }},
            a = 2s/t² = {{ stuck ? '0' : measuredAcceleration.toFixed(3) }} m/s²
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 150px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.control-group input {
    width: 100%;
}

.buttons {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

.btn {
    padding: 0.625rem 1rem;
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
