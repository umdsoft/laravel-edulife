<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const focalLength = ref(10); // cm
const objectDistance = ref(25); // cm
const objectHeight = ref(3); // cm

const canvas = ref(null);
const ctx = ref(null);

const imageDistance = computed(() => {
    const inv = 1 / focalLength.value - 1 / objectDistance.value;
    return inv !== 0 ? 1 / inv : null;
});
const magnification = computed(() => imageDistance.value ? -imageDistance.value / objectDistance.value : 0);
const imageHeight = computed(() => objectHeight.value * magnification.value);
const isReal = computed(() => imageDistance.value > 0);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scale = 8;

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Tasvir masofasi', value: imageDistance.value?.toFixed(2), unit: 'cm' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const lensX = w / 2;

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

    // Focal points
    c.fillStyle = '#f59e0b';
    const focalPx = focalLength.value * scale;
    c.beginPath();
    c.arc(lensX - focalPx, centerY, 6, 0, Math.PI * 2);
    c.arc(lensX + focalPx, centerY, 6, 0, Math.PI * 2);
    c.fill();
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', lensX - focalPx, centerY + 20);
    c.fillText("F'", lensX + focalPx, centerY + 20);

    // Object
    const objX = lensX - objectDistance.value * scale;
    const objTop = centerY - objectHeight.value * scale * 10;

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

    // Ray tracing
    if (imageDistance.value) {
        const imgX = lensX + imageDistance.value * scale;
        const imgTop = centerY - imageHeight.value * scale * 10;

        // Ray 1: parallel to axis, through F'
        c.strokeStyle = '#ef4444';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(objX, objTop);
        c.lineTo(lensX, objTop);
        c.lineTo(imgX, imgTop);
        c.stroke();

        // Ray 2: through center
        c.strokeStyle = '#3b82f6';
        c.beginPath();
        c.moveTo(objX, objTop);
        c.lineTo(imgX, imgTop);
        c.stroke();

        // Ray 3: through F, parallel after lens
        c.strokeStyle = '#f59e0b';
        c.beginPath();
        c.moveTo(objX, objTop);
        const yAtLens = objTop + (centerY - objTop) * (lensX - objX) / (lensX - focalPx - objX + focalPx);
        c.lineTo(lensX, yAtLens);
        c.lineTo(imgX, yAtLens);
        c.stroke();

        // Image
        c.strokeStyle = isReal.value ? '#ef4444' : '#ef444488';
        c.setLineDash(isReal.value ? [] : [5, 5]);
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(imgX, centerY);
        c.lineTo(imgX, imgTop);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Fokus masofasi (f): ${focalLength.value} cm`, 35, 45);
    c.fillText(`Predmet masofasi (d): ${objectDistance.value} cm`, 35, 65);
    c.fillText(`Predmet balandligi: ${objectHeight.value} cm`, 35, 85);

    if (imageDistance.value) {
        c.fillStyle = '#ef4444';
        c.fillText(`Tasvir masofasi: ${imageDistance.value.toFixed(1)} cm`, 35, 110);
        c.fillStyle = '#3b82f6';
        c.fillText(`Kattalashtirish: ${Math.abs(magnification.value).toFixed(2)}x`, 35, 130);
        c.fillText(`Tasvir: ${isReal.value ? 'Haqiqiy' : 'Mavhum'}`, 35, 150);
        c.fillText(`Yo'nalish: ${magnification.value < 0 ? 'Teskari' : 'To\'g\'ri'}`, 35, 170);
    }

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '13px serif';
    c.fillText('1/f = 1/d + 1/d\'', 35, 185);
};

watch([focalLength, objectDistance, objectHeight], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Fokus (f): <strong>{{ focalLength }} cm</strong></label>
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
