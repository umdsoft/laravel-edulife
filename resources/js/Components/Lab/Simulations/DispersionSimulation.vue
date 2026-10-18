<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    NM, toRad, toDeg, cauchyIndex, prismDeviation, prismMinimumDeviationIncidence,
    refractDirection, reflectDirection,
} from '../physics/optics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const prismAngle = ref(60); // apex angle A, degrees
const showColors = ref(true);

const canvas = ref(null);
const ctx = ref(null);

// Spectral samples; the refractive index of each is computed from the Cauchy formula
// n(λ) = A + B/λ² with BK7 crown-glass constants (see physics/optics.js).
const colors = [
    { name: 'Qizil', wavelength: 700, color: '#ef4444' },
    { name: 'To\'q sariq', wavelength: 620, color: '#f97316' },
    { name: 'Sariq', wavelength: 580, color: '#eab308' },
    { name: 'Yashil', wavelength: 530, color: '#22c55e' },
    { name: 'Ko\'k', wavelength: 470, color: '#3b82f6' },
    { name: 'Binafsha', wavelength: 400, color: '#8b5cf6' },
].map((col) => ({ ...col, n: cauchyIndex(col.wavelength * NM) }));

// The prism is set at minimum deviation for the sodium D line (589.3 nm), as on a
// spectrometer; every colour is then traced exactly with Snell's law at both faces.
const REFERENCE_WAVELENGTH_NM = 589.3;
const FALLBACK_INCIDENCE_DEG = 80; // used when no ray can emerge (A > 2θc)

const apexRad = computed(() => toRad(prismAngle.value));
const incidenceRad = computed(() => {
    const t = prismMinimumDeviationIncidence(cauchyIndex(REFERENCE_WAVELENGTH_NM * NM), apexRad.value);
    return t === null ? toRad(FALLBACK_INCIDENCE_DEG) : t;
});
// δ = θ1 + θ2' − A for every colour (null = total internal reflection at the second face)
const deviations = computed(() => colors.map((col) => {
    const p = prismDeviation(col.n, apexRad.value, incidenceRad.value);
    return p === null ? null : toDeg(p.deviation);
}));
const deltaN = computed(() => colors[5].n - colors[0].n);
const angularDispersion = computed(() => {
    const [r, v] = [deviations.value[0], deviations.value[5]];
    return r === null || v === null ? null : v - r;
});

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Dispersiya n(400 nm) − n(700 nm)', value: deltaN.value.toFixed(4), unit: 'Δn' });
    if (angularDispersion.value !== null) {
        emit('measurement', { name: `Burchak dispersiyasi δ_bin − δ_qiz (A=${prismAngle.value}°)`, value: angularDispersion.value.toFixed(3), unit: '°' });
    }
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Prism: isosceles triangle with apex angle A, apex up, base horizontal
    const apex = [370, 110];
    const side = 230;
    const half = apexRad.value / 2;
    const eL = [-Math.sin(half), Math.cos(half)]; // along the left face (apex → base)
    const eR = [Math.sin(half), Math.cos(half)]; // along the right face
    const BL = [apex[0] + side * eL[0], apex[1] + side * eL[1]];
    const BR = [apex[0] + side * eR[0], apex[1] + side * eR[1]];

    c.beginPath();
    c.moveTo(apex[0], apex[1]);
    c.lineTo(BL[0], BL[1]);
    c.lineTo(BR[0], BR[1]);
    c.closePath();

    const prismGrad = c.createLinearGradient(BL[0], apex[1], BR[0], apex[1]);
    prismGrad.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    prismGrad.addColorStop(0.5, 'rgba(139, 92, 246, 0.3)');
    prismGrad.addColorStop(1, 'rgba(59, 130, 246, 0.3)');
    c.fillStyle = prismGrad;
    c.fill();
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.stroke();

    // Face normals (canvas frame, y down)
    const nLeftOut = [-Math.cos(half), -Math.sin(half)];
    const nRightIn = [-Math.cos(half), Math.sin(half)];

    // Incident white ray hits the middle of the left face at angle θ1 from its normal
    const pIn = [apex[0] + 0.5 * side * eL[0], apex[1] + 0.5 * side * eL[1]];
    const phiIn = half - incidenceRad.value;
    const d0 = [Math.cos(phiIn), Math.sin(phiIn)];

    c.strokeStyle = 'white';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(pIn[0] - 260 * d0[0], pIn[1] - 260 * d0[1]);
    c.lineTo(pIn[0], pIn[1]);
    c.stroke();

    // Arrow
    const ax = pIn[0] - 60 * d0[0];
    const ay = pIn[1] - 60 * d0[1];
    c.fillStyle = 'white';
    c.beginPath();
    c.moveTo(ax + 12 * d0[0], ay + 12 * d0[1]);
    c.lineTo(ax - 8 * d0[1], ay + 8 * d0[0]);
    c.lineTo(ax + 8 * d0[1], ay - 8 * d0[0]);
    c.closePath();
    c.fill();

    // Screen
    const screenX = w - 80;
    const screenTop = 40;
    const screenBottom = h - 20;
    c.fillStyle = '#1e293b';
    c.fillRect(screenX, screenTop, 30, screenBottom - screenTop);

    // Dispersed light: exact vector ray trace for every colour
    if (showColors.value) {
        const hits = [];
        colors.forEach((col) => {
            const d1 = refractDirection(d0, nLeftOut, 1, col.n);
            // intersection of pIn + t·d1 with the right face apex + u·eR
            const det = d1[0] * -eR[1] + eR[0] * d1[1];
            const rx = apex[0] - pIn[0];
            const ry = apex[1] - pIn[1];
            const t = (rx * -eR[1] + eR[0] * ry) / det;
            const u = (d1[0] * ry - d1[1] * rx) / det;
            if (!(u >= 0 && u <= side)) return; // would hit the base instead
            const pOut = [pIn[0] + t * d1[0], pIn[1] + t * d1[1]];

            c.strokeStyle = col.color;
            c.lineWidth = 2;
            c.beginPath();
            c.moveTo(pIn[0], pIn[1]);
            c.lineTo(pOut[0], pOut[1]);
            c.stroke();

            const d2 = refractDirection(d1, nRightIn, col.n, 1);
            if (d2 === null) {
                // total internal reflection at the second face
                const r = reflectDirection(d1, nRightIn);
                const len = r[1] > 0 ? Math.min(150, (BL[1] - pOut[1]) / r[1]) : 60;
                c.globalAlpha = 0.7;
                c.beginPath();
                c.moveTo(pOut[0], pOut[1]);
                c.lineTo(pOut[0] + len * r[0], pOut[1] + len * r[1]);
                c.stroke();
                c.globalAlpha = 1;
                return;
            }
            const ts = (screenX - pOut[0]) / d2[0];
            const hitY = pOut[1] + ts * d2[1];
            c.lineWidth = 2;
            c.beginPath();
            c.moveTo(pOut[0], pOut[1]);
            c.lineTo(screenX, hitY);
            c.stroke();
            hits.push({ col, y: hitY });
        });

        // Spectrum on the screen at the computed hit points
        hits.forEach(({ col, y }) => {
            c.fillStyle = col.color;
            c.fillRect(screenX + 5, y - 2, 20, 4);
        });
        if (hits.length >= 2) {
            const top = hits[0];
            const bottom = hits[hits.length - 1];
            c.font = '11px Inter';
            c.textAlign = 'right';
            c.fillStyle = top.col.color;
            c.fillText(top.col.name, screenX - 6, top.y - 6);
            c.fillStyle = bottom.col.color;
            c.fillText(bottom.col.name, screenX - 6, bottom.y + 14);
        }
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 230, 190);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Prizma burchagi: ${prismAngle.value}°`, 35, 45);
    c.fillText(`Tushish burchagi: ${toDeg(incidenceRad.value).toFixed(1)}°`, 35, 63);
    c.fillText('Sindirish ko\'rsatkichlari (Koshi):', 35, 83);

    c.fillStyle = '#ef4444';
    c.fillText(`Qizil (700nm): n = ${colors[0].n.toFixed(4)}`, 35, 101);
    c.fillStyle = '#8b5cf6';
    c.fillText(`Binafsha (400nm): n = ${colors[5].n.toFixed(4)}`, 35, 119);
    c.fillStyle = '#f59e0b';
    c.fillText(`Δn = ${deltaN.value.toFixed(4)}`, 35, 139);

    const [dRed, dViolet] = [deviations.value[0], deviations.value[5]];
    if (dRed !== null && dViolet !== null) {
        c.fillStyle = '#ffffff';
        c.fillText(`δ qizil = ${dRed.toFixed(2)}°, δ bin. = ${dViolet.toFixed(2)}°`, 35, 159);
        c.fillStyle = '#f59e0b';
        c.fillText(`Δδ = ${angularDispersion.value.toFixed(3)}°`, 35, 177);
    } else if (deviations.value.every((d) => d === null)) {
        c.fillStyle = '#f59e0b';
        c.fillText('Nur 2-yoqda to\'liq ichki qaytadi', 35, 159);
        c.fillText('(A > 2θc) — prizmadan chiqmaydi', 35, 177);
    } else {
        c.fillStyle = '#f59e0b';
        c.fillText('Ba\'zi ranglar 2-yoqda to\'liq', 35, 159);
        c.fillText('ichki qaytadi', 35, 177);
    }

    // Title
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText('δ = θ₁ + θ₂\' − A,  n = A + B/λ²', 35, 198);
};

watch([prismAngle, showColors], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Prizma burchagi: <strong>{{ prismAngle }}°</strong></label>
                <input type="range" v-model.number="prismAngle" :min="30" :max="90" :step="10" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showColors" />
                <span>Spektrni ko'rsatish</span>
            </label>
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
    align-items: center;
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

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
    cursor: pointer;
}

.checkbox input {
    width: 18px;
    height: 18px;
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
