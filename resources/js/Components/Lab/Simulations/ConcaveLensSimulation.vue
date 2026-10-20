<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { CM, normalize, thinLensImage, thinLensPrincipalRays, OPTICS_INSTRUMENTS } from '../physics/optics.js';
import { createInstrument } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Sign convention (real-is-positive): 1/F = 1/d + 1/d', F < 0 for a concave (diverging)
// lens, d' < 0 = virtual image on the object side, m = −d'/d.
const focalLength = ref(-15); // cm (negative: diverging)
const objectDistance = ref(30); // cm
const objectHeight = ref(3); // cm

const canvas = ref(null);
const ctx = ref(null);

const image = computed(() => thinLensImage(focalLength.value * CM, objectDistance.value * CM, objectHeight.value * CM));
const imageDistance = computed(() => (image.value.atInfinity ? null : image.value.imageDistance / CM)); // cm
const magnification = computed(() => image.value.magnification);
const imageHeight = computed(() => image.value.imageHeight / CM); // cm

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scaleX = 6; // px per cm along the axis
const scaleY = 48; // px per cm across the axis (affine scaling keeps rays straight)

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

// The image is found on the optical bench (1 mm scale): screen moved until the image is
// sharpest (real image) or parallax method (virtual image) — σ = 1 mm, reading ±3 mm.
// The k-th reading of a set-up is reproducible (seeded from the set-up).
const bench = createInstrument(OPTICS_INSTRUMENTS.benchImage, 'ConcaveLensSimulation');
const lastReading = ref(null);

const measure = () => {
    if (imageDistance.value === null) return; // impossible for a diverging lens and a real object
    const value = bench.read(imageDistance.value, [focalLength.value, objectDistance.value]);
    lastReading.value = value;
    emit('measurement', { name: 'Tasvir masofasi', value: bench.format(value), unit: 'cm' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const lensX = w / 2;

    const toPx = ([x, y]) => [lensX + (x / CM) * scaleX, centerY - (y / CM) * scaleY];
    const dirPx = ([dx, dy]) => normalize(dx * scaleX, -dy * scaleY);

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

    // Concave lens (diverging)
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 3;

    // Draw concave lens shape
    c.beginPath();
    c.moveTo(lensX - 10, centerY - 120);
    c.quadraticCurveTo(lensX + 5, centerY, lensX - 10, centerY + 120);
    c.stroke();

    c.beginPath();
    c.moveTo(lensX + 10, centerY - 120);
    c.quadraticCurveTo(lensX - 5, centerY, lensX + 10, centerY + 120);
    c.stroke();

    // Connect tops and bottoms
    c.beginPath();
    c.moveTo(lensX - 10, centerY - 120);
    c.lineTo(lensX + 10, centerY - 120);
    c.moveTo(lensX - 10, centerY + 120);
    c.lineTo(lensX + 10, centerY + 120);
    c.stroke();

    // Focal points of a diverging lens: F' (image-side, virtual) lies on the OBJECT side
    // at x = F < 0; F (object-side) lies behind the lens at x = −F.
    c.fillStyle = '#f59e0b';
    const focalPx = Math.abs(focalLength.value) * scaleX;
    c.beginPath();
    c.arc(lensX - focalPx, centerY, 5, 0, Math.PI * 2); // F'
    c.fill();
    c.beginPath();
    c.arc(lensX + focalPx, centerY, 5, 0, Math.PI * 2); // F
    c.fill();
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', lensX + focalPx, centerY + 20);
    c.fillText("F'", lensX - focalPx, centerY + 20);

    // Object
    const objX = lensX - objectDistance.value * scaleX;
    const objTop = centerY - objectHeight.value * scaleY;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(objX, centerY);
    c.lineTo(objX, objTop);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.beginPath();
    c.moveTo(objX, objTop);
    c.lineTo(objX - 8, objTop + 15);
    c.lineTo(objX + 8, objTop + 15);
    c.closePath();
    c.fill();

    // Ray tracing: 1) parallel → diverges as if from F'; 2) through the centre, undeviated;
    // 3) aimed at F → leaves parallel. Dashed backward extensions meet at the virtual image.
    const img = image.value;
    if (!img.atInfinity) {
        const imgPt = toPx([img.imageDistance, img.imageHeight]);
        const rayColors = { parallel: '#ef4444', central: '#3b82f6', focal: '#f59e0b' };
        const rays = thinLensPrincipalRays(focalLength.value * CM, objectDistance.value * CM, objectHeight.value * CM);
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

            if (!img.isReal) {
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

        // Image (virtual → dashed)
        c.strokeStyle = img.isReal ? '#ef4444' : 'rgba(239, 68, 68, 0.5)';
        c.setLineDash(img.isReal ? [] : [5, 5]);
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(imgPt[0], centerY);
        c.lineTo(imgPt[0], imgPt[1]);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 230, 175);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Fokus masofasi: ${focalLength.value} cm`, 35, 45);
    c.fillText(`Predmet masofasi: ${objectDistance.value} cm`, 35, 65);

    if (imageDistance.value !== null) {
        c.fillStyle = '#ef4444';
        c.fillText(`Tasvir masofasi: ${imageDistance.value.toFixed(1)} cm`, 35, 90);
        c.fillStyle = '#3b82f6';
        c.fillText(`Kattalashtirish: ${Math.abs(magnification.value).toFixed(2)}x`, 35, 110);
        const kind = [
            img.isReal ? 'Haqiqiy' : 'Mavhum',
            img.isUpright ? 'to\'g\'ri' : 'teskari',
            img.isEnlarged ? 'katta' : 'kichik',
        ].join(', ');
        c.fillText(`Tasvir: ${kind}`, 35, 130);
        c.fillText(`Tasvir balandligi: ${Math.abs(imageHeight.value).toFixed(2)} cm`, 35, 150);
    }

    c.fillStyle = '#f59e0b';
    c.fillText('Botiq linza - tarqatuvchi (F < 0)', 35, 170);

    c.fillStyle = '#94a3b8';
    c.font = '13px serif';
    c.fillText('1/F = 1/d + 1/d\'', 35, 188);
};

watch([focalLength, objectDistance, objectHeight], () => { lastReading.value = null; draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Fokus: <strong>{{ focalLength }} cm</strong></label>
                <input type="range" v-model.number="focalLength" :min="-30" :max="-5" :step="1" />
            </div>
            <div class="control-group">
                <label>Predmet: <strong>{{ objectDistance }} cm</strong></label>
                <input type="range" v-model.number="objectDistance" :min="10" :max="60" :step="2" />
            </div>
            <button @click="measure" class="btn">📏</button>
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
    min-width: 140px;
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
</style>
