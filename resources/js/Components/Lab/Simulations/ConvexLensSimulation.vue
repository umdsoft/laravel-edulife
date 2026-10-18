<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { CM, normalize, thinLensImage, thinLensPrincipalRays } from '../physics/optics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Sign convention (real-is-positive): 1/F = 1/d + 1/d', F > 0 for a convex lens,
// d' > 0 real image, d' < 0 virtual image, m = −d'/d.
const focalLength = ref(10); // cm
const objectDistance = ref(25); // cm
const objectHeight = ref(3); // cm

const canvas = ref(null);
const ctx = ref(null);

// All optics in SI (metres); displayed in cm.
const image = computed(() => thinLensImage(focalLength.value * CM, objectDistance.value * CM, objectHeight.value * CM));
const imageDistance = computed(() => (image.value.atInfinity ? null : image.value.imageDistance / CM)); // cm, null = ∞
const magnification = computed(() => image.value.magnification);
const imageHeight = computed(() => image.value.imageHeight / CM); // cm
const isReal = computed(() => image.value.isReal === true);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scaleX = 8; // px per cm along the axis
const scaleY = 20; // px per cm across the axis (affine scaling keeps rays straight and image points exact)

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    if (imageDistance.value === null) {
        // Object at the focus: image at infinity. The API needs a number, so report 1/d' = 0.
        emit('measurement', { name: "1/d' (predmet fokusda, d' = ∞)", value: '0', unit: '1/cm' });
        return;
    }
    emit('measurement', { name: 'Tasvir masofasi', value: imageDistance.value.toFixed(2), unit: 'cm' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const lensX = w / 2;

    // physical (m) → canvas (px); y up in physics, down on the canvas
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

    // Lens
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(lensX, centerY - 150);
    c.lineTo(lensX, centerY + 150);
    c.stroke();

    // Lens arrows (convex)
    c.fillStyle = '#3b82f6';
    c.beginPath();
    c.moveTo(lensX, centerY - 150);
    c.lineTo(lensX - 10, centerY - 140);
    c.lineTo(lensX + 10, centerY - 140);
    c.closePath();
    c.fill();
    c.beginPath();
    c.moveTo(lensX, centerY + 150);
    c.lineTo(lensX - 10, centerY + 140);
    c.lineTo(lensX + 10, centerY + 140);
    c.closePath();
    c.fill();

    // Focal points: F (object side) and F' (image side)
    c.fillStyle = '#f59e0b';
    const focalPx = focalLength.value * scaleX;
    c.beginPath();
    c.arc(lensX - focalPx, centerY, 6, 0, Math.PI * 2);
    c.fill();
    c.beginPath();
    c.arc(lensX + focalPx, centerY, 6, 0, Math.PI * 2);
    c.fill();
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', lensX - focalPx, centerY + 20);
    c.fillText("F'", lensX + focalPx, centerY + 20);

    // Object
    const objX = lensX - objectDistance.value * scaleX;
    const objTop = centerY - objectHeight.value * scaleY;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(objX, centerY);
    c.lineTo(objX, objTop);
    c.stroke();

    // Arrow head
    c.fillStyle = '#22c55e';
    c.beginPath();
    c.moveTo(objX, objTop);
    c.lineTo(objX - 8, objTop + 15);
    c.lineTo(objX + 8, objTop + 15);
    c.closePath();
    c.fill();

    // Ray tracing — principal rays computed in optics.js; every outgoing ray (real image)
    // or its dashed backward extension (virtual image) passes through the computed image.
    const img = image.value;
    const imgPt = img.atInfinity ? null : toPx([img.imageDistance, img.imageHeight]);
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

        if (imgPt && !isReal.value) {
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
        c.strokeStyle = isReal.value ? '#ef4444' : '#ef444488';
        c.setLineDash(isReal.value ? [] : [5, 5]);
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(imgPt[0], centerY);
        c.lineTo(imgPt[0], imgPt[1]);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 230, 190);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Fokus masofasi (F): ${focalLength.value} cm`, 35, 45);
    c.fillText(`Predmet masofasi (d): ${objectDistance.value} cm`, 35, 65);
    c.fillText(`Predmet balandligi: ${objectHeight.value} cm`, 35, 85);

    if (imageDistance.value !== null) {
        c.fillStyle = '#ef4444';
        c.fillText(`Tasvir masofasi (d'): ${imageDistance.value.toFixed(1)} cm`, 35, 110);
        c.fillStyle = '#3b82f6';
        c.fillText(`Kattalashtirish: ${Math.abs(magnification.value).toFixed(2)}x (Γ = ${magnification.value.toFixed(2)})`, 35, 130);
        c.fillText(`Tasvir balandligi: ${Math.abs(imageHeight.value).toFixed(2)} cm`, 35, 150);
        c.fillText(`Tasvir: ${isReal.value ? 'Haqiqiy' : 'Mavhum'}, ${magnification.value < 0 ? 'teskari' : 'to\'g\'ri'}`, 35, 170);
    } else {
        c.fillStyle = '#f59e0b';
        c.fillText('Predmet fokusda: nurlar parallel,', 35, 110);
        c.fillText('tasvir cheksizlikda (d\' = ∞)', 35, 130);
    }

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '13px serif';
    c.fillText('1/F = 1/d + 1/d\',  Γ = −d\'/d', 35, 195);
};

watch([focalLength, objectDistance, objectHeight], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Fokus (F): <strong>{{ focalLength }} cm</strong></label>
                <input type="range" v-model.number="focalLength" :min="5" :max="20" :step="1" />
            </div>
            <div class="control-group">
                <label>Predmet (d): <strong>{{ objectDistance }} cm</strong></label>
                <input type="range" v-model.number="objectDistance" :min="5" :max="50" :step="1" />
            </div>
            <div class="control-group">
                <label>Balandlik: <strong>{{ objectHeight }} cm</strong></label>
                <input type="range" v-model.number="objectHeight" :min="1" :max="5" :step="0.5" />
            </div>
            <button @click="measure" class="btn measure">📏 O'lchash</button>
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    background: #3b82f6;
    color: white;
}
</style>
