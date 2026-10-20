<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { rcCharging, rcDischarging, rcTimeConstant, capacitorCharge, toFixedSafe, createDmm } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const capacitance = ref(100); // microfarads (slider unit) → ×1e-6 F
const resistance = ref(1000); // ohms

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const isCharging = ref(true);
const rcProcess = ref('idle'); // 'idle' | 'charge' | 'discharge'
const animationId = ref(null);

// Simulated (physical) time since the switch was thrown [s]. All values below are
// closed-form functions of it, so the result does not depend on the frame rate.
const time = ref(0);
let lastTs = null;
const arrowOffset = ref(0); // purely visual

const C_SI = computed(() => capacitance.value * 1e-6);
const maxCharge = computed(() => capacitorCharge(C_SI.value, voltage.value)); // Q = CV
const timeConstant = computed(() => rcTimeConstant(resistance.value, C_SI.value)); // τ = RC
const totalTime = computed(() => 5 * timeConstant.value); // 99.3 % complete after 5τ

// Playback: a fast process (τ = 1 ms … 2.5 s) is stretched to at least 4 s of wall
// time. Physical values are always evaluated at the simulated time.
const MIN_PLAYBACK_S = 4;
const playbackRate = computed(() => Math.min(1, totalTime.value / MIN_PLAYBACK_S));

const rc = computed(() => {
    if (rcProcess.value === 'charge') return rcCharging(voltage.value, resistance.value, C_SI.value, time.value);
    if (rcProcess.value === 'discharge') return rcDischarging(voltage.value, resistance.value, C_SI.value, time.value);
    return { vc: 0, i: 0, q: 0, energy: 0 };
});
const charge = computed(() => rc.value.q);
const current = computed(() => rc.value.i);
const capacitorVoltage = computed(() => rc.value.vc);
const storedEnergy = computed(() => rc.value.energy); // W = ½CV²

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const startProcess = (mode) => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    isCharging.value = mode === 'charge';
    rcProcess.value = mode;
    time.value = 0;
    lastTs = null;
    isRunning.value = true;
    animationId.value = requestAnimationFrame(animate);
};

const startCharge = () => startProcess('charge');
const startDischarge = () => startProcess('discharge'); // capacitor starts fully charged (Vc = U)

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    rcProcess.value = 'idle';
    isCharging.value = true;
    time.value = 0;
    draw();
};

const emitState = () => {
    emit('update:state', {
        ...(props.state || {}),
        voltage: voltage.value,
        capacitance: C_SI.value,
        resistance: resistance.value,
        timeConstant: timeConstant.value,
        process: rcProcess.value,
        time: time.value,
        capacitorVoltage: capacitorVoltage.value,
        charge: charge.value,
        current: current.value,
        energy: storedEnergy.value,
    });
};

const animate = (ts) => {
    if (!isRunning.value) return;
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused) {
        time.value = Math.min(totalTime.value, time.value + dt * playbackRate.value);
        const i0 = voltage.value / resistance.value;
        arrowOffset.value = (arrowOffset.value + 120 * dt * (i0 ? Math.abs(current.value) / i0 : 0)) % 150;
        if (time.value >= totalTime.value) {
            isRunning.value = false;
            emitState();
        }
    }
    draw();
    if (isRunning.value) animationId.value = requestAnimationFrame(animate);
};

// Capacitor voltage: 3½-digit auto-ranging DMM (10 MΩ input — its leakage time
// constant 10 MΩ·C ≥ 100 s does not disturb a transient of ≤ 12.5 s). The switch is
// thrown by the timer, so the reading at the end of the run is exactly t = 5τ.
const voltmeter = createDmm('voltage', 'capacitor');
const voltmeterDisplay = computed(() => voltmeter.show(capacitorVoltage.value).display);
const measure = () => {
    const r = voltmeter.read(capacitorVoltage.value, [rcProcess.value, capacitance.value, resistance.value, time.value]);
    emit('measurement', {
        name: `Kondensator kuchlanishi (t=${toFixedSafe(time.value * 1000, 0)} ms, τ=${toFixedSafe(timeConstant.value * 1000, 1)} ms)`,
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

    // Circuit
    const cx = 200;
    const cy = 200;

    // Battery (+ at the top)
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(cx - 80, cy - 80);
    c.lineTo(cx - 80, cy + 80);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.fillRect(cx - 95, cy - 30, 30, 60);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, cx - 80, cy + 5);
    c.fillText('+', cx - 80, cy - 38);
    c.fillText('−', cx - 80, cy + 48);

    // Wires
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(cx - 80, cy - 80);
    c.lineTo(cx + 150, cy - 80);
    c.lineTo(cx + 150, cy - 30);
    c.stroke();

    c.beginPath();
    c.moveTo(cx - 80, cy + 80);
    c.lineTo(cx + 150, cy + 80);
    c.lineTo(cx + 150, cy + 30);
    c.stroke();

    // Resistor R in series on the top wire
    c.fillStyle = '#1e293b';
    c.fillRect(cx + 10, cy - 92, 60, 24);
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.strokeRect(cx + 10, cy - 92, 60, 24);
    c.fillStyle = '#f59e0b';
    c.font = '11px Inter';
    c.fillText(`${resistance.value}Ω`, cx + 40, cy - 76);

    // Capacitor
    c.strokeStyle = '#64748b';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(cx + 130, cy - 30);
    c.lineTo(cx + 170, cy - 30);
    c.moveTo(cx + 130, cy + 30);
    c.lineTo(cx + 170, cy + 30);
    c.stroke();

    // Charge level: the plate wired to + becomes positive (red), the other negative (blue)
    const chargePercent = voltage.value ? Math.max(0, Math.min(1, capacitorVoltage.value / voltage.value)) : 0;
    c.globalAlpha = chargePercent;
    c.fillStyle = '#ef4444';
    c.fillRect(cx + 135, cy - 25, 30, 20);
    c.fillStyle = '#3b82f6';
    c.fillRect(cx + 135, cy + 5, 30, 20);
    c.globalAlpha = 1;
    if (chargePercent > 0.02) {
        c.fillStyle = 'white';
        c.font = 'bold 12px Inter';
        c.fillText('+', cx + 150, cy - 10);
        c.fillText('−', cx + 150, cy + 20);
    }

    // Current arrows on the top wire: → while charging (I > 0), ← while discharging (I < 0)
    if (Math.abs(current.value) > 1e-7) {
        const arrowDir = current.value > 0 ? 1 : -1;
        c.fillStyle = '#f59e0b';
        for (let i = 0; i < 3; i++) {
            const offset = (arrowOffset.value + i * 50) % 150;
            const x = arrowDir > 0 ? cx - 60 + offset : cx + 130 - offset;
            c.beginPath();
            c.moveTo(x + 5 * arrowDir, cy - 80);
            c.lineTo(x - 5 * arrowDir, cy - 90);
            c.lineTo(x - 5 * arrowDir, cy - 70);
            c.fill();
        }
    }

    // Graphs
    const graphX = 400;

    // Voltage graph: exact curve sampled at a fixed number of points over [0, 5τ]
    // (a fixed 10 ms step drew nothing when 5τ < 10 ms).
    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, 30, 180, 150);
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    const N = 200;
    const T = totalTime.value;
    const discharging = rcProcess.value === 'discharge';
    for (let k = 0; k <= N; k++) {
        const t = (k / N) * T;
        const v = discharging
            ? rcDischarging(voltage.value, resistance.value, C_SI.value, t).vc
            : rcCharging(voltage.value, resistance.value, C_SI.value, t).vc;
        const x = graphX + 20 + (k / N) * 140;
        const y = 170 - (v / voltage.value) * 120;
        if (k === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Operating point
    if (rcProcess.value !== 'idle' && T > 0) {
        const dotX = graphX + 20 + (time.value / T) * 140;
        const dotY = 170 - chargePercent * 120;
        c.beginPath();
        c.arc(dotX, dotY, 5, 0, Math.PI * 2);
        c.fillStyle = '#3b82f6';
        c.fill();
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('Vc(t)', graphX + 10, 50);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, 200, 220, 200);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`C = ${capacitance.value} µF`, graphX + 15, 225);
    c.fillText(`R = ${resistance.value} Ω`, graphX + 15, 245);
    c.fillText(`τ = RC = ${toFixedSafe(timeConstant.value * 1000, 1)} ms`, graphX + 15, 265);
    c.fillStyle = '#3b82f6';
    c.fillText(`Vc = ${voltmeterDisplay.value}`, graphX + 15, 290);
    c.fillStyle = '#f59e0b';
    c.fillText(`I = ${toFixedSafe(current.value * 1000, 2)} mA`, graphX + 15, 310);
    c.fillStyle = '#ef4444';
    c.fillText(`Q = C·Vc = ${toFixedSafe(charge.value * 1e6, 1)} µC`, graphX + 15, 330);
    c.fillStyle = '#22c55e';
    c.fillText(`W = ½CVc² = ${toFixedSafe(storedEnergy.value * 1000, 2)} mJ`, graphX + 15, 350);
    c.fillStyle = '#64748b';
    c.fillText(`t = ${toFixedSafe(time.value * 1000, 0)} ms`, graphX + 15, 370);
    if (playbackRate.value < 1) {
        c.fillText(`sekinlashtirilgan ×${toFixedSafe(1 / playbackRate.value, 0)}`, graphX + 15, 390);
    }
};

// Changing R or C invalidates a finished transient (it was computed with the old τ)
watch([capacitance, resistance], () => { if (!isRunning.value) reset(); });
watch(() => props.isPaused, (p) => { if (!p) lastTs = null; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Sig'im: <strong>{{ capacitance }} µF</strong></label>
                <input type="range" v-model.number="capacitance" :min="10" :max="500" :step="10"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Qarshilik: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="100" :max="5000" :step="100"
                    :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="startCharge" :disabled="isRunning" class="btn charge">⚡ Zaryadlash</button>
                <button @click="startDischarge" :disabled="isRunning" class="btn discharge">🔋 Razryad</button>
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
}

.btn {
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.charge {
    background: #3b82f6;
    color: white;
}

.btn.discharge {
    background: #f59e0b;
    color: #1e293b;
}

.btn.measure {
    background: #0ea5e9;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
