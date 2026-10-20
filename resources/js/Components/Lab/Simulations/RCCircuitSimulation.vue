<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { rcCharging, rcDischarging, rcTimeConstant, toFixedSafe, createDmm } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(10);
const resistance = ref(1000);
const capacitance = ref(100); // µF (slider unit) → ×1e-6 F
const circuitType = ref('charge');

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const hasStarted = ref(false); // switch thrown at least once since the last reset
const animationId = ref(null);

// Simulated (physical) time since the switch was thrown [s]. Vc and I are exact
// closed-form functions of it, so nothing depends on the frame rate.
const time = ref(0);
let lastTs = null;

const C_SI = computed(() => capacitance.value * 1e-6);
const tau = computed(() => rcTimeConstant(resistance.value, C_SI.value)); // τ = RC
const totalTime = computed(() => 5 * tau.value);

// Stretch fast transients to ≥ 4 s of wall-clock playback (values still use simulated t).
const MIN_PLAYBACK_S = 4;
const playbackRate = computed(() => Math.min(1, totalTime.value / MIN_PLAYBACK_S));

const solve = (t) => (circuitType.value === 'charge'
    ? rcCharging(voltage.value, resistance.value, C_SI.value, t)
    : rcDischarging(voltage.value, resistance.value, C_SI.value, t));

// Before the switch is thrown: charging starts from Vc = 0, discharging from Vc = V0; I = 0.
const voltageC = computed(() => (hasStarted.value ? solve(time.value).vc : (circuitType.value === 'charge' ? 0 : voltage.value)));
const current = computed(() => (hasStarted.value ? solve(time.value).i : 0));

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const emitState = () => {
    emit('update:state', {
        ...(props.state || {}),
        voltage: voltage.value,
        resistance: resistance.value,
        capacitance: C_SI.value,
        tau: tau.value,
        process: circuitType.value,
        time: time.value,
        capacitorVoltage: voltageC.value,
        current: current.value,
    });
};

const start = () => {
    if (isRunning.value) return;
    time.value = 0;
    lastTs = null;
    hasStarted.value = true;
    isRunning.value = true;
    animationId.value = requestAnimationFrame(animate);
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    hasStarted.value = false;
    time.value = 0;
    draw();
};

const animate = (ts) => {
    if (!isRunning.value) return;
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused) {
        time.value = Math.min(totalTime.value, time.value + dt * playbackRate.value);
        if (time.value >= totalTime.value) {
            isRunning.value = false;
            emitState();
        }
    }
    draw();
    if (isRunning.value) animationId.value = requestAnimationFrame(animate);
};

// Vc: 3½-digit auto-ranging DMM across the capacitor (10 MΩ input, negligible load).
// The run stops at exactly t = 5τ (timer-controlled switch).
const voltmeter = createDmm('voltage', 'rc-circuit');
const voltmeterDisplay = computed(() => voltmeter.show(voltageC.value).display);
const measure = () => {
    const r = voltmeter.read(voltageC.value, [circuitType.value, resistance.value, capacitance.value, time.value]);
    emit('measurement', {
        name: `Vc (t=${toFixedSafe(time.value * 1000, 0)} ms, τ=${toFixedSafe(tau.value * 1000, 1)} ms)`,
        value: r.text,
        unit: r.unit,
    });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Voltage graph
    const graphX = 50;
    const graphY = 50;
    const graphW = 380;
    const graphH = 150;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 40, graphY + graphH - 20);
    c.lineTo(graphX + 40, graphY + 10);
    c.lineTo(graphX + graphW - 10, graphY + 10);
    c.stroke();

    // Exact curves sampled at N points over [0, 5τ] (a fixed 10 ms step drew
    // nothing when 5τ < 10 ms, e.g. R = 100 Ω, C = 10 µF → 5τ = 5 ms).
    const N = 200;
    const T = totalTime.value;

    // Voltage curve
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let k = 0; k <= N; k++) {
        const v = solve((k / N) * T).vc;
        const x = graphX + 40 + (k / N) * (graphW - 60);
        const y = graphY + graphH - 20 - (v / voltage.value) * (graphH - 40);
        if (k === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current point
    if (hasStarted.value && T > 0) {
        const x = graphX + 40 + (time.value / T) * (graphW - 60);
        const y = graphY + graphH - 20 - (voltageC.value / voltage.value) * (graphH - 40);
        c.beginPath();
        c.arc(x, y, 5, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
    }

    c.fillStyle = '#3b82f6';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText('Vc(t)', graphX + 10, graphY + 30);

    // Current graph
    const graphY2 = 230;
    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY2, graphW, graphH);

    c.strokeStyle = '#475569';
    c.beginPath();
    c.moveTo(graphX + 40, graphY2 + graphH / 2);
    c.lineTo(graphX + graphW - 10, graphY2 + graphH / 2);
    c.moveTo(graphX + 40, graphY2 + 10);
    c.lineTo(graphX + 40, graphY2 + graphH - 10);
    c.stroke();

    // Current curve
    c.strokeStyle = '#22c55e';
    c.lineWidth = 2;
    c.beginPath();
    const maxI = voltage.value / resistance.value;
    for (let k = 0; k <= N; k++) {
        const i = solve((k / N) * T).i;
        const x = graphX + 40 + (k / N) * (graphW - 60);
        const y = graphY2 + graphH / 2 - (i / maxI) * (graphH / 2 - 20);
        if (k === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current operating point
    if (hasStarted.value && T > 0) {
        const x = graphX + 40 + (time.value / T) * (graphW - 60);
        const y = graphY2 + graphH / 2 - (current.value / maxI) * (graphH / 2 - 20);
        c.beginPath();
        c.arc(x, y, 5, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
    }

    c.fillStyle = '#22c55e';
    c.fillText('I(t)', graphX + 10, graphY2 + 30);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(500, 50, 350, 220);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`R = ${resistance.value} Ω`, 520, 80);
    c.fillText(`C = ${capacitance.value} µF`, 520, 100);
    c.fillStyle = '#f59e0b';
    c.fillText(`τ = RC = ${toFixedSafe(tau.value * 1000, 1)} ms`, 520, 125);

    c.fillStyle = '#3b82f6';
    c.fillText(`Vc = ${voltmeterDisplay.value}`, 520, 155);
    c.fillStyle = '#22c55e';
    c.fillText(`I = ${toFixedSafe(current.value * 1000, 2)} mA`, 520, 175);
    c.fillStyle = '#64748b';
    c.fillText(`t = ${toFixedSafe(time.value * 1000, 0)} ms`, 520, 195);
    if (playbackRate.value < 1) {
        c.fillText(`(sekinlashtirilgan ×${toFixedSafe(1 / playbackRate.value, 0)})`, 620, 195);
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText(circuitType.value === 'charge' ? 'Vc = V₀(1 - e^(-t/τ))' : 'Vc = V₀ × e^(-t/τ)', 520, 225);
    c.fillText(circuitType.value === 'charge' ? 'I = I₀ × e^(-t/τ)' : 'I = -I₀ × e^(-t/τ)', 520, 245);
};

// Parameter / mode changes (only possible while stopped) start a fresh experiment
watch([resistance, capacitance, circuitType], () => { if (!isRunning.value) reset(); });
watch(() => props.isPaused, (p) => { if (!p) lastTs = null; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>R: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="100" :max="5000" :step="100"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>C: <strong>{{ capacitance }} µF</strong></label>
                <input type="range" v-model.number="capacitance" :min="10" :max="500" :step="10"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Jarayon:</label>
                <select v-model="circuitType" :disabled="isRunning">
                    <option value="charge">Zaryadlash</option>
                    <option value="discharge">Razryad</option>
                </select>
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="measure" class="btn measure">📏</button>
                <button @click="reset" class="btn reset">🔄</button>
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
    min-width: 100px;
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
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #22c55e;
    color: white;
}

.btn.measure {
    background: #3b82f6;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
