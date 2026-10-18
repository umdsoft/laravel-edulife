<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { WATER, heatingCurve, heatingCurveBreakpoints, heatingCurveHeatFor } from '../physics/thermodynamics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const heatPower = ref(200);
const isHeating = ref(true);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const mass = 0.5;            // kg of water (ice at start)
const T_START = -20;         // °C
const T_END = 150;           // °C, experiment stops in the steam phase
const MAX_REAL_RUN = 60;     // s of real time for the whole curve (time-accelerated)

const curveParams = { mass, T0: T_START, ...WATER };
const breakpoints = heatingCurveBreakpoints(curveParams);     // 21 kJ, 188 kJ, 397.3 kJ, 1527.3 kJ
const Q_END = heatingCurveHeatFor(T_END, curveParams);         // 1577.3 kJ

const time = ref(0);        // simulated heating time [s]
const heatAdded = ref(0);   // Q = P·t [J]
let lastTs = null;

// State is the exact piecewise-analytic heating curve T(Q) (plateaus at 0 °C and 100 °C).
const curveState = computed(() => heatingCurve(heatAdded.value, curveParams));
const temperature = computed(() => curveState.value.T);
const phase = computed(() => curveState.value.phase); // solid, melting, liquid, boiling, gas

// The full curve takes Q_END/P (≈ 2.2 h at 200 W) → run it accelerated; Q and t shown are physical.
const timeScale = computed(() => Math.max(1, Math.ceil(Q_END / heatPower.value / MAX_REAL_RUN)));

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const scheduleFrame = () => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    lastTs = performance.now(); // clock starts when the user presses the button
    animationId.value = requestAnimationFrame(animate);
};

const start = () => {
    if (isRunning.value) return;
    if (heatAdded.value >= Q_END) return;
    isRunning.value = true;
    scheduleFrame();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    animationId.value = null;
    heatAdded.value = 0;
    time.value = 0;
    draw();
};

const animate = (ts) => {
    animationId.value = null;
    if (!isRunning.value || props.isPaused) return;
    time.value += Math.min(Math.max((ts - lastTs) / 1000, 0), 0.1) * timeScale.value;
    lastTs = ts;

    const tEnd = Q_END / heatPower.value;
    if (time.value >= tEnd) {
        time.value = tEnd;
        isRunning.value = false;
    }
    heatAdded.value = Math.min(Q_END, heatPower.value * time.value);

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

    // Container
    c.strokeStyle = '#64748b';
    c.lineWidth = 4;
    c.strokeRect(100, 150, 200, 200);

    // Content based on phase
    if (phase.value === 'solid') {
        c.fillStyle = '#93c5fd';
        c.fillRect(102, 250, 196, 98);
        // Ice crystals
        for (let i = 0; i < 8; i++) {
            c.fillStyle = 'rgba(255,255,255,0.5)';
            c.beginPath();
            c.arc(120 + i * 22, 290, 8, 0, Math.PI * 2);
            c.fill();
        }
    } else if (phase.value === 'melting') {
        // Water (bottom) and floating ice (top) in proportion to the melted fraction.
        const waterH = 98 * curveState.value.meltedFraction;
        c.fillStyle = '#3b82f6';
        c.fillRect(102, 348 - waterH, 196, waterH);
        c.fillStyle = '#93c5fd';
        c.fillRect(102, 250, 196, 98 - waterH);
    } else if (phase.value === 'liquid' || phase.value === 'boiling') {
        // Liquid level drops as water is vaporised during boiling.
        const liquidH = 98 * (1 - curveState.value.vaporizedFraction);
        c.fillStyle = '#3b82f6';
        c.fillRect(102, 348 - liquidH, 196, liquidH);

        if (phase.value === 'boiling' || temperature.value > 80) {
            // Bubbles
            for (let i = 0; i < 10; i++) {
                c.beginPath();
                c.arc(120 + Math.random() * 160, 260 + Math.random() * 80, 5 + Math.random() * 8, 0, Math.PI * 2);
                c.fillStyle = 'rgba(255,255,255,0.4)';
                c.fill();
            }
        }
    } else if (phase.value === 'gas') {
        // Steam
        for (let i = 0; i < 15; i++) {
            const x = 120 + Math.random() * 160;
            const y = 200 + Math.sin(time.value + i) * 50;
            c.beginPath();
            c.arc(x, y, 10 + Math.random() * 15, 0, Math.PI * 2);
            c.fillStyle = 'rgba(200,200,200,0.3)';
            c.fill();
        }
    }

    // Heat source
    if (isRunning.value && isHeating.value) {
        c.fillStyle = '#ef4444';
        c.fillRect(90, 355, 220, 15);
        for (let i = 0; i < 6; i++) {
            c.beginPath();
            c.moveTo(110 + i * 35, 355);
            c.lineTo(110 + i * 35 - 8, 335 - Math.random() * 15);
            c.lineTo(110 + i * 35 + 8, 335 - Math.random() * 15);
            c.fillStyle = '#f97316';
            c.fill();
        }
    }

    // Temperature graph
    const graphX = 400;
    const graphW = 350;
    const graphH = 300;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, 75, graphW, graphH);

    // Graph axes: x = delivered heat Q, y = temperature T (both linear)
    const plotL = graphX + 50;
    const plotR = graphX + graphW - 20;
    const plotTop = 100;
    const plotBottom = 350;
    const xOf = (q) => plotL + (q / Q_END) * (plotR - plotL);
    const yOf = (t) => plotBottom - ((t - T_START) / (T_END - T_START)) * (plotBottom - plotTop);

    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(plotL, plotTop);
    c.lineTo(plotL, plotBottom);
    c.lineTo(plotR, plotBottom);
    c.stroke();

    // Temperature markers
    const phases = [
        { temp: -20, label: 'Muz' },
        { temp: 0, label: 'Suyuqlanish' },
        { temp: 100, label: 'Qaynash' },
        { temp: 150, label: 'Bug\'' },
    ];

    c.fillStyle = '#94a3b8';
    c.font = '10px Inter';
    c.textAlign = 'right';
    phases.forEach(p => {
        const y = yOf(p.temp);
        c.fillText(`${p.temp}°C`, plotL - 5, y + 3);
        c.setLineDash([3, 3]);
        c.beginPath();
        c.moveTo(plotL, y);
        c.lineTo(plotR, y);
        c.stroke();
        c.setLineDash([]);
    });
    c.textAlign = 'center';
    c.fillText(`Q (kJ), jami ${(Q_END / 1000).toFixed(1)} kJ`, (plotL + plotR) / 2, plotBottom + 18);

    // Heating curve: exact piecewise-linear T(Q) through the breakpoints.
    const corners = [0, breakpoints.solidEnd, breakpoints.meltEnd, breakpoints.liquidEnd, breakpoints.boilEnd, Q_END];
    const traceCurve = (qMax) => {
        c.beginPath();
        c.moveTo(xOf(0), yOf(heatingCurve(0, curveParams).T));
        for (const q of corners) {
            if (q > qMax) break;
            c.lineTo(xOf(q), yOf(heatingCurve(q, curveParams).T));
        }
        c.lineTo(xOf(qMax), yOf(heatingCurve(qMax, curveParams).T));
        c.stroke();
    };
    c.strokeStyle = 'rgba(148, 163, 184, 0.35)';
    c.lineWidth = 1;
    traceCurve(Q_END);
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    traceCurve(heatAdded.value);
    c.beginPath();
    c.arc(xOf(heatAdded.value), yOf(temperature.value), 4, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Current state
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 180, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    const phaseNames = { solid: 'Qattiq (Muz)', melting: 'Suyuqlanish', liquid: 'Suyuq (Suv)', boiling: 'Qaynash', gas: 'Gaz (Bug\')' };
    c.fillText(`Holat: ${phaseNames[phase.value]}`, 35, 45);
    c.fillStyle = temperature.value < 0 ? '#3b82f6' : '#ef4444';
    c.fillText(`T = ${temperature.value.toFixed(1)} °C`, 35, 70);
    c.fillStyle = '#f59e0b';
    c.fillText(`Q = ${(heatAdded.value / 1000).toFixed(1)} kJ`, 35, 95);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s${timeScale.value > 1 ? ` (×${timeScale.value})` : ''}`, 35, 120);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) scheduleFrame(); });
// Q = P·t: changing P between runs would make the stored t inconsistent → restart.
watch(heatPower, () => { if (!isRunning.value) reset(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Quvvat: <strong>{{ heatPower }} W</strong></label>
                <input type="range" v-model.number="heatPower" :min="100" :max="500" :step="50" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🔥 Isitish</button>
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
    min-width: 150px;
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
    background: #ef4444;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
