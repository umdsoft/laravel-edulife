<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    R, entropyIsothermal, entropyIsobaricHeating, adiabaticTemperature, isobarTemperatureAtEntropy,
} from '../physics/thermodynamics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Working body: 1 mol of monatomic ideal gas.
const N_MOL = 1;
const CP = 2.5 * R;              // J/(mol·K)
const GAMMA = 5 / 3;
const ADIABATIC_COMPRESSION = 2; // reversible adiabatic compression V → V/2

const processType = ref('isothermal');
const initialTemp = ref(300);    // K
const heatAdded = ref(500);      // J (slider)

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

// Heat actually exchanged: an adiabatic process has Q = 0 by definition.
const heatExchanged = computed(() => (processType.value === 'adiabatic' ? 0 : heatAdded.value));

// Analytic results of the process:
//  isothermal: ΔS = Q/T,                         T₂ = T₁
//  isobaric:   T₂ = T₁ + Q/(nCp), ΔS = nCp ln(T₂/T₁)
//  adiabatic (reversible): ΔS = 0,               T₂ = T₁ (V₁/V₂)^(γ−1)
const process = computed(() => {
    const T1 = initialTemp.value;
    if (processType.value === 'isothermal') {
        return { dS: entropyIsothermal(heatExchanged.value, T1), T2: T1 };
    }
    if (processType.value === 'isobaric') {
        const r = entropyIsobaricHeating(N_MOL, CP, T1, heatExchanged.value);
        return { dS: r.dS, T2: r.T2 };
    }
    return { dS: 0, T2: adiabaticTemperature(T1, 1, 1 / ADIABATIC_COMPRESSION, GAMMA) };
});
const entropyChange = computed(() => process.value.dS);
const finalTemp = computed(() => process.value.T2);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

// Decorative particle jitter driven by real elapsed time (1.2 units/s as before at 60 fps).
let lastTs = null;
const animate = (ts) => {
    if (lastTs !== null) time.value += Math.min((ts - lastTs) / 1000, 0.1) * 1.2;
    lastTs = ts;
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

    // TS Diagram
    const graphX = 50;
    const graphY = 50;
    const graphW = 400;
    const graphH = 300;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 50, graphY + graphH - 40);
    c.lineTo(graphX + 50, graphY + 20);
    c.lineTo(graphX + graphW - 20, graphY + 20);
    c.stroke();

    // Axes scales: horizontal = ΔS from the initial state (absolute S is arbitrary),
    // vertical = T in kelvin from 200 K (axis line) to 800 K.
    const axisY = graphY + graphH - 40;
    const S_PX = 50;                    // px per J/K (ΔS ≤ 5 J/K fits)
    const T_MIN_AXIS = 200;
    const T_PX = 240 / 600;             // 200…800 K → 240 px
    const scaleS = (dS) => graphX + 80 + dS * S_PX;
    const scaleT = (t) => axisY - (t - T_MIN_AXIS) * T_PX;

    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('ΔS (J/K)', graphX + graphW - 40, graphY + 35);
    c.fillText('T (K)', graphX + 30, graphY + graphH - 50);
    c.font = '10px Inter';
    for (let s = 0; s <= 5; s++) c.fillText(String(s), scaleS(s), axisY + 14);
    c.textAlign = 'right';
    for (const t of [200, 500, 800]) c.fillText(String(t), graphX + 46, scaleT(t) + 3);
    c.textAlign = 'center';

    // Process visualization
    const startT = initialTemp.value;
    const dS = entropyChange.value;
    const endT = finalTemp.value;

    // Process path, sampled from the analytic T(S) of each process
    const path = [];
    if (processType.value === 'isothermal') {
        path.push({ s: 0, t: startT }, { s: dS, t: startT });
    } else if (processType.value === 'isobaric') {
        const N = 40;
        for (let k = 0; k <= N; k++) {
            const s = (dS * k) / N;
            path.push({ s, t: isobarTemperatureAtEntropy(startT, s, N_MOL, CP) });
        }
    } else {
        path.push({ s: 0, t: startT }, { s: 0, t: endT });
    }

    c.strokeStyle = '#22c55e';
    c.lineWidth = 3;
    c.beginPath();
    path.forEach((p, k) => (k === 0 ? c.moveTo(scaleS(p.s), scaleT(p.t)) : c.lineTo(scaleS(p.s), scaleT(p.t))));
    c.stroke();

    // Area under the curve (Q = ∫T dS, shaded down to the axis)
    if (dS > 0) {
        c.fillStyle = 'rgba(34, 197, 94, 0.3)';
        c.beginPath();
        c.moveTo(scaleS(0), axisY);
        path.forEach(p => c.lineTo(scaleS(p.s), scaleT(p.t)));
        c.lineTo(scaleS(dS), axisY);
        c.closePath();
        c.fill();

        c.fillStyle = '#22c55e';
        c.font = '14px Inter';
        c.fillText(processType.value === 'isothermal' ? 'Q = T·ΔS' : 'Q = ∫T dS', scaleS(dS / 2), scaleT(startT) + 30);
    }

    // Entropy visualization (particles)
    const particleArea = { x: 550, y: 100, w: 300, h: 200 };

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(particleArea.x, particleArea.y, particleArea.w, particleArea.h);
    c.strokeStyle = '#475569';
    c.strokeRect(particleArea.x, particleArea.y, particleArea.w, particleArea.h);

    // Draw particles with disorder based on entropy
    const numParticles = 30;
    const disorder = entropyChange.value / 2;

    for (let i = 0; i < numParticles; i++) {
        const baseX = particleArea.x + 40 + (i % 6) * 40;
        const baseY = particleArea.y + 30 + Math.floor(i / 6) * 35;

        const offsetX = Math.sin(time.value * 3 + i) * disorder * 10;
        const offsetY = Math.cos(time.value * 2 + i * 0.5) * disorder * 10;

        c.beginPath();
        c.arc(baseX + offsetX, baseY + offsetY, 8, 0, Math.PI * 2);
        c.fillStyle = `hsl(${200 + disorder * 50}, 70%, 50%)`;
        c.fill();
    }

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('Molekulalar tarqalishi', particleArea.x + particleArea.w / 2, particleArea.y - 10);
    c.fillText(entropyChange.value > 0 ? '↑ Ko\'proq tartibsizlik' : '= Tartibsizlik o\'zgarmaydi',
        particleArea.x + particleArea.w / 2, particleArea.y + particleArea.h + 25);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(550, 320, 300, 110);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Jarayon: ${processType.value === 'isothermal' ? 'Izotermik' :
        processType.value === 'isobaric' ? 'Izobarik' : 'Adiabatik'}`, 565, 345);
    c.fillText(processType.value === 'isothermal'
        ? `T = ${initialTemp.value} K`
        : `T₁ = ${initialTemp.value} K → T₂ = ${endT.toFixed(1)} K`, 565, 365);
    c.fillText(`Q = ${heatExchanged.value} J`, 565, 385);
    c.fillStyle = '#22c55e';
    c.font = 'bold 14px Inter';
    c.fillText(`ΔS = ${entropyChange.value.toFixed(3)} J/K`, 565, 410);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    const formula = processType.value === 'isothermal' ? 'ΔS = Q/T'
        : processType.value === 'isobaric' ? 'ΔS = nCp·ln(T₂/T₁)' : 'ΔS = 0 (Q = 0)';
    c.fillText(formula, 700, 410);
};

watch([processType, initialTemp, heatAdded], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Jarayon turi:</label>
                <select v-model="processType">
                    <option value="isothermal">Izotermik</option>
                    <option value="isobaric">Izobarik</option>
                    <option value="adiabatic">Adiabatik</option>
                </select>
            </div>
            <div class="control-group">
                <label>Harorat: <strong>{{ initialTemp }} K</strong></label>
                <input type="range" v-model.number="initialTemp" :min="200" :max="500" :step="50" />
            </div>
            <div class="control-group">
                <label>Issiqlik: <strong>{{ heatAdded }} J</strong></label>
                <input type="range" v-model.number="heatAdded" :min="100" :max="1000" :step="100"
                    :disabled="processType === 'adiabatic'" />
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
    min-width: 130px;
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
</style>
