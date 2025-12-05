<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Simulation parameters
const length = ref(props.config?.pendulum?.length?.default || 1.0);
const mass = ref(props.config?.pendulum?.mass?.default || 0.5);
const angle = ref(props.config?.pendulum?.initialAngle?.default || 15);
const gravity = ref(props.config?.physics?.gravity || 9.8);

// Animation state
const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// Physics state
const theta = ref(0);
const omega = ref(0); // Angular velocity
const time = ref(0);

// Measurements
const period = ref(0);
const frequency = ref(0);
const lastCrossing = ref(0);
const crossingCount = ref(0);
const measuredPeriods = ref([]);

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

const theoreticalPeriod = computed(() => {
    return 2 * Math.PI * Math.sqrt(length.value / gravity.value);
});

const measuredAveragePeriod = computed(() => {
    if (measuredPeriods.value.length === 0) return 0;
    return measuredPeriods.value.reduce((a, b) => a + b, 0) / measuredPeriods.value.length;
});

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

const start = () => {
    if (isRunning.value) return;

    theta.value = (angle.value * Math.PI) / 180;
    omega.value = 0;
    time.value = 0;
    crossingCount.value = 0;
    measuredPeriods.value = [];
    angleHistory.value = [];
    energyHistory.value = [];

    isRunning.value = true;
    animate();
};

const pause = () => {
    isRunning.value = false;
    if (animationId.value) {
        cancelAnimationFrame(animationId.value);
    }
};

const reset = () => {
    pause();
    theta.value = (angle.value * Math.PI) / 180;
    omega.value = 0;
    time.value = 0;
    period.value = 0;
    crossingCount.value = 0;
    measuredPeriods.value = [];
    angleHistory.value = [];
    energyHistory.value = [];
    draw();
};

const measure = () => {
    // Record current period measurement
    if (measuredAveragePeriod.value > 0) {
        emit('measurement', {
            name: 'Tebranish davri (T)',
            value: measuredAveragePeriod.value.toFixed(3),
            unit: 's',
        });
    }
};

const animate = () => {
    if (!isRunning.value || props.isPaused) {
        return;
    }

    const dt = 1 / 60; // 60 FPS

    // Simple pendulum physics
    // d²θ/dt² = -(g/L) * sin(θ)
    const damping = props.config?.physics?.damping || 0.001;
    const alpha = -(gravity.value / length.value) * Math.sin(theta.value) - damping * omega.value;

    omega.value += alpha * dt;
    theta.value += omega.value * dt;
    time.value += dt;

    // Zero crossing detection for period measurement
    const prevSign = Math.sign(theta.value - omega.value * dt);
    const currSign = Math.sign(theta.value);

    if (prevSign !== currSign && omega.value > 0) {
        crossingCount.value++;
        if (crossingCount.value > 1) {
            const measuredT = time.value - lastCrossing.value;
            measuredPeriods.value.push(measuredT * 2); // Half period x 2
            period.value = measuredAveragePeriod.value;
            frequency.value = 1 / period.value;
        }
        lastCrossing.value = time.value;
    }

    // Calculate energies (per unit mass)
    const v = omega.value * length.value;
    const h = length.value * (1 - Math.cos(theta.value));

    kineticEnergy.value = 0.5 * mass.value * v * v;
    potentialEnergy.value = mass.value * gravity.value * h;
    totalEnergy.value = kineticEnergy.value + potentialEnergy.value;

    // Record history for graphs
    if (angleHistory.value.length > 500) {
        angleHistory.value.shift();
        energyHistory.value.shift();
    }

    angleHistory.value.push({
        t: time.value,
        theta: (theta.value * 180) / Math.PI,
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
    c.fillRect(20, 20, 200, 140);
    c.strokeStyle = '#334155';
    c.strokeRect(20, 20, 200, 140);

    c.fillStyle = '#ffffff';
    c.font = '12px Inter, sans-serif';

    c.fillText(`Ip uzunligi: ${length.value.toFixed(2)} m`, 35, 45);
    c.fillText(`Massa: ${mass.value.toFixed(2)} kg`, 35, 65);
    c.fillText(`Burchak: ${((theta.value * 180) / Math.PI).toFixed(1)}°`, 35, 85);

    c.fillStyle = '#10b981';
    c.fillText(`Davr (T): ${period.value.toFixed(3)} s`, 35, 110);
    c.fillStyle = '#3b82f6';
    c.fillText(`Nazariy T: ${theoreticalPeriod.value.toFixed(3)} s`, 35, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s`, 35, 150);
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
        const y = graphY + graphH / 2 - (point.theta / angle.value) * (graphH / 2 - 20);

        if (i === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    });

    c.stroke();
};

// Watchers
watch(() => props.isPaused, (paused) => {
    if (!paused && isRunning.value) {
        animate();
    }
});

watch([length, mass, angle], () => {
    if (!isRunning.value) {
        theta.value = (angle.value * Math.PI) / 180;
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
