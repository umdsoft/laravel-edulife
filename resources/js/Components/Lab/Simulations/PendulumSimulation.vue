<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    numberOr,
    resolveGravity,
    toRad,
    toDeg,
    frameDt,
    pendulumAdvance,
    pendulumEnergy,
    pendulumSmallAnglePeriod,
    pendulumExactPeriod,
    periodFromTiming,
    MECH_INSTRUMENTS,
    TIMED_PERIODS,
} from '../physics/mechanics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Simulation parameters (SI units; angle slider in degrees)
const length = ref(numberOr(props.config?.pendulum?.length?.default, 1.0));
const mass = ref(numberOr(props.config?.pendulum?.mass?.default, 0.5));
const angle = ref(numberOr(props.config?.pendulum?.initialAngle?.default, 15));
const gravity = ref(resolveGravity(props.config?.physics?.gravity));
// Linear damping coefficient b (1/s). Default 0 → ideal pendulum, energy conserved.
const damping = computed(() => Math.max(0, numberOr(props.config?.physics?.damping, 0)));

// Animation state
const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
let lastFrameTs = null;

// Physics state
const theta = ref(0);
const omega = ref(0); // Angular velocity
const time = ref(0);

// Measurements: a photogate at the lowest point starts the electronic timer
// (0.1 ms) at an upward passage and stops it after N = 10 full oscillations,
// like the real lab procedure "time 10 oscillations, T = t₁₀ / 10".
const timer = createInstrument(MECH_INSTRUMENTS.timer, 'pendulum');
const period = ref(0); // T = t_N / N from the timer reading
const frequency = ref(0);
const firstCrossing = ref(null); // timer start (exact model instant)
const countedPeriods = ref(0); // N counted by the photogate (≤ 10)
const timerReading = ref(0); // displayed t_N, s

// Energy
const kineticEnergy = ref(0);
const potentialEnergy = ref(0);
const totalEnergy = ref(0);

// Graph data
const angleHistory = ref([]);
const energyHistory = ref([]);

// Computed
const pendulumConfig = computed(() => props.config?.pendulum || {});
const canvasConfig = computed(() => props.config?.canvas || { width: 800, height: 600 });

// Exact period of the nonlinear pendulum: T = 4√(L/g)·K(sin(θ₀/2))
const theoreticalPeriod = computed(() => pendulumExactPeriod(length.value, gravity.value, toRad(angle.value)));
// Small-angle (harmonic) approximation: T₀ = 2π√(L/g)
const smallAnglePeriod = computed(() => pendulumSmallAnglePeriod(length.value, gravity.value));

// Scale for visualization
const scale = computed(() => {
    return 200; // pixels per meter
});

const pivotX = computed(() => canvasConfig.value.width / 2);
const pivotY = computed(() => 100);

const bobX = computed(() => {
    return pivotX.value + length.value * scale.value * Math.sin(theta.value);
});

const bobY = computed(() => {
    return pivotY.value + length.value * scale.value * Math.cos(theta.value);
});

// Methods
const initCanvas = () => {
    if (!canvas.value) return;
    ctx.value = canvas.value.getContext('2d');
    draw();
};

const updateEnergy = () => {
    const e = pendulumEnergy(mass.value, length.value, gravity.value, theta.value, omega.value);
    kineticEnergy.value = e.ke;
    potentialEnergy.value = e.pe;
    totalEnergy.value = e.total;
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

const resetPhysics = () => {
    theta.value = toRad(angle.value);
    omega.value = 0;
    time.value = 0;
    period.value = 0;
    frequency.value = 0;
    firstCrossing.value = null;
    countedPeriods.value = 0;
    timerReading.value = 0;
    angleHistory.value = [];
    energyHistory.value = [];
    updateEnergy();
};

const start = () => {
    if (isRunning.value) return;
    resetPhysics();
    isRunning.value = true;
    startLoop();
};

const pause = () => {
    isRunning.value = false;
    stopLoop();
};

const reset = () => {
    pause();
    resetPhysics();
    draw();
};

// T = t_N / N shown with 5 decimals: t₁₀ has 0.1 ms resolution, so t₁₀/10 is exact to 0.01 ms.
const PERIOD_DECIMALS = 5;

const measure = () => {
    if (period.value > 0) {
        emit('measurement', {
            name: 'Tebranish davri (T)',
            value: period.value.toFixed(PERIOD_DECIMALS),
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

    // Advance by the real elapsed time (frame-rate independent).
    const now = ts ?? performance.now();
    const dt = frameDt(lastFrameTs, now);
    lastFrameTs = now;

    // Nonlinear pendulum θ'' = −(g/L)·sin θ − b·θ', integrated with RK4 sub-steps.
    const next = pendulumAdvance(
        { theta: theta.value, omega: omega.value, t: time.value },
        dt,
        { L: length.value, g: gravity.value, damping: damping.value },
    );
    theta.value = next.theta;
    omega.value = next.omega;
    time.value = next.t;

    // Photogate: upward passages through θ = 0 (θ: − → +). The first one starts the
    // timer; every further one is one more full period, up to N = 10.
    for (const tc of next.crossings) {
        if (firstCrossing.value === null) {
            firstCrossing.value = tc;
        } else if (countedPeriods.value < TIMED_PERIODS) {
            countedPeriods.value++;
            const n = countedPeriods.value;
            // Timer reading of t_N (seeded scatter, 0.1 ms display); key = set-up + N
            timerReading.value = timer.read(tc - firstCrossing.value, [length.value, angle.value, mass.value, damping.value, n]);
            period.value = periodFromTiming(timerReading.value, n);
            frequency.value = period.value > 0 ? 1 / period.value : 0;
        }
    }

    // Energies: KE = ½m(Lω)², PE = mgL(1 − cos θ)
    updateEnergy();

    // Record history for graphs
    if (angleHistory.value.length > 500) {
        angleHistory.value.shift();
        energyHistory.value.shift();
    }

    angleHistory.value.push({
        t: time.value,
        theta: toDeg(theta.value),
    });

    energyHistory.value.push({
        t: time.value,
        ke: kineticEnergy.value,
        pe: potentialEnergy.value,
        total: totalEnergy.value,
    });

    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;

    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    // Clear
    c.fillStyle = canvasConfig.value.background || '#1a1a2e';
    c.fillRect(0, 0, w, h);

    // Draw pivot
    c.beginPath();
    c.arc(pivotX.value, pivotY.value, 8, 0, Math.PI * 2);
    c.fillStyle = '#64748b';
    c.fill();

    // Draw string
    c.beginPath();
    c.moveTo(pivotX.value, pivotY.value);
    c.lineTo(bobX.value, bobY.value);
    c.strokeStyle = pendulumConfig.value.stringColor || '#ffffff';
    c.lineWidth = 2;
    c.stroke();

    // Draw bob
    const bobRadius = pendulumConfig.value.bobRadius || 20;
    c.beginPath();
    c.arc(bobX.value, bobY.value, bobRadius, 0, Math.PI * 2);

    // Gradient for bob
    const gradient = c.createRadialGradient(
        bobX.value - 5, bobY.value - 5, 0,
        bobX.value, bobY.value, bobRadius
    );
    gradient.addColorStop(0, '#f87171');
    gradient.addColorStop(1, pendulumConfig.value.bobColor || '#ef4444');
    c.fillStyle = gradient;
    c.fill();

    // Draw angle arc
    if (isRunning.value) {
        c.beginPath();
        c.arc(pivotX.value, pivotY.value, 50, Math.PI / 2, Math.PI / 2 - theta.value, theta.value > 0);
        c.strokeStyle = 'rgba(59, 130, 246, 0.5)';
        c.lineWidth = 2;
        c.stroke();
    }

    // Draw measurements display
    drawMeasurements(c);

    // Draw mini graph
    drawMiniGraph(c);
};

const drawMeasurements = (c) => {
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(20, 20, 300, 165);
    c.strokeStyle = '#334155';
    c.strokeRect(20, 20, 300, 165);

    c.fillStyle = '#ffffff';
    c.font = '12px Inter, sans-serif';

    c.fillText(`Ip uzunligi: ${length.value.toFixed(2)} m`, 35, 45);
    c.fillText(`Massa: ${mass.value.toFixed(2)} kg`, 35, 65);
    c.fillText(`Burchak: ${toDeg(theta.value).toFixed(1)}°`, 35, 85);

    c.fillStyle = '#10b981';
    c.fillText(`Davr (T): ${period.value.toFixed(PERIOD_DECIMALS)} s  (N = ${countedPeriods.value}, t = ${timerReading.value.toFixed(4)} s)`, 35, 108);
    c.fillStyle = '#3b82f6';
    c.fillText(`Nazariy T (aniq): ${theoreticalPeriod.value.toFixed(3)} s`, 35, 127);
    c.fillStyle = '#93c5fd';
    c.fillText(`T₀ = 2π√(L/g): ${smallAnglePeriod.value.toFixed(3)} s`, 35, 146);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s`, 35, 170);
};

const drawMiniGraph = (c) => {
    if (angleHistory.value.length < 2) return;

    const graphX = 20;
    const graphY = canvasConfig.value.height - 120;
    const graphW = 200;
    const graphH = 100;

    // Background
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(graphX, graphY, graphW, graphH);
    c.strokeStyle = '#334155';
    c.strokeRect(graphX, graphY, graphW, graphH);

    // Title
    c.fillStyle = '#94a3b8';
    c.font = '10px Inter, sans-serif';
    c.fillText('θ - t grafik', graphX + 5, graphY + 12);

    // Axis
    c.strokeStyle = '#475569';
    c.beginPath();
    c.moveTo(graphX + 25, graphY + graphH - 15);
    c.lineTo(graphX + graphW - 10, graphY + graphH - 15);
    c.moveTo(graphX + 25, graphY + 15);
    c.lineTo(graphX + 25, graphY + graphH - 15);
    c.stroke();

    // Plot angle
    const data = angleHistory.value.slice(-100);
    if (data.length < 2) return;

    const minT = data[0].t;
    const maxT = data[data.length - 1].t;
    const range = maxT - minT || 1;

    c.beginPath();
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 1.5;

    data.forEach((point, i) => {
        const x = graphX + 25 + ((point.t - minT) / range) * (graphW - 40);
        const y = graphY + graphH / 2 - (point.theta / (angle.value || 1)) * (graphH / 2 - 20);

        if (i === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    });

    c.stroke();
};

// Watchers
watch(() => props.isPaused, (paused) => {
    if (!paused && isRunning.value) {
        startLoop();
    }
});

watch([length, mass, angle], () => {
    if (!isRunning.value) {
        // New set-up: start position θ₀ and a cleared timer (the old T belongs to the old set-up)
        resetPhysics();
        draw();
    }

    // Update state
    emit('update:state', {
        ...props.state,
        length: length.value,
        mass: mass.value,
        angle: angle.value,
    });
});

// Lifecycle
onMounted(() => {
    resetPhysics();
    initCanvas();
});

onUnmounted(() => {
    pause();
});
</script>

<template>
    <div class="pendulum-simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="simulation-canvas" />

        <!-- Controls -->
        <div class="controls-panel">
            <div class="control-group">
                <label class="control-label">
                    Ip uzunligi: <strong>{{ length.toFixed(2) }} m</strong>
                </label>
                <input type="range" v-model.number="length" :min="pendulumConfig.length?.min || 0.3"
                    :max="pendulumConfig.length?.max || 1.5" :step="pendulumConfig.length?.step || 0.1"
                    :disabled="isRunning" class="range-slider" />
            </div>

            <div class="control-group">
                <label class="control-label">
                    Massa: <strong>{{ mass.toFixed(2) }} kg</strong>
                </label>
                <input type="range" v-model.number="mass" :min="pendulumConfig.mass?.min || 0.1"
                    :max="pendulumConfig.mass?.max || 2.0" :step="pendulumConfig.mass?.step || 0.1"
                    :disabled="isRunning" class="range-slider" />
            </div>

            <div class="control-group">
                <label class="control-label">
                    Boshlang'ich burchak: <strong>{{ angle }}°</strong>
                </label>
                <input type="range" v-model.number="angle" :min="pendulumConfig.initialAngle?.min || 5"
                    :max="pendulumConfig.initialAngle?.max || 25" :step="pendulumConfig.initialAngle?.step || 1"
                    :disabled="isRunning" class="range-slider" />
            </div>

            <div class="button-group">
                <button @click="start" :disabled="isRunning" class="btn btn-start">
                    ▶️ Boshlash
                </button>
                <button @click="pause" :disabled="!isRunning" class="btn btn-pause">
                    ⏸️ To'xtatish
                </button>
                <button @click="reset" class="btn btn-reset">
                    🔄 Qayta
                </button>
                <button @click="measure" :disabled="period === 0" class="btn btn-measure">
                    📏 O'lchash
                </button>
            </div>
        </div>

        <!-- Photogate timer readout (what the real apparatus displays) -->
        <div class="timer-readout">
            Fotodarvoza taymeri: N = {{ countedPeriods }} / {{ TIMED_PERIODS }} tebranish,
            t = {{ timerReading.toFixed(4) }} s, T = t/N = {{ period.toFixed(PERIOD_DECIMALS) }} s
        </div>

        <!-- Energy Display -->
        <div class="energy-panel">
            <h4>Energiya (J)</h4>
            <div class="energy-bars">
                <div class="energy-item">
                    <span class="energy-label">Kinetik</span>
                    <div class="energy-bar kinetic">
                        <div class="bar" :style="{ width: (kineticEnergy / (totalEnergy || 1) * 100) + '%' }"></div>
                    </div>
                    <span class="energy-value">{{ kineticEnergy.toFixed(4) }}</span>
                </div>
                <div class="energy-item">
                    <span class="energy-label">Potensial</span>
                    <div class="energy-bar potential">
                        <div class="bar" :style="{ width: (potentialEnergy / (totalEnergy || 1) * 100) + '%' }"></div>
                    </div>
                    <span class="energy-value">{{ potentialEnergy.toFixed(4) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pendulum-simulation {
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
    flex-wrap: wrap;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 150px;
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
    cursor: pointer;
}

.range-slider:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-start {
    background: #10b981;
    color: white;
}

.btn-pause {
    background: #f59e0b;
    color: #1e293b;
}

.btn-reset {
    background: #475569;
    color: white;
}

.btn-measure {
    background: #3b82f6;
    color: white;
}

.timer-readout {
    font-size: 0.8rem;
    color: #94a3b8;
    font-variant-numeric: tabular-nums;
}

.energy-panel {
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    min-width: 250px;
}

.energy-panel h4 {
    font-size: 0.9rem;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.energy-bars {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.energy-item {
    display: grid;
    grid-template-columns: 80px 1fr 60px;
    align-items: center;
    gap: 0.5rem;
}

.energy-label {
    font-size: 0.75rem;
    color: #94a3b8;
}

.energy-bar {
    height: 8px;
    background: #334155;
    border-radius: 4px;
    overflow: hidden;
}

.energy-bar .bar {
    height: 100%;
    transition: width 0.1s;
}

.energy-bar.kinetic .bar {
    background: #ef4444;
}

.energy-bar.potential .bar {
    background: #10b981;
}

.energy-value {
    font-size: 0.75rem;
    color: white;
    text-align: right;
    font-variant-numeric: tabular-nums;
}
</style>
