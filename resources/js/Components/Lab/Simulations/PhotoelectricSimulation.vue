<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    photoelectric, photocurrent, photonRate, frequencyToWavelength, wavelengthToRGB, nearestMetal,
} from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const lightIntensity = ref(50);
const frequency = ref(600); // THz
const metalWorkFunction = ref(2); // eV

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0); // elapsed time [s]
let lastTs = null;

// Light power on the cathode: 100 % = 1 mW; quantum efficiency 10⁻³ (typical metal).
const MAX_POWER = 1e-3; // W
const QUANTUM_EFFICIENCY = 1e-3;

const freqHz = computed(() => frequency.value * 1e12);
const pe = computed(() => photoelectric({ frequency: freqHz.value, workFunctionEV: metalWorkFunction.value }));
const photonEnergy = computed(() => pe.value.photonEnergyEV); // eV  (E = hf)
const hasEmission = computed(() => pe.value.emits); // only if hf > φ, independent of intensity
const electronKE = computed(() => pe.value.kineticEnergyMaxEV); // KEmax = hf − φ  [eV]
const thresholdFreq = computed(() => pe.value.thresholdFrequency / 1e12); // THz
const lightPower = computed(() => (lightIntensity.value / 100) * MAX_POWER);
const current = computed(() => photocurrent({
    power: lightPower.value, frequency: freqHz.value, workFunctionEV: metalWorkFunction.value, quantumEfficiency: QUANTUM_EFFICIENCY,
})); // A
const wavelengthNm = computed(() => frequencyToWavelength(freqHz.value) * 1e9);
const metalName = computed(() => nearestMetal(metalWorkFunction.value));

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const frequencyToColor = (fTHz) => {
    const wl = frequencyToWavelength(fTHz * 1e12) * 1e9;
    return wavelengthToRGB(wl) ?? (wl < 380 ? '#a855f7' : '#7f1d1d');
};

// ── Electrons: deterministic, closed-form in t (no per-frame integration) ──
// Emission rate on screen ∝ photon rate Ṅ = P/(hf) (6 e⁻/s at 50 %, 600 THz).
const REF_RATE = photonRate(0.5 * MAX_POWER, 600e12);
const emissionRate = computed(() => 6 * photonRate(lightPower.value, freqHz.value) / REF_RATE);
let emissionStart = 0;
const hash = (k, s) => { const x = Math.sin(k * 12.9898 + s * 78.233) * 43758.5453; return x - Math.floor(x); };
const PLATE_X = 330;
const MIN_VX = 40; // px/s

const visibleElectrons = () => {
    if (!hasEmission.value || !(emissionRate.value > 0)) return [];
    const w = canvasConfig.value.width;
    const elapsed = time.value - emissionStart;
    const maxLife = (w - 80 - PLATE_X) / MIN_VX;
    const rate = emissionRate.value;
    const kMax = Math.floor(elapsed * rate);
    const kMin = Math.max(0, Math.floor((elapsed - maxLife) * rate));
    const list = [];
    for (let k = kMin; k <= kMax; k++) {
        const age = elapsed - k / rate;
        if (age < 0) continue;
        // Each electron gets KE ∈ [0, KEmax]; on-screen speed ∝ v = √(2KE/m)
        const ke = hash(k, 2) * electronKE.value;
        const vx = MIN_VX + 160 * Math.sqrt(ke);
        const vy = (hash(k, 3) - 0.5) * 90;
        const x = PLATE_X + vx * age;
        const y = 250 + (hash(k, 1) - 0.5) * 100 + vy * age;
        if (x < w - 80 && y > 0 && y < canvasConfig.value.height) list.push({ x, y });
    }
    return list;
};

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused) time.value += dt;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Light source
    const lightColor = frequencyToColor(frequency.value);

    // Photon beams
    for (let i = 0; i < 5; i++) {
        const offset = ((time.value * 200 + i * 60) % 200);
        const alpha = lightIntensity.value / 100;

        c.strokeStyle = lightColor;
        c.globalAlpha = alpha * (1 - offset / 200);
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(50 + offset, 150 + i * 30);
        c.lineTo(300, 250);
        c.stroke();
    }
    c.globalAlpha = 1;

    // Light source icon
    c.beginPath();
    c.arc(50, 250, 30, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText('hf', 50, 255);

    // Metal plate
    c.fillStyle = '#475569';
    c.fillRect(300, 150, 30, 200);
    c.fillStyle = '#64748b';
    c.fillRect(300, 150, 30, 10);
    c.fillRect(300, 340, 30, 10);

    // Electrons
    visibleElectrons().forEach(e => {
        c.beginPath();
        c.arc(e.x, e.y, 5, 0, Math.PI * 2);
        c.fillStyle = '#22c55e';
        c.fill();
    });

    // Collector plate
    c.fillStyle = '#334155';
    c.fillRect(w - 80, 150, 20, 200);

    // Circuit elements
    c.strokeStyle = '#64748b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(330, 350);
    c.lineTo(330, 400);
    c.lineTo(w - 70, 400);
    c.lineTo(w - 70, 350);
    c.stroke();

    // Ammeter
    c.beginPath();
    c.arc(w / 2, 400, 20, 0, Math.PI * 2);
    c.fillStyle = '#1e293b';
    c.fill();
    c.strokeStyle = '#3b82f6';
    c.stroke();
    c.fillStyle = '#3b82f6';
    c.font = '10px Inter';
    c.textAlign = 'center';
    c.fillText('A', w / 2, 404);
    c.font = '11px Inter';
    c.fillText(`I = ${(current.value * 1e6).toFixed(3)} µA`, w / 2, 438);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 260, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Chastota: ${frequency.value} THz (λ = ${wavelengthNm.value.toFixed(0)} nm)`, 35, 40);
    c.fillText(`Foton energiyasi: hf = ${photonEnergy.value.toFixed(3)} eV`, 35, 57);
    c.fillText(`Ish funksiyasi W = ${metalWorkFunction.value} eV${metalName.value ? ` (≈ ${metalName.value})` : ''}`, 35, 74);
    c.fillText(`Chegaraviy ν₀ = W/h = ${thresholdFreq.value.toFixed(0)} THz`, 35, 91);

    c.fillStyle = hasEmission.value ? '#22c55e' : '#ef4444';
    c.fillText(hasEmission.value ? '✓ Emissiya mavjud (hf > W)' : '✗ Emissiya yo\'q (hf ≤ W)', 35, 112);

    if (hasEmission.value) {
        c.fillStyle = '#f59e0b';
        c.fillText(`KE_max = hf − W = ${electronKE.value.toFixed(3)} eV`, 35, 130);
        c.fillText(`U₀ = KE_max/e = ${pe.value.stoppingVoltage.toFixed(3)} V`, 35, 147);
        c.fillText(`v_max = ${(pe.value.maxSpeed / 1e3).toFixed(0)} km/s`, 35, 164);
    } else {
        c.fillStyle = '#94a3b8';
        c.fillText('Intensivlik oshirilsa ham chiqmaydi', 35, 130);
    }

    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('KE = hf - W,  eU₀ = KE_max', 35, 188);
};

watch([lightIntensity, frequency, metalWorkFunction], () => { emissionStart = time.value; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency }} THz</strong></label>
                <input type="range" v-model.number="frequency" :min="400" :max="800" :step="20" />
            </div>
            <div class="control-group">
                <label>Intensivlik: <strong>{{ lightIntensity }}%</strong></label>
                <input type="range" v-model.number="lightIntensity" :min="10" :max="100" :step="10" />
            </div>
            <div class="control-group">
                <label>Ish funksiyasi: <strong>{{ metalWorkFunction }} eV</strong></label>
                <input type="range" v-model.number="metalWorkFunction" :min="1" :max="5" :step="0.5" />
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
</style>
