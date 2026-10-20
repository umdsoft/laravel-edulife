<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { transformer, labDmmReading, UZ_MAINS } from '../physics/magnetism.js';
import { createRng, seedFrom } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const primaryTurns = ref(100);
const secondaryTurns = ref(200);
const primaryVoltage = ref(220); // RMS [V]
const loadResistance = ref(100); // Ω

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0); // elapsed animation time [s]
let lastTs = null;

// Ideal transformer: Vs/Vp = Ns/Np, Ip/Is = Ns/Np, Pin = Pout.
const tr = computed(() => transformer({
    primaryTurns: primaryTurns.value,
    secondaryTurns: secondaryTurns.value,
    primaryVoltage: primaryVoltage.value,
    loadResistance: loadResistance.value,
    efficiency: 1,
}));
const turnsRatio = computed(() => tr.value.ratio);
const secondaryVoltage = computed(() => tr.value.secondaryVoltage);
const isStepUp = computed(() => tr.value.type === 'step-up');
const isIsolation = computed(() => tr.value.type === 'isolation');

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

// AC voltmeter / ammeters: 3½-digit DMMs (auto-range: 200 V → 0.1 V, 2000 V → 1 V;
// 2 A → 1 mA, 20 A → 10 mA), σ = 0.05 %; the k-th reading of a set-up is reproducible.
const lastReading = ref(null);
const readCounts = new Map();
const measure = () => {
    const key = seedFrom('TransformerSimulation', primaryTurns.value, secondaryTurns.value, primaryVoltage.value, loadResistance.value);
    const k = readCounts.get(key) ?? 0;
    readCounts.set(key, k + 1);
    const rng = createRng(seedFrom(key, k));
    const u2 = labDmmReading(secondaryVoltage.value, rng);
    const i2 = labDmmReading(tr.value.secondaryCurrent, rng);
    const i1 = labDmmReading(tr.value.primaryCurrent, rng);
    lastReading.value = { u2, i2, i1 };
    emit('measurement', { name: 'Ikkilamchi kuchlanish', value: u2, unit: 'V' });
    emit('measurement', { name: 'Ikkilamchi tok', value: i2, unit: 'A' });
    emit('measurement', { name: 'Birlamchi tok', value: i1, unit: 'A' });
};
watch([primaryTurns, secondaryTurns, primaryVoltage, loadResistance], () => { lastReading.value = null; });

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused) time.value += dt;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

// Visual: the 50 Hz mains wave is drawn slowed down to ~0.5 Hz.
const VISUAL_OMEGA = 2 * Math.PI * 0.5;

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const centerX = w / 2;
    const centerY = h / 2;

    // Iron core
    c.fillStyle = '#475569';
    c.fillRect(centerX - 80, centerY - 100, 160, 30);
    c.fillRect(centerX - 80, centerY + 70, 160, 30);
    c.fillRect(centerX - 80, centerY - 100, 30, 200);
    c.fillRect(centerX + 50, centerY - 100, 30, 200);

    // Primary coil (left)
    const primaryX = centerX - 120;
    for (let i = 0; i < 8; i++) {
        const y = centerY - 60 + i * 15;
        c.strokeStyle = '#f59e0b';
        c.lineWidth = 4;
        c.beginPath();
        c.ellipse(primaryX, y, 25, 8, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Secondary coil (right) — drawn turns proportional to Ns/Np (8 drawn for the primary)
    const secondaryX = centerX + 120;
    const numSecondary = Math.max(2, Math.min(16, Math.round(8 * turnsRatio.value)));
    for (let i = 0; i < numSecondary; i++) {
        const y = centerY - 70 + i * (140 / numSecondary);
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 4;
        c.beginPath();
        c.ellipse(secondaryX, y, 25, 8, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Magnetic flux animation
    const fluxPhase = time.value * VISUAL_OMEGA;
    c.strokeStyle = `rgba(239, 68, 68, ${0.3 + 0.2 * Math.sin(fluxPhase)})`;
    c.lineWidth = 2;
    for (let i = 0; i < 4; i++) {
        const offset = i * 20;
        c.setLineDash([10 + Math.sin(fluxPhase + i) * 5, 5]);
        c.beginPath();
        c.moveTo(centerX - 40, centerY - 80 + offset);
        c.lineTo(centerX + 40, centerY - 80 + offset);
        c.stroke();
    }
    c.setLineDash([]);

    // Input AC symbol
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText(`AC ${UZ_MAINS.frequency} Hz`, primaryX - 60, centerY - 20);
    c.fillText(`${primaryVoltage.value}V`, primaryX - 60, centerY + 5);

    // AC waves share one amplitude scale: the larger of Vp, Vs is drawn 25 px high.
    const ampScale = 25 / Math.max(primaryVoltage.value, secondaryVoltage.value, 1e-9);
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < 40; x++) {
        const y = centerY + 40 - Math.sin((x / 5) + time.value * VISUAL_OMEGA) * primaryVoltage.value * ampScale;
        if (x === 0) c.moveTo(primaryX - 80 + x, y);
        else c.lineTo(primaryX - 80 + x, y);
    }
    c.stroke();

    // Output
    c.fillStyle = 'white';
    c.fillText('AC', secondaryX + 60, centerY - 20);
    c.fillStyle = isIsolation.value ? '#94a3b8' : isStepUp.value ? '#22c55e' : '#ef4444';
    c.fillText(`${secondaryVoltage.value.toFixed(1)}V`, secondaryX + 60, centerY + 5);

    // Output wave (in phase with the input for an ideal transformer)
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < 40; x++) {
        const y = centerY + 40 - Math.sin((x / 5) + time.value * VISUAL_OMEGA) * secondaryVoltage.value * ampScale;
        if (x === 0) c.moveTo(secondaryX + 40 + x, y);
        else c.lineTo(secondaryX + 40 + x, y);
    }
    c.stroke();

    // Labels
    c.font = '12px Inter';
    c.fillStyle = '#f59e0b';
    c.fillText(`N₁ = ${primaryTurns.value}`, primaryX, centerY + 100);
    c.fillStyle = '#3b82f6';
    c.fillText(`N₂ = ${secondaryTurns.value}`, secondaryX, centerY + 100);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Birlamchi o'ramlar: ${primaryTurns.value}`, 35, 45);
    c.fillText(`Ikkilamchi o'ramlar: ${secondaryTurns.value}`, 35, 65);
    c.fillText(`Nisbat N₂/N₁: ${turnsRatio.value.toFixed(2)}`, 35, 85);
    c.fillStyle = '#f59e0b';
    c.fillText(`V₁ = ${primaryVoltage.value} V`, 35, 110);
    c.fillStyle = '#3b82f6';
    c.fillText(`V₂ = ${secondaryVoltage.value.toFixed(1)} V`, 35, 130);

    // Currents & power (ideal transformer with resistive load R)
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 230, 20, 210, 130);
    c.fillStyle = '#cbd5e1';
    c.fillText(`Yuklama: R = ${loadResistance.value} Ω`, w - 215, 45);
    c.fillStyle = '#3b82f6';
    c.fillText(`I₂ = V₂/R = ${tr.value.secondaryCurrent.toFixed(3)} A`, w - 215, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`I₁ = I₂·N₂/N₁ = ${tr.value.primaryCurrent.toFixed(3)} A`, w - 215, 85);
    c.fillStyle = '#ffffff';
    c.fillText(`P₁ = ${tr.value.inputPower.toFixed(1)} W, P₂ = ${tr.value.outputPower.toFixed(1)} W`, w - 215, 108);
    c.fillStyle = '#94a3b8';
    c.fillText('Ideal: η = P₂/P₁ = 100 %', w - 215, 130);

    // Meter readings (after "O'lchash")
    const r = lastReading.value;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, h - 95, 200, 75);
    c.fillStyle = '#22c55e';
    c.font = '11px Inter';
    if (!r) c.fillText("Asboblar: — (O'lchash)", 35, h - 72);
    else {
        c.fillText(`V: U₂ = ${r.u2} V`, 35, h - 72);
        c.fillText(`A: I₂ = ${r.i2} A`, 35, h - 54);
        c.fillText(`A: I₁ = ${r.i1} A`, 35, h - 36);
    }

    // Type label
    c.fillStyle = isIsolation.value ? '#94a3b8' : isStepUp.value ? '#22c55e' : '#ef4444';
    c.font = 'bold 14px Inter';
    c.textAlign = 'center';
    c.fillText(isIsolation.value ? '↔ 1:1 (ajratuvchi)' : isStepUp.value ? '⬆️ Ko\'taruvchi' : '⬇️ Tushiruvchi', centerX, h - 30);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>N₁: <strong>{{ primaryTurns }}</strong></label>
                <input type="range" v-model.number="primaryTurns" :min="50" :max="300" :step="25" />
            </div>
            <div class="control-group">
                <label>N₂: <strong>{{ secondaryTurns }}</strong></label>
                <input type="range" v-model.number="secondaryTurns" :min="50" :max="500" :step="25" />
            </div>
            <div class="control-group">
                <label>V₁: <strong>{{ primaryVoltage }} V</strong></label>
                <input type="range" v-model.number="primaryVoltage" :min="110" :max="380" :step="10" />
            </div>
            <div class="control-group">
                <label>R: <strong>{{ loadResistance }} Ω</strong></label>
                <input type="range" v-model.number="loadResistance" :min="10" :max="1000" :step="10" />
            </div>
            <button @click="measure" class="btn" title="Voltmetr va ampermetrlar bilan o'lchash">📏 O'lchash</button>
        </div>
        <div class="formula">V₂/V₁ = N₂/N₁</div>
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}

.formula {
    font-family: serif;
    font-size: 1.1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
