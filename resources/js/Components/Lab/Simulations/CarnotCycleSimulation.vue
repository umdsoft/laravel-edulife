<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { carnotEfficiency, carnotCycle, carnotStateAt } from '../physics/thermodynamics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const hotTemp = ref(600);   // K
const coldTemp = ref(300);  // K

// Working body: 1 mol of monatomic ideal gas (γ = 5/3), isothermal expansion 1 L → 2 L.
const N_MOL = 1;
const GAMMA = 5 / 3;
const V1 = 1e-3; // m³
const V2 = 2e-3; // m³
const STAGE_DURATION = 2; // s of real time per stage

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);
const stage = ref(0);
const stageProgress = ref(0);
let lastTs = null;

// η = 1 − Tc/Th (kelvin); Qh = nRTh ln(V2/V1), Qc = nRTc ln(V2/V1), W = Qh − Qc.
const efficiency = computed(() => carnotEfficiency(hotTemp.value, coldTemp.value));
const cycle = computed(() => carnotCycle({ n: N_MOL, Th: hotTemp.value, Tc: coldTemp.value, V1, V2, gamma: GAMMA }));
const qHot = computed(() => cycle.value ? cycle.value.Qh : NaN);
const qCold = computed(() => cycle.value ? cycle.value.Qc : NaN);
const work = computed(() => cycle.value ? cycle.value.W : NaN);
const currentState = computed(() => carnotStateAt(cycle.value, stage.value, stageProgress.value));

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

// Stage and position on the cycle are analytic functions of elapsed time.
const animate = (ts) => {
    if (lastTs !== null && !props.isPaused) time.value += Math.min((ts - lastTs) / 1000, 0.1);
    lastTs = ts;
    const phase = time.value / STAGE_DURATION;
    stage.value = Math.floor(phase) % 4;
    stageProgress.value = phase - Math.floor(phase);
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const stageNames = ['Izotermik kengayish', 'Adiabatik kengayish', 'Izotermik siqilish', 'Adiabatik siqilish'];

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // PV Diagram
    const pvX = 50;
    const pvY = 50;
    const pvW = 350;
    const pvH = 280;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(pvX, pvY, pvW, pvH);

    // Axes (origin bottom-left: P up, V right)
    const axL = pvX + 40;
    const axR = pvX + pvW - 20;
    const axTop = pvY + 20;
    const axBottom = pvY + pvH - 30;
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(axL, axTop);
    c.lineTo(axL, axBottom);
    c.lineTo(axR, axBottom);
    c.stroke();

    const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6'];
    const cyc = cycle.value;
    if (cyc) {
        const vMax = Math.max(...cyc.states.map(s => s.V)) * 1.08;
        const pMax = Math.max(...cyc.states.map(s => s.P)) * 1.08;
        const xOf = (V) => axL + (V / vMax) * (axR - axL);
        const yOf = (P) => axBottom - (P / pMax) * (axBottom - axTop);

        c.fillStyle = '#94a3b8';
        c.font = '12px Inter';
        c.textAlign = 'center';
        c.fillText('V (L)', axR - 10, axBottom + 18);
        c.fillText(`${(vMax * 1000).toFixed(1)}`, axR - 50, axBottom + 18);
        c.textAlign = 'left';
        c.fillText('P (kPa)', axL + 5, axTop - 5);
        c.textAlign = 'right';
        c.fillText(`${(pMax / 1000).toFixed(0)}`, axL - 4, axTop + 10);
        c.fillText('0', axL - 4, axBottom + 4);
        c.textAlign = 'center';

        // Sample the exact isotherms (PV = const) and adiabats (PV^γ = const).
        const SAMPLES = 48;
        const stagePath = (i) => {
            const pts = [];
            for (let k = 0; k <= SAMPLES; k++) pts.push(carnotStateAt(cyc, i, k / SAMPLES));
            return pts;
        };
        const paths = [0, 1, 2, 3].map(stagePath);

        // Fill cycle area (= net work W)
        c.fillStyle = 'rgba(34, 197, 94, 0.2)';
        c.beginPath();
        paths.flat().forEach((s, k) => (k === 0 ? c.moveTo(xOf(s.V), yOf(s.P)) : c.lineTo(xOf(s.V), yOf(s.P))));
        c.closePath();
        c.fill();

        // 1→2 isothermal expansion (hot), 2→3 adiabatic expansion,
        // 3→4 isothermal compression (cold), 4→1 adiabatic compression
        paths.forEach((pts, i) => {
            c.strokeStyle = colors[i];
            c.lineWidth = stage.value === i ? 4 : 2;
            c.beginPath();
            pts.forEach((s, k) => (k === 0 ? c.moveTo(xOf(s.V), yOf(s.P)) : c.lineTo(xOf(s.V), yOf(s.P))));
            c.stroke();
        });

        // Point labels
        c.font = 'bold 12px Inter';
        cyc.states.forEach((s, i) => {
            const x = xOf(s.V);
            const y = yOf(s.P);
            c.beginPath();
            c.arc(x, y, 8, 0, Math.PI * 2);
            c.fillStyle = '#1e293b';
            c.fill();
            c.fillStyle = 'white';
            c.fillText(String(i + 1), x, y + 4);
        });

        // Current state of the working gas
        const cs = currentState.value;
        c.beginPath();
        c.arc(xOf(cs.V), yOf(cs.P), 5, 0, Math.PI * 2);
        c.fillStyle = '#ffffff';
        c.fill();
    }

    // Energy flow diagram
    const flowX = 480;

    // Hot reservoir
    c.fillStyle = '#ef4444';
    c.fillRect(flowX, 60, 150, 60);
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText('Issiq manba', flowX + 75, 85);
    c.fillText(`TH = ${hotTemp.value} K`, flowX + 75, 105);

    // Engine
    c.fillStyle = '#22c55e';
    c.fillRect(flowX + 25, 180, 100, 80);
    c.fillStyle = 'white';
    c.fillText('Issiqlik', flowX + 75, 210);
    c.fillText('dvigatel', flowX + 75, 230);

    // Cold reservoir
    c.fillStyle = '#3b82f6';
    c.fillRect(flowX, 320, 150, 60);
    c.fillStyle = 'white';
    c.fillText('Sovuq manba', flowX + 75, 345);
    c.fillText(`TC = ${coldTemp.value} K`, flowX + 75, 365);

    // Arrows
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(flowX + 75, 120);
    c.lineTo(flowX + 75, 180);
    c.stroke();
    c.fillStyle = '#ef4444';
    c.fillText(`QH = ${qHot.value.toFixed(0)} J`, flowX + 75, 155);

    c.strokeStyle = '#3b82f6';
    c.beginPath();
    c.moveTo(flowX + 75, 260);
    c.lineTo(flowX + 75, 320);
    c.stroke();
    c.fillStyle = '#3b82f6';
    c.fillText(`QC = ${qCold.value.toFixed(0)} J`, flowX + 75, 295);

    // Work output
    c.strokeStyle = '#f59e0b';
    c.beginPath();
    c.moveTo(flowX + 125, 220);
    c.lineTo(flowX + 200, 220);
    c.stroke();
    c.fillStyle = '#f59e0b';
    c.fillText(`W = ${work.value.toFixed(0)} J`, flowX + 180, 205);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillStyle = colors[stage.value];
    c.font = 'bold 12px Inter';
    c.fillText(`Bosqich: ${stageNames[stage.value]}`, w - 205, 45);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText(`TH = ${hotTemp.value} K`, w - 205, 70);
    c.fillText(`TC = ${coldTemp.value} K`, w - 205, 88);
    c.fillText(`T (gaz) = ${currentState.value.T.toFixed(0)} K`, w - 205, 106);
    c.fillStyle = '#22c55e';
    c.font = 'bold 14px Inter';
    c.fillText(`η = ${(efficiency.value * 100).toFixed(1)}%`, w - 205, 128);

    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('η = 1 - TC/TH = W/QH', w - 205, 150);
    c.font = '10px Inter';
    c.fillText('1 mol gaz, γ = 5/3, V: 1→2 L', w - 205, 165);
};

watch([hotTemp, coldTemp], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Issiq TH: <strong>{{ hotTemp }} K</strong></label>
                <input type="range" v-model.number="hotTemp" :min="400" :max="800" :step="50" />
            </div>
            <div class="control-group">
                <label>Sovuq TC: <strong>{{ coldTemp }} K</strong></label>
                <input type="range" v-model.number="coldTemp" :min="200" :max="400" :step="50" />
            </div>
            <div class="efficiency">
                <span>Samaradorlik:</span>
                <strong>{{ (efficiency * 100).toFixed(1) }}%</strong>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
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

.efficiency {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #22c55e;
    border-radius: 8px;
}

.efficiency span {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

.efficiency strong {
    font-size: 1.25rem;
    color: white;
}
</style>
