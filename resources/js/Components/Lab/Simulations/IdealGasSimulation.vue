<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { R, ATM, idealGasPressure, idealGasVolume } from '../physics/thermodynamics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// UI units: P [atm], V [L], T [K], n [mol]. Physics is evaluated in SI.
const L_PER_M3 = 1000;
const atmFromPa = (pa) => pa / ATM;
const solveVolumeL = (n, T, Patm) => idealGasVolume(n, T, Patm * ATM) * L_PER_M3;
const solvePressureAtm = (n, T, VL) => atmFromPa(idealGasPressure(n, T, VL / L_PER_M3));

const DEFAULTS = { T: 300, P: 1, n: 1 };

// The state always satisfies PV = nRT: three variables are chosen, the fourth is derived.
// - Moving P recomputes V (T, n fixed — Boyle's law).
// - Moving V recomputes P (T, n fixed — Boyle's law).
// - Moving T or n recomputes the variable that is NOT held constant
//   (hold 'pressure' → V changes: Charles; hold 'volume' → P changes: Gay-Lussac).
const holdConstant = ref('pressure');
const pressure = ref(DEFAULTS.P); // atm
const temperature = ref(DEFAULTS.T); // K
const moles = ref(DEFAULTS.n);
const volume = ref(solveVolumeL(DEFAULTS.n, DEFAULTS.T, DEFAULTS.P)); // L (24.6 L)

const recomputeDependent = () => {
    if (holdConstant.value === 'pressure') volume.value = solveVolumeL(moles.value, temperature.value, pressure.value);
    else pressure.value = solvePressureAtm(moles.value, temperature.value, volume.value);
};

const pressureModel = computed({
    get: () => pressure.value,
    set: (p) => { if (!(p > 0)) return; pressure.value = p; volume.value = solveVolumeL(moles.value, temperature.value, p); },
});
const volumeModel = computed({
    get: () => volume.value,
    set: (v) => { if (!(v > 0)) return; volume.value = v; pressure.value = solvePressureAtm(moles.value, temperature.value, v); },
});
const temperatureModel = computed({
    get: () => temperature.value,
    set: (t) => { if (!(t > 0)) return; temperature.value = t; recomputeDependent(); },
});
const molesModel = computed({
    get: () => moles.value,
    set: (n) => { if (!(n > 0)) return; moles.value = n; recomputeDependent(); },
});

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
const time = ref(0);
let lastTs = null;

const particles = ref([]);
const numParticles = 100;

// Consistency check values (both in J): PV and nRT.
const pvJoule = computed(() => pressure.value * ATM * (volume.value / L_PER_M3));
const nrtJoule = computed(() => moles.value * R * temperature.value);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

// Drawn box AREA is proportional to V (2-D picture of the 3-D volume).
const BOX_X = 100;
const BOX_Y = 100;
const V_REF = 30; // L drawn as BOX_W0 × BOX_H0
const BOX_W0 = 180;
const BOX_H0 = 150;
const boxSize = computed(() => {
    const k = Math.sqrt(Math.max(volume.value, 0) / V_REF);
    return { w: Math.min(410, Math.max(16, BOX_W0 * k)), h: Math.min(340, Math.max(14, BOX_H0 * k)) };
});

// Particles live in normalised box coordinates (u, v ∈ [0, 1]) so they stay inside when V changes.
const initParticles = () => {
    particles.value = [];
    for (let i = 0; i < numParticles; i++) {
        const a = Math.random() * Math.PI * 2;
        particles.value.push({ u: Math.random(), v: Math.random(), vx: Math.cos(a) * 120, vy: Math.sin(a) * 120 });
    }
};

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        initParticles();
        draw();
    }
};

const scheduleFrame = () => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    lastTs = null;
    animationId.value = requestAnimationFrame(animate);
};

const start = () => {
    if (isRunning.value) return;
    isRunning.value = true;
    scheduleFrame();
};

const pause = () => { isRunning.value = false; };

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    animationId.value = null;
    temperature.value = DEFAULTS.T;
    pressure.value = DEFAULTS.P;
    moles.value = DEFAULTS.n;
    volume.value = solveVolumeL(DEFAULTS.n, DEFAULTS.T, DEFAULTS.P);
    time.value = 0;
    initParticles();
    draw();
};

const measure = () => {
    emit('measurement', { name: 'Hisoblangan V', value: solveVolumeL(moles.value, temperature.value, pressure.value).toFixed(3), unit: 'L' });
};

const animate = (ts) => {
    animationId.value = null;
    if (!isRunning.value || props.isPaused) return;
    const dt = lastTs === null ? 0 : Math.min((ts - lastTs) / 1000, 0.1);
    lastTs = ts;

    // Molecular speed ∝ v_rms = √(3RT/M) ∝ √T.
    const speedFactor = Math.sqrt(temperature.value / 300);
    const { w: bw, h: bh } = boxSize.value;

    particles.value.forEach(p => {
        p.u += (p.vx * speedFactor * dt) / bw;
        p.v += (p.vy * speedFactor * dt) / bh;
        if (p.u < 0 || p.u > 1) { p.vx *= -1; p.u = Math.max(0, Math.min(1, p.u)); }
        if (p.v < 0 || p.v > 1) { p.vy *= -1; p.v = Math.max(0, Math.min(1, p.v)); }
    });

    time.value += dt;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Container
    const { w: containerW, h: containerH } = boxSize.value;

    c.strokeStyle = '#475569';
    c.lineWidth = 4;
    c.strokeRect(BOX_X, BOX_Y, containerW, containerH);

    // Piston (top)
    c.fillStyle = '#64748b';
    c.fillRect(BOX_X, BOX_Y - 20, containerW, 20);

    // Particles
    const speedFactor = Math.sqrt(temperature.value / 300);
    particles.value.forEach(p => {
        c.beginPath();
        c.arc(BOX_X + p.u * containerW, BOX_Y + p.v * containerH, 4, 0, Math.PI * 2);
        c.fillStyle = `hsl(${60 - speedFactor * 30}, 90%, 60%)`;
        c.fill();
    });

    // Labels
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`V = ${volume.value.toFixed(2)} L`, BOX_X + containerW / 2, BOX_Y + containerH + 30);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 260, 20, 240, 200);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Bosim (P): ${pressure.value.toFixed(2)} atm`, w - 245, 45);
    c.fillText(`Hajm (V): ${volume.value.toFixed(2)} L`, w - 245, 65);
    c.fillText(`Harorat (T): ${temperature.value} K`, w - 245, 85);
    c.fillText(`Mol soni (n): ${moles.value}`, w - 245, 105);

    c.fillStyle = '#10b981';
    c.fillText(`PV = ${pvJoule.value.toFixed(1)} J`, w - 245, 130);
    c.fillStyle = '#3b82f6';
    c.fillText(`nRT = ${nrtJoule.value.toFixed(1)} J`, w - 245, 150);
    c.fillStyle = '#94a3b8';
    c.fillText(`Doimiy: ${holdConstant.value === 'pressure' ? 'bosim (P)' : 'hajm (V)'}`, w - 245, 170);

    // Formula
    c.fillStyle = '#f59e0b';
    c.font = '16px serif';
    c.fillText('PV = nRT', w - 245, 200);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) scheduleFrame(); });
watch([pressure, temperature, volume, moles, holdConstant], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Bosim: <strong>{{ pressure.toFixed(2) }} atm</strong></label>
                <input type="range" v-model.number="pressureModel" :min="0.5" :max="10" :step="0.1" />
            </div>
            <div class="control-group">
                <label>Harorat: <strong>{{ temperature }} K</strong></label>
                <input type="range" v-model.number="temperatureModel" :min="200" :max="500" :step="10" />
            </div>
            <div class="control-group">
                <label>Hajm: <strong>{{ volume.toFixed(2) }} L</strong></label>
                <input type="range" v-model.number="volumeModel" :min="2" :max="160" :step="1" />
            </div>
            <div class="control-group">
                <label>Mol: <strong>{{ moles }}</strong></label>
                <input type="range" v-model.number="molesModel" :min="0.5" :max="2" :step="0.1" />
            </div>
            <div class="control-group">
                <label>T yoki n o'zgarganda doimiy:</label>
                <select v-model="holdConstant">
                    <option value="pressure">Bosim (P)</option>
                    <option value="volume">Hajm (V)</option>
                </select>
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="pause" :disabled="!isRunning" class="btn pause">⏸️</button>
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
    align-items: flex-end;
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

.control-group select {
    padding: 0.4rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
}

.buttons {
    display: flex;
    gap: 0.5rem;
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

.btn.pause {
    background: #f59e0b;
    color: #1e293b;
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
