<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { DIODE_MODELS, diodeCircuit, shockleyCurrent, thermalVoltage, toFixedSafe } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(5);
const resistance = ref(100);
const diodeType = ref('silicon');

const canvas = ref(null);
const ctx = ref(null);

// forwardDrop = textbook threshold (constant-drop model); the simulation itself
// uses the Shockley equation with the parameters in DIODE_MODELS (300 K).
const diodes = {
    silicon: { name: 'Kremniy', forwardDrop: 0.7, color: '#3b82f6', model: DIODE_MODELS.silicon },
    germanium: { name: 'Germaniy', forwardDrop: 0.3, color: '#22c55e', model: DIODE_MODELS.germanium },
    led: { name: 'LED', forwardDrop: 1.8, color: '#ef4444', model: DIODE_MODELS.led },
};

const VT = thermalVoltage(300); // ≈ 25.85 mV
const CONDUCTION_THRESHOLD_A = 1e-4; // 0.1 mA: "conducting" for the UI label

const forwardVoltage = computed(() => diodes[diodeType.value].forwardDrop);
const model = computed(() => diodes[diodeType.value].model);
// Exact operating point of source → R → diode (Lambert-W closed form)
const operatingPoint = computed(() => diodeCircuit(voltage.value, resistance.value, model.value, VT));
const isReverseBiased = computed(() => voltage.value < 0);
const isForwardBiased = computed(() => operatingPoint.value.current > CONDUCTION_THRESHOLD_A); // conducting
const current = computed(() => operatingPoint.value.current * 1000); // mA
const diodeVoltage = computed(() => operatingPoint.value.diodeVoltage); // V

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', {
        name: `Diod toki (${diodes[diodeType.value].name}, U=${voltage.value}V, R=${resistance.value}Ω)`,
        value: toFixedSafe(current.value, 2),
        unit: 'mA',
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
    const circuitX = 100;
    const circuitY = 200;

    // Battery (terminal facing the diode is + for U > 0, − for U < 0)
    c.fillStyle = '#22c55e';
    c.fillRect(circuitX, circuitY - 30, 15, 60);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, circuitX + 7, circuitY + 50);
    c.fillText(voltage.value < 0 ? '−' : '+', circuitX + 7, circuitY - 40);

    // Wires
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(circuitX + 15, circuitY);
    c.lineTo(circuitX + 100, circuitY);
    c.stroke();

    // Diode (anode on the left, cathode bar on the right)
    const diodeColor = diodes[diodeType.value].color;
    c.fillStyle = diodeColor;
    c.beginPath();
    c.moveTo(circuitX + 100, circuitY - 30);
    c.lineTo(circuitX + 100, circuitY + 30);
    c.lineTo(circuitX + 140, circuitY);
    c.closePath();
    c.fill();

    c.strokeStyle = diodeColor;
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(circuitX + 140, circuitY - 30);
    c.lineTo(circuitX + 140, circuitY + 30);
    c.stroke();

    // LED glow: brightness ∝ current (full at 20 mA)
    if (diodeType.value === 'led' && isForwardBiased.value) {
        const b = Math.min(1, operatingPoint.value.current / 0.02);
        const glowGrad = c.createRadialGradient(circuitX + 120, circuitY, 0, circuitX + 120, circuitY, 20 + 30 * b);
        glowGrad.addColorStop(0, `rgba(239, 68, 68, ${0.3 + 0.6 * b})`);
        glowGrad.addColorStop(1, 'transparent');
        c.fillStyle = glowGrad;
        c.fillRect(circuitX + 60, circuitY - 60, 120, 120);
    }

    // Continue wire
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(circuitX + 140, circuitY);
    c.lineTo(circuitX + 200, circuitY);
    c.stroke();

    // Resistor
    c.fillStyle = '#f59e0b';
    c.fillRect(circuitX + 200, circuitY - 15, 60, 30);
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.fillText(`${resistance.value}Ω`, circuitX + 230, circuitY + 5);

    // Complete circuit
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(circuitX + 260, circuitY);
    c.lineTo(circuitX + 320, circuitY);
    c.lineTo(circuitX + 320, circuitY + 100);
    c.lineTo(circuitX, circuitY + 100);
    c.lineTo(circuitX, circuitY + 30);
    c.stroke();

    // Current arrows (conventional current, anode → cathode)
    if (isForwardBiased.value) {
        c.fillStyle = '#22c55e';
        for (let i = 0; i < 3; i++) {
            const offset = i * 80;
            c.beginPath();
            c.moveTo(circuitX + 50 + offset, circuitY - 10);
            c.lineTo(circuitX + 60 + offset, circuitY);
            c.lineTo(circuitX + 50 + offset, circuitY + 10);
            c.fill();
        }
    }

    // I–V characteristic (Shockley) with the circuit's load line I = (U − V_D)/R.
    // Their intersection is the operating point.
    const graphX = 500;
    const graphY = 30;
    const graphW = 350;
    const graphH = 270;
    const VMIN = -5;
    const VMAX = 3;
    const plotL = graphX + 20;
    const plotR = graphX + graphW - 20;
    const plotTop = graphY + 25;
    const y0 = graphY + graphH - 35; // I = 0 axis
    const xOf = (v) => plotL + ((v - VMIN) / (VMAX - VMIN)) * (plotR - plotL);
    const x0 = xOf(0);
    const iScale = Math.max(0.02, operatingPoint.value.current * 1.25); // full-scale current [A]
    const yOf = (i) => y0 - (i / iScale) * (y0 - plotTop);

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(x0, plotTop - 5);
    c.lineTo(x0, graphY + graphH - 10);
    c.moveTo(plotL, y0);
    c.lineTo(plotR, y0);
    c.stroke();

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('V_D (V)', plotR - 15, y0 - 8);
    c.fillText(`I (max ${toFixedSafe(iScale * 1000, 0)} mA)`, x0 + 55, plotTop - 8);
    for (let v = VMIN; v <= VMAX; v += 1) {
        c.fillText(String(v), xOf(v), y0 + 15);
    }

    c.save();
    c.beginPath();
    c.rect(plotL, plotTop, plotR - plotL, graphY + graphH - plotTop);
    c.clip();

    // Load line
    c.strokeStyle = '#94a3b8';
    c.lineWidth = 1;
    c.setLineDash([4, 4]);
    c.beginPath();
    c.moveTo(xOf(VMIN), yOf((voltage.value - VMIN) / resistance.value));
    c.lineTo(xOf(VMAX), yOf((voltage.value - VMAX) / resistance.value));
    c.stroke();
    c.setLineDash([]);

    // Shockley curve
    c.strokeStyle = diodeColor;
    c.lineWidth = 2;
    c.beginPath();
    const steps = 400;
    for (let k = 0; k <= steps; k++) {
        const v = VMIN + (k / steps) * (VMAX - VMIN);
        const i = shockleyCurrent(v, model.value.Is, model.value.n, VT);
        const y = Math.max(plotTop - 10, yOf(i));
        if (k === 0) c.moveTo(xOf(v), y);
        else c.lineTo(xOf(v), y);
        if (y <= plotTop - 10) break;
    }
    c.stroke();
    c.restore();

    // Operating point (diode voltage V_D, diode current I)
    const opX = Math.max(plotL, Math.min(plotR, xOf(diodeVoltage.value)));
    const opY = Math.max(plotTop, yOf(operatingPoint.value.current));
    c.beginPath();
    c.arc(opX, opY, 6, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Vf marker (textbook threshold)
    const vf = forwardVoltage.value;
    c.strokeStyle = '#f59e0b';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(xOf(vf), y0);
    c.lineTo(xOf(vf), plotTop);
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vf=${vf}V`, xOf(vf), y0 + 28);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 10, 350, 115);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    const py = graphY + graphH + 10;
    c.fillText(`Diod: ${diodes[diodeType.value].name}   (Vf ≈ ${forwardVoltage.value} V)`, graphX + 15, py + 22);
    c.fillText(`V_D = ${toFixedSafe(diodeVoltage.value, 3)} V,  U_R = ${toFixedSafe(operatingPoint.value.resistorVoltage, 3)} V`, graphX + 15, py + 42);
    c.fillStyle = isForwardBiased.value ? '#22c55e' : '#ef4444';
    const status = isForwardBiased.value
        ? '✓ O\'tkazadi (to\'g\'ri ulangan)'
        : (isReverseBiased.value ? '✗ O\'tkazmaydi (teskari ulangan)' : '✗ O\'tkazmaydi (U < Vf)');
    c.fillText(status, graphX + 15, py + 62);
    c.fillText(`I = ${toFixedSafe(current.value, 2)} mA`, graphX + 15, py + 82);
    c.fillStyle = '#94a3b8';
    c.font = '10px Inter';
    c.fillText('I = Is(e^(V_D/nV_T) − 1),  U = I·R + V_D,  V_T = 25.85 mV', graphX + 15, py + 102);
};

watch([voltage, resistance, diodeType], () => {
    draw();
    emit('update:state', {
        ...(props.state || {}),
        diodeType: diodeType.value,
        voltage: voltage.value,
        resistance: resistance.value,
        current: operatingPoint.value.current,
        diodeVoltage: diodeVoltage.value,
    });
});
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Diod turi:</label>
                <select v-model="diodeType">
                    <option v-for="(d, key) in diodes" :key="key" :value="key">{{ d.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Kuchlanish: <strong>{{ voltage }} V</strong></label>
                <input type="range" v-model.number="voltage" :min="-5" :max="10" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Qarshilik: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="50" :max="500" :step="50" />
            </div>
            <button @click="measure" class="btn">📏</button>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 120px;
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
