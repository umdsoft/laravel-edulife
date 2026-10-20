<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    toRad, toDeg, brewsterAngle as brewster, fresnelReflectance, snellRefractionAngle,
    analyzerIntensity, degreeOfPolarization, photometerReading, formatPhotometer, REFRACTIVE_INDEX,
} from '../physics/optics.js';
import { createRng, seedFrom } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const N1 = 1.0; // air (1.000293 — the 0.03 % difference is far below every tolerance)
const N2 = REFRACTIVE_INDEX.glass; // school-textbook glass n = 1.5 (BK7: 1.5167 → θB = 56.6°)

const angle = ref(0); // angle of incidence, degrees from the normal
const analyzerAngle = ref(0); // analyser axis measured from the s-direction (⊥ plane of incidence), degrees

const canvas = ref(null);
const ctx = ref(null);

// tan θB = n2/n1
const brewsterAngle = computed(() => toDeg(brewster(N1, N2)));
// Fresnel power reflectances for s and p light
const reflectedIntensity = computed(() => {
    const f = fresnelReflectance(N1, N2, toRad(angle.value));
    return { s: f.Rs, p: f.Rp };
});
// Unpolarised incident light I0: half of it is s, half p. Reflected components:
// Is = I0·Rs/2, Ip = I0·Rp/2 (incoherent). Analyser (Malus for each): I = Is cos²φ + Ip sin²φ.
const reflectedS = computed(() => reflectedIntensity.value.s / 2);
const reflectedP = computed(() => reflectedIntensity.value.p / 2);
const polarizationDegree = computed(() => degreeOfPolarization(reflectedS.value, reflectedP.value));
const analyzerTransmitted = computed(() => analyzerIntensity(reflectedS.value, reflectedP.value, toRad(analyzerAngle.value))); // in units of I0
const nearBrewster = computed(() => Math.abs(angle.value - brewsterAngle.value) < 1);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

// A photodiode behind the analyser reads the intensity in % of the incident beam
// (4½-digit auto-ranging meter, σ = 0.05 % of the reading, seeded per set-up).
// Degree of polarisation as in a real lab: analyser turned to the maximum (φ = 0°,
// s-axis) and to the minimum (φ = 90°), P = (I_max − I_min)/(I_max + I_min).
const readCounters = new Map();
const photometer = (percent, key) => {
    const seed = seedFrom('PolarizationSimulation', ...key);
    const k = readCounters.get(seed) ?? 0;
    readCounters.set(seed, k + 1);
    return photometerReading(percent, createRng(seedFrom(seed, k)));
};
const intensityAt = (phiDeg) => analyzerIntensity(reflectedS.value, reflectedP.value, toRad(phiDeg)) * 100; // %
const lastReading = ref(null);

const measure = () => {
    const iMax = photometer(intensityAt(0), [angle.value, 0]);
    const iMin = photometer(intensityAt(90), [angle.value, 90]);
    const degree = iMax + iMin > 0 ? ((iMax - iMin) / (iMax + iMin)) * 100 : 0;
    const iPhi = photometer(intensityAt(analyzerAngle.value), [angle.value, analyzerAngle.value, 'φ']);
    lastReading.value = { iMax, iMin, degree, iPhi };
    emit('measurement', { name: `Analizator φ=0°: I_max/I₀ (θ=${angle.value}°)`, value: formatPhotometer(iMax), unit: '%' });
    emit('measurement', { name: `Analizator φ=90°: I_min/I₀ (θ=${angle.value}°)`, value: formatPhotometer(iMin), unit: '%' });
    emit('measurement', { name: `Qaytgan nur qutblanish darajasi (θ=${angle.value}°)`, value: degree.toFixed(2), unit: '%' });
    emit('measurement', { name: `Analizatordan o'tgan I/I₀ (θ=${angle.value}°, φ=${analyzerAngle.value}°)`, value: formatPhotometer(iPhi), unit: '%' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerX = 350;
    const centerY = 250;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Glass surface
    c.fillStyle = 'rgba(59, 130, 246, 0.2)';
    c.fillRect(100, centerY, 500, 150);

    // Normal line
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(centerX, centerY - 180);
    c.lineTo(centerX, centerY + 100);
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#64748b';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('Normal', centerX, centerY - 190);

    const angleRad = toRad(angle.value);
    const rayLen = 150;

    // Incident ray
    const incX = centerX - rayLen * Math.sin(angleRad);
    const incY = centerY - rayLen * Math.cos(angleRad);

    c.strokeStyle = '#ffffff';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(incX, incY);
    c.lineTo(centerX, centerY);
    c.stroke();

    // Polarization indicator on incident ray (unpolarised: both s ⊙ and p ↔ components)
    c.fillStyle = '#f59e0b';
    const midX = (incX + centerX) / 2;
    const midY = (incY + centerY) / 2;
    c.beginPath();
    c.arc(midX, midY, 8, 0, Math.PI * 2);
    c.fill();
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(midX - 15, midY);
    c.lineTo(midX + 15, midY);
    c.moveTo(midX, midY - 15);
    c.lineTo(midX, midY + 15);
    c.stroke();
    c.fillStyle = '#f59e0b';
    c.font = '11px Inter';
    c.fillText('Qutblanmagan', midX, midY - 25);

    // Reflected ray (partially polarized), brightness ∝ unpolarised reflectance
    const refX = centerX + rayLen * Math.sin(angleRad);
    const refY = centerY - rayLen * Math.cos(angleRad);

    const reflectAlpha = (reflectedIntensity.value.s + reflectedIntensity.value.p) / 2;
    c.strokeStyle = `rgba(34, 197, 94, ${0.3 + reflectAlpha * 0.7})`;
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(refX, refY);
    c.stroke();

    // At Brewster's angle the reflected light is purely s-polarised: E ⊥ plane of
    // incidence, drawn as dots (⊙) on the ray.
    const refMidX = (refX + centerX) / 2;
    const refMidY = (refY + centerY) / 2;
    if (nearBrewster.value) {
        c.fillStyle = '#22c55e';
        const ux = Math.sin(angleRad);
        const uy = -Math.cos(angleRad);
        for (const k of [-24, -8, 8, 24]) {
            c.beginPath();
            c.arc(refMidX + k * ux, refMidY + k * uy, 4, 0, Math.PI * 2);
            c.fill();
        }
        c.textAlign = 'left';
        c.fillText('S-qutblangan (⊙)', refMidX + 20, refMidY + 10);
        c.textAlign = 'center';
    }

    // Transmitted ray, Snell n1 sinθ1 = n2 sinθ2
    const transAngleRad = snellRefractionAngle(N1, N2, angleRad);
    const transX = centerX + 120 * Math.sin(transAngleRad);
    const transY = centerY + 120 * Math.cos(transAngleRad);

    c.strokeStyle = 'rgba(59, 130, 246, 0.8)';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(transX, transY);
    c.stroke();

    // Brewster angle marker
    c.strokeStyle = '#f59e0b';
    c.setLineDash([3, 3]);
    c.lineWidth = 1;
    const brewRad = toRad(brewsterAngle.value);
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(centerX - 100 * Math.sin(brewRad), centerY - 100 * Math.cos(brewRad));
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText(`θB = ${brewsterAngle.value.toFixed(1)}°`, centerX - 120 * Math.sin(brewRad), centerY - 120 * Math.cos(brewRad));

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 240, 20, 220, 225);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tushish burchagi: ${angle.value}°`, w - 225, 45);
    c.fillText(`Bryuster burchagi: ${brewsterAngle.value.toFixed(1)}°`, w - 225, 65);
    c.fillStyle = '#22c55e';
    c.fillText(`S-qaytish: ${(reflectedIntensity.value.s * 100).toFixed(1)}%`, w - 225, 90);
    c.fillStyle = '#3b82f6';
    c.fillText(`P-qaytish: ${(reflectedIntensity.value.p * 100).toFixed(2)}%`, w - 225, 110);
    c.fillStyle = '#ffffff';
    c.fillText(`Qutblanish darajasi: ${(polarizationDegree.value * 100).toFixed(1)}%`, w - 225, 130);
    c.fillText(`Analizator φ = ${analyzerAngle.value}°:`, w - 225, 155);
    c.fillText(`I/I₀ = ${(analyzerTransmitted.value * 100).toFixed(2)}%`, w - 225, 173);

    if (nearBrewster.value) {
        c.fillStyle = '#f59e0b';
        c.font = 'bold 12px Inter';
        c.fillText('⚡ Bryuster burchagida!', w - 225, 200);
        c.font = '11px Inter';
        c.fillText('P-to\'lqin (deyarli) qaytmaydi', w - 225, 220);
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('I = Is·cos²φ + Ip·sin²φ (Malus)', w - 225, 238);
};

watch([angle, analyzerAngle], () => { lastReading.value = null; draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tushish burchagi: <strong>{{ angle }}°</strong></label>
                <input type="range" v-model.number="angle" :min="0" :max="85" :step="1" />
            </div>
            <div class="control-group">
                <label>Analizator burchagi: <strong>{{ analyzerAngle }}°</strong></label>
                <input type="range" v-model.number="analyzerAngle" :min="0" :max="180" :step="5" />
            </div>
            <div class="info">
                <span>tan θB = n₂/n₁</span>
            </div>
            <button @click="measure" class="btn">📏 O'lchash</button>
            <div v-if="lastReading" class="control-group">
                <label>Fotometr: <strong>I_max = {{ formatPhotometer(lastReading.iMax) }} %, I_min = {{ formatPhotometer(lastReading.iMin) }} %</strong></label>
                <label>P = <strong>{{ lastReading.degree.toFixed(2) }} %</strong>, I(φ) = <strong>{{ formatPhotometer(lastReading.iPhi) }} %</strong></label>
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
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.info {
    color: #94a3b8;
    font-family: serif;
    font-size: 1.1rem;
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
