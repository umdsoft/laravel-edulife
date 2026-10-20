<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { CM, normalize, mirrorImage, mirrorPrincipalRays, OPTICS_INSTRUMENTS } from '../physics/optics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Sign convention (real-is-positive): 1/F = 1/d + 1/d', F = R/2 with F > 0 for a concave
// mirror and F < 0 for a convex one; d' > 0 real image in front of the mirror,
// d' < 0 virtual image behind it; m = −d'/d.
const mirrorType = ref('concave');
const focalLength = ref(20); // |F|, cm
const objectDistance = ref(40); // cm

const canvas = ref(null);
const ctx = ref(null);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scale = 5; // px per cm (isotropic)
const objHeightPx = 40;
const objectHeightCm = objHeightPx / scale; // 8 cm

const signedF = computed(() => (mirrorType.value === 'concave' ? focalLength.value : -focalLength.value)); // cm
const image = computed(() => mirrorImage(signedF.value * CM, objectDistance.value * CM, objectHeightCm * CM));
const imageDistance = computed(() => (image.value.atInfinity ? null : image.value.imageDistance / CM)); // cm
const magnification = computed(() => image.value.magnification);
const isReal = computed(() => image.value.isReal === true);

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

// The image is found on the optical bench (1 mm scale): screen moved until the image is
// sharpest (real image) or parallax method (virtual image) — σ = 1 mm, reading ±3 mm.
// The k-th reading of a set-up is reproducible (seeded from the set-up).
const bench = createInstrument(OPTICS_INSTRUMENTS.benchImage, 'CurvedMirrorSimulation');
const lastReading = ref(null);

const measure = () => {
    if (imageDistance.value === null) {
        emit('measurement', { name: "1/d' (predmet fokusda, d' = ∞)", value: '0', unit: '1/cm' });
        return;
    }
    const value = bench.read(imageDistance.value, [mirrorType.value, focalLength.value, objectDistance.value]);
    lastReading.value = value;
    emit('measurement', { name: `Tasvir masofasi (${mirrorType.value === 'concave' ? 'botiq' : 'qavariq'} oyna)`, value: bench.format(value), unit: 'cm' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const mirrorX = 700;

    const toPx = ([x, y]) => [mirrorX + (x / CM) * scale, centerY - (y / CM) * scale];
    const dirPx = ([dx, dy]) => normalize(dx, -dy);

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Optical axis
    c.strokeStyle = '#334155';
    c.lineWidth = 1;
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(0, centerY);
    c.lineTo(w, centerY);
    c.stroke();
    c.setLineDash([]);

    // Mirror: arc of the real radius R = 2|F| with its vertex at mirrorX.
    // Concave: centre of curvature in front (left); convex: behind (right).
    const Rpx = 2 * focalLength.value * scale;
    const halfAperture = Math.min(150, 0.95 * Rpx);
    const alpha = Math.asin(halfAperture / Rpx);
    c.strokeStyle = '#94a3b8';
    c.lineWidth = 4;
    c.beginPath();
    if (mirrorType.value === 'concave') {
        c.arc(mirrorX - Rpx, centerY, Rpx, -alpha, alpha);
    } else {
        c.arc(mirrorX + Rpx, centerY, Rpx, Math.PI - alpha, Math.PI + alpha);
    }
    c.stroke();

    // Focal point F (x = −F) and centre of curvature C (x = −2F); behind the mirror for convex.
    const fX = toPx([-signedF.value * CM, 0])[0];
    const cX = toPx([-2 * signedF.value * CM, 0])[0];
    c.fillStyle = '#f59e0b';
    c.beginPath();
    c.arc(fX, centerY, 5, 0, Math.PI * 2);
    c.fill();
    c.beginPath();
    c.arc(cX, centerY, 5, 0, Math.PI * 2);
    c.fill();

    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', fX, centerY + 20);
    c.fillText('C', cX, centerY + 20);

    // Object
    const objX = mirrorX - objectDistance.value * scale;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(objX, centerY);
    c.lineTo(objX, centerY - objHeightPx);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.beginPath();
    c.moveTo(objX, centerY - objHeightPx);
    c.lineTo(objX - 8, centerY - objHeightPx + 15);
    c.lineTo(objX + 8, centerY - objHeightPx + 15);
    c.closePath();
    c.fill();

    // Ray tracing (paraxial; reflection drawn at the vertex plane):
    // parallel → through/away from F, through C → back on itself, through F → parallel.
    const img = image.value;
    const imgPt = img.atInfinity ? null : toPx([-img.imageDistance, img.imageHeight]);
    const rayColors = { parallel: '#ef4444', centre: '#3b82f6', focal: '#a855f7' };
    const rays = mirrorPrincipalRays(signedF.value * CM, objectDistance.value * CM, objectHeightCm * CM)
        .filter((r) => r.id in rayColors);
    const far = 3 * w;

    rays.forEach((ray) => {
        const [sx, sy] = toPx(ray.start);
        const [hx, hy] = toPx(ray.hit);
        const [ux, uy] = dirPx(ray.dir);
        c.strokeStyle = rayColors[ray.id];
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(sx, sy);
        c.lineTo(hx, hy);
        c.lineTo(hx + ux * far, hy + uy * far);
        c.stroke();

        if (imgPt && !isReal.value) {
            // backward extension behind the mirror to the virtual image
            c.setLineDash([5, 5]);
            c.globalAlpha = 0.5;
            c.beginPath();
            c.moveTo(hx, hy);
            c.lineTo(imgPt[0], imgPt[1]);
            c.stroke();
            c.setLineDash([]);
            c.globalAlpha = 1;
        }
    });

    // Image
    if (imgPt) {
        c.strokeStyle = isReal.value ? '#ef4444' : 'rgba(239, 68, 68, 0.5)';
        c.setLineDash(isReal.value ? [] : [5, 5]);
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(imgPt[0], centerY);
        c.lineTo(imgPt[0], imgPt[1]);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 175);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Oyna turi: ${mirrorType.value === 'concave' ? 'Botiq' : 'Qavariq'}`, 35, 45);
    c.fillText(`F = ${signedF.value} cm  (R = ${2 * focalLength.value} cm)`, 35, 65);
    c.fillText(`d = ${objectDistance.value} cm`, 35, 85);

    if (imageDistance.value !== null) {
        c.fillStyle = '#ef4444';
        c.fillText(`d' = ${imageDistance.value.toFixed(1)} cm`, 35, 110);
        c.fillStyle = '#3b82f6';
        c.fillText(`M = ${Math.abs(magnification.value).toFixed(2)}x (Γ = ${magnification.value.toFixed(2)})`, 35, 130);
        c.fillText(`Tasvir: ${isReal.value ? 'Haqiqiy' : 'Mavhum'}, ${magnification.value < 0 ? 'teskari' : 'to\'g\'ri'}`, 35, 150);
    } else {
        c.fillStyle = '#f59e0b';
        c.fillText('Predmet fokusda: tasvir', 35, 110);
        c.fillText('cheksizlikda (d\' = ∞)', 35, 130);
    }

    c.fillStyle = '#94a3b8';
    c.font = '13px serif';
    c.fillText('1/F = 1/d + 1/d\',  F = R/2', 35, 180);
};

watch([mirrorType, focalLength, objectDistance], () => { lastReading.value = null; draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Oyna turi:</label>
                <select v-model="mirrorType">
                    <option value="concave">Botiq</option>
                    <option value="convex">Qavariq</option>
                </select>
            </div>
            <div class="control-group">
                <label>Fokus: <strong>{{ focalLength }} cm</strong></label>
                <input type="range" v-model.number="focalLength" :min="10" :max="40" :step="5" />
            </div>
            <div class="control-group">
                <label>Predmet: <strong>{{ objectDistance }} cm</strong></label>
                <input type="range" v-model.number="objectDistance" :min="15" :max="80" :step="5" />
            </div>
            <button @click="measure" class="btn">📏 O'lchash</button>
            <div v-if="lastReading !== null" class="control-group">
                <label>Optik skamya: <strong>d' = {{ bench.format(lastReading) }} cm</strong></label>
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
