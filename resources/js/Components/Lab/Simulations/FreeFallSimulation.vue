<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    resolveGravity,
    frameDt,
    freeFallState,
    freeFallTime,
    gravityFromFall,
} from '../physics/mechanics.js';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Parameters (SI)
const height = ref(numberOr(props.config?.height?.default, 5));
const gravity = ref(resolveGravity(props.config?.physics?.gravity));

// Animation state
const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

// Physics state
const y = ref(0);
const velocity = ref(0);
const time = ref(0);
const isFallen = ref(false);

// Measurements
const fallTime = ref(0);
const finalVelocity = ref(0);
const measuredG = ref(0);

// Canvas config
const canvasConfig = computed(() => props.config?.canvas || { width: 800, height: 700 });
// px per metre: the maximum height must fit between y = 100 px and the ground (h − 50 px, minus ball radius)
const scale = computed(() => (canvasConfig.value.height - 170) / (numberOr(props.config?.height?.max, 10) || 10));

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

const start = () => {
    if (isRunning.value || isFallen.value) return;

    y.value = 0;
    velocity.value = 0;
    time.value = 0;
    isRunning.value = true;
    startLoop();
};

const reset = () => {
    isRunning.value = false;
    stopLoop();

    y.value = 0;
    velocity.value = 0;
    time.value = 0;
    isFallen.value = false;
    fallTime.value = 0;
    finalVelocity.value = 0;
    measuredG.value = 0;
    draw();
};

const measure = () => {
    if (fallTime.value > 0) {
        emit('measurement', {
            name: `Tushish vaqti (h=${height.value}m)`,
            value: fallTime.value.toFixed(3),
            unit: 's',
        });
    }
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

    // Closed-form free fall: s = ½gt², v = gt (clamped at the ground, t = √(2h/g))
    const s = freeFallState(time.value + dt, height.value, gravity.value);
    time.value = s.t;
    y.value = s.y;
    velocity.value = s.v;

    if (s.landed) {
        isFallen.value = true;
        isRunning.value = false;
        fallTime.value = s.t;
        finalVelocity.value = s.v;
        measuredG.value = gravityFromFall(height.value, s.t); // g = 2h/t²
    }

    draw();

    if (isRunning.value) {
        animationId.value = requestAnimationFrame(animate);
    }
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    // Clear
    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Release point is placed `height` metres above the ground surface so the
    // ball lands exactly on the drawn ground after falling h.
    const ballRadius = numberOr(props.config?.object?.radius, 15);
    const groundY = h - 50;
    const releaseY = groundY - ballRadius - height.value * scale.value;

    // Draw height ruler
    drawRuler(c, releaseY);

    // Draw ball
    const ballX = w / 2;
    const ballY = releaseY + y.value * scale.value;

    // Ball shadow
    if (!isFallen.value) {
        c.beginPath();
        c.ellipse(ballX, groundY, ballRadius * 0.5 + y.value * 2, 5, 0, 0, Math.PI * 2);
        c.fillStyle = 'rgba(0, 0, 0, 0.3)';
        c.fill();
    }

    // Ball
    c.beginPath();
    c.arc(ballX, ballY, ballRadius, 0, Math.PI * 2);
    const gradient = c.createRadialGradient(
        ballX - 5, ballY - 5, 0,
        ballX, ballY, ballRadius
    );
    gradient.addColorStop(0, '#60a5fa');
    gradient.addColorStop(1, '#3b82f6');
    c.fillStyle = gradient;
    c.fill();

    // Ground
    c.fillStyle = '#334155';
    c.fillRect(0, h - 50, w, 50);

    // Draw measurements
    drawMeasurements(c);
};

const drawRuler = (c, startY) => {
    const x = 100;
    const endY = startY + height.value * scale.value;

    // Main line
    c.strokeStyle = '#64748b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(x, startY);
    c.lineTo(x, endY);
    c.stroke();

    // Markers
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter, sans-serif';

    for (let i = 0; i <= height.value; i++) {
        const markerY = startY + i * scale.value;

        c.beginPath();
        c.moveTo(x - 10, markerY);
        c.lineTo(x, markerY);
        c.stroke();

        c.fillText(`${i} m`, x - 40, markerY + 4);
    }

    // Current height marker
    if (isRunning.value || isFallen.value) {
        const currentY = startY + y.value * scale.value;
        c.fillStyle = '#ef4444';
        c.beginPath();
        c.moveTo(x + 5, currentY);
        c.lineTo(x + 20, currentY - 5);
        c.lineTo(x + 20, currentY + 5);
        c.closePath();
        c.fill();

        c.fillStyle = '#ef4444';
        c.fillText(`${y.value.toFixed(2)} m`, x + 25, currentY + 4);
    }
};

const drawMeasurements = (c) => {
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(canvasConfig.value.width - 220, 20, 200, 160);
    c.strokeStyle = '#334155';
    c.strokeRect(canvasConfig.value.width - 220, 20, 200, 160);

    c.fillStyle = '#ffffff';
    c.font = '12px Inter, sans-serif';

    const x = canvasConfig.value.width - 205;
    c.fillText(`Balandlik (h): ${height.value.toFixed(1)} m`, x, 45);
    c.fillText(`Vaqt (t): ${time.value.toFixed(3)} s`, x, 70);
    c.fillText(`Tezlik (v): ${velocity.value.toFixed(2)} m/s`, x, 95);

    c.fillStyle = '#10b981';
    c.fillText(`Tushish vaqti: ${fallTime.value.toFixed(3)} s`, x, 125);

    c.fillStyle = '#3b82f6';
    const theoreticalT = freeFallTime(height.value, gravity.value);
    c.fillText(`Nazariy t: ${theoreticalT.toFixed(3)} s`, x, 150);

    if (measuredG.value > 0) {
        c.fillStyle = '#f59e0b';
        c.fillText(`Hisoblangan g: ${measuredG.value.toFixed(2)} m/s²`, x, 175);
    }
};

// Watchers
watch(() => props.isPaused, (paused) => {
    if (!paused && isRunning.value) startLoop();
});

watch(height, () => {
    reset();
    emit('update:state', { ...props.state, height: height.value });
});

onMounted(() => initCanvas());
onUnmounted(() => stopLoop());
</script>

<template>
    <div class="free-fall-simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="simulation-canvas" />

        <div class="controls-panel">
            <div class="control-group">
                <label class="control-label">
                    Balandlik: <strong>{{ height.toFixed(1) }} m</strong>
                </label>
                <input type="range" v-model.number="height" :min="config?.height?.min || 1"
                    :max="config?.height?.max || 10" :step="config?.height?.step || 0.5" :disabled="isRunning"
                    class="range-slider" />
            </div>

            <div class="button-group">
                <button @click="start" :disabled="isRunning || isFallen" class="btn btn-start">
                    🔽 Tushirish
                </button>
                <button @click="reset" class="btn btn-reset">
                    🔄 Qayta
                </button>
                <button @click="measure" :disabled="!isFallen" class="btn btn-measure">
                    📏 O'lchash
                </button>
            </div>
        </div>

        <!-- Formulas hint -->
        <div class="formulas-hint">
            <div class="formula">h = ½gt²</div>
            <div class="formula">v = gt</div>
            <div class="formula">g = 2h/t²</div>
        </div>
    </div>
</template>

<style scoped>
.free-fall-simulation {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.simulation-canvas {
    border-radius: 12px;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
}

.controls-panel {
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 180px;
}

.control-label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-label strong {
    color: white;
}

.range-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    appearance: none;
    background: #334155;
    cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #3b82f6;
}

.range-slider:disabled {
    opacity: 0.5;
}

.button-group {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-start {
    background: #10b981;
    color: white;
}

.btn-reset {
    background: #475569;
    color: white;
}

.btn-measure {
    background: #3b82f6;
    color: white;
}

.formulas-hint {
    display: flex;
    gap: 2rem;
    padding: 0.75rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}

.formula {
    font-family: 'Times New Roman', serif;
    font-size: 1rem;
    color: #94a3b8;
}
</style>
