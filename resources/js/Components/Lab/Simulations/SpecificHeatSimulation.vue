<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { constantPowerHeating, specificHeatFromMeasurement, SPECIFIC_HEAT, THERMO_INSTRUMENTS } from '../physics/thermodynamics.js';
import { createInstrument, roundSig } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const T_START = 20;            // °C
const T_STOP = 100;            // °C (thermometer range / boiling point of water)
const MAX_REAL_RUN = 20;       // s of real time for a full run; slower runs are time-accelerated

const material = ref('water');
const mass = ref(0.2);
const heatPower = ref(100);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const temperature = ref(T_START);
const time = ref(0);           // simulated heating time [s]
const heatAdded = ref(0);      // Q = P·t [J]
let lastTs = null;

// c from the tested table in physics/thermodynamics.js (sources documented there).
const materials = {
    water: { c: SPECIFIC_HEAT.water, name: 'Suv', color: '#3b82f6' },
    oil: { c: SPECIFIC_HEAT.oil, name: 'Moy', color: '#eab308' },
    iron: { c: SPECIFIC_HEAT.iron, name: 'Temir', color: '#64748b' },
    copper: { c: SPECIFIC_HEAT.copper, name: 'Mis', color: '#f97316' },
    aluminum: { c: SPECIFIC_HEAT.aluminium, name: 'Alyuminiy', color: '#a8a29e' },
};

// Readings of a real set-up: digital thermometer (0.1 °C) at the start and at the moment of
// measuring, stopwatch (0.01 s) for the heating time; the heater power P is the nameplate value.
const thermometer = createInstrument(THERMO_INSTRUMENTS.thermometer, 'specific-heat');
const stopwatch = createInstrument(THERMO_INSTRUMENTS.stopwatch, 'specific-heat');
const startReading = ref(null); // thermometer reading of T₀ of the current run [°C]

const specificHeat = computed(() => materials[material.value].c);

// Full run duration t = mcΔT/P (e.g. 1 kg water at 50 W ≈ 1.9 h) → accelerate so a run
// takes at most MAX_REAL_RUN real seconds. Displayed time/heat are the simulated (physical) values.
const fullRunTime = computed(() => (mass.value * specificHeat.value * (T_STOP - T_START)) / heatPower.value);
const timeScale = computed(() => Math.max(1, Math.ceil(fullRunTime.value / MAX_REAL_RUN)));

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const updateState = () => {
    const s = constantPowerHeating({
        T0: T_START, power: heatPower.value, mass: mass.value, c: specificHeat.value,
        elapsed: time.value, Tmax: T_STOP,
    });
    temperature.value = s.T;
    heatAdded.value = s.Q;
    if (s.done) {
        time.value = s.tMax;
        isRunning.value = false;
    }
};

const scheduleFrame = () => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    lastTs = performance.now(); // clock starts when the user presses the button
    animationId.value = requestAnimationFrame(animate);
};

const start = () => {
    if (isRunning.value) return;
    if (temperature.value >= T_STOP) return;
    if (startReading.value === null) {
        startReading.value = thermometer.read(T_START, [material.value, mass.value, heatPower.value, 'T0']);
    }
    isRunning.value = true;
    scheduleFrame();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    animationId.value = null;
    temperature.value = T_START;
    heatAdded.value = 0;
    time.value = 0;
    startReading.value = null;
    draw();
};

// Measurement: thermometer T (0.1 °C) and stopwatch t (0.01 s) are read, then
// c = P·t / (m·(T − T₀)) is evaluated from the READINGS (4 significant figures).
// Before the sample has warmed by ≥ 1 °C, c cannot be determined → only T is recorded.
const measure = () => {
    const key = [material.value, mass.value, heatPower.value, Number(time.value.toFixed(3))];
    const T = thermometer.read(temperature.value, key);
    emit('measurement', { name: `Harorat (${materials[material.value].name})`, value: thermometer.format(T), unit: '°C' });
    if (startReading.value === null || temperature.value - T_START < 1) return;
    const t = stopwatch.read(time.value, key);
    const c = specificHeatFromMeasurement(heatPower.value * t, mass.value, T - startReading.value);
    if (!(c > 0)) return;
    emit('measurement', { name: `Issiqlik sig'imi (${materials[material.value].name})`, value: String(roundSig(c, 4)), unit: 'J/(kg·°C)' });
};

// Exact solution Q = P·t, T = T₀ + Pt/(mc), driven by elapsed time (frame-rate independent).
const animate = (ts) => {
    animationId.value = null;
    if (!isRunning.value || props.isPaused) return;
    time.value += Math.min(Math.max((ts - lastTs) / 1000, 0), 0.1) * timeScale.value;
    lastTs = ts;

    updateState();

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

    // Beaker
    const beakerX = 150;
    const beakerY = 100;
    const beakerW = 150;
    const beakerH = 200;

    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(beakerX, beakerY);
    c.lineTo(beakerX, beakerY + beakerH);
    c.lineTo(beakerX + beakerW, beakerY + beakerH);
    c.lineTo(beakerX + beakerW, beakerY);
    c.stroke();

    // Liquid
    const liquidH = beakerH * 0.7;
    const liquidY = beakerY + beakerH - liquidH;
    c.fillStyle = materials[material.value].color;
    c.globalAlpha = 0.7;
    c.fillRect(beakerX + 3, liquidY, beakerW - 6, liquidH - 3);
    c.globalAlpha = 1;

    // Bubbles if hot
    if (temperature.value > 60) {
        const numBubbles = Math.floor((temperature.value - 60) / 10);
        for (let i = 0; i < numBubbles; i++) {
            const bx = beakerX + 20 + Math.random() * (beakerW - 40);
            const by = liquidY + Math.random() * liquidH;
            c.beginPath();
            c.arc(bx, by, 3 + Math.random() * 5, 0, Math.PI * 2);
            c.fillStyle = 'rgba(255,255,255,0.3)';
            c.fill();
        }
    }

    // Heat source
    c.fillStyle = '#ef4444';
    c.fillRect(beakerX - 10, beakerY + beakerH + 10, beakerW + 20, 20);
    if (isRunning.value) {
        for (let i = 0; i < 5; i++) {
            const fx = beakerX + 20 + i * 30;
            const fy = beakerY + beakerH + 5;
            c.fillStyle = '#f59e0b';
            c.beginPath();
            c.moveTo(fx, fy);
            c.lineTo(fx - 8, fy - 25 - Math.random() * 10);
            c.lineTo(fx + 8, fy - 25 - Math.random() * 10);
            c.closePath();
            c.fill();
        }
    }

    // Thermometer
    const thermX = beakerX + beakerW + 80;
    const thermY = 80;
    const thermH = 250;

    c.fillStyle = '#1e293b';
    c.fillRect(thermX - 15, thermY, 30, thermH);
    c.beginPath();
    c.arc(thermX, thermY + thermH + 15, 25, 0, Math.PI * 2);
    c.fill();

    const tempHeight = ((temperature.value - 0) / 100) * (thermH - 30);
    c.fillStyle = '#ef4444';
    c.fillRect(thermX - 8, thermY + thermH - tempHeight - 20, 16, tempHeight + 20);
    c.beginPath();
    c.arc(thermX, thermY + thermH + 15, 18, 0, Math.PI * 2);
    c.fill();

    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.textAlign = 'right';
    for (let t = 0; t <= 100; t += 20) {
        const y = thermY + thermH - 20 - (t / 100) * (thermH - 30);
        c.fillText(`${t}°`, thermX - 20, y + 3);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 280, 20, 260, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Modda: ${materials[material.value].name}`, w - 265, 45);
    c.fillText(`Massa: ${mass.value} kg`, w - 265, 65);
    c.fillText(`c = ${specificHeat.value} J/(kg·°C)`, w - 265, 85);
    c.fillText(`Isitgich quvvati: ${heatPower.value} W`, w - 265, 105);
    c.fillStyle = '#ef4444';
    c.fillText(`T = ${temperature.value.toFixed(1)} °C`, w - 265, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Q = ${heatAdded.value.toFixed(0)} J`, w - 265, 150);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s${timeScale.value > 1 ? ` (×${timeScale.value})` : ''}`, w - 265, 170);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('Q = mcΔT', w - 265, 192);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) scheduleFrame(); });
// Changing the sample/heater invalidates the recorded Q and T → start a fresh experiment.
watch([material, mass, heatPower], () => { if (!isRunning.value) reset(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Modda:</label>
                <select v-model="material" :disabled="isRunning">
                    <option v-for="(m, key) in materials" :key="key" :value="key">{{ m.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.1" :max="1" :step="0.1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Quvvat: <strong>{{ heatPower }} W</strong></label>
                <input type="range" v-model.number="heatPower" :min="50" :max="500" :step="50" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🔥 Isitish</button>
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
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.control-group select {
    padding: 0.5rem;
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
    background: #ef4444;
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
