<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const focalLength = ref(-15);
const objectDistance = ref(30);
const objectHeight = ref(3);

const canvas = ref(null);
const ctx = ref(null);

const imageDistance = computed(() => {
    const inv = 1 / focalLength.value - 1 / objectDistance.value;
    return inv !== 0 ? 1 / inv : null;
});
const magnification = computed(() => imageDistance.value ? -imageDistance.value / objectDistance.value : 0);
const imageHeight = computed(() => objectHeight.value * magnification.value);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scale = 6;

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

    // Focal points (virtual for concave)
    c.fillStyle = '#f59e0b';
    const focalPx = Math.abs(focalLength.value) * scale;
    c.beginPath();
    c.arc(lensX + focalPx, centerY, 5, 0, Math.PI * 2); // F' (virtual, same side as object for concave)
    c.arc(lensX - focalPx, centerY, 5, 0, Math.PI * 2); // F
    c.fill();
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', lensX + focalPx, centerY + 20);
    c.fillText("F'", lensX - focalPx, centerY + 20);

    // Object
    const objX = lensX - objectDistance.value * scale;
    const objTop = centerY - objectHeight.value * scale * 8;

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

    // Ray tracing for concave lens
    if (imageDistance.value) {
        const imgX = lensX + imageDistance.value * scale;
        const imgTop = centerY - imageHeight.value * scale * 8;

        // Ray 1: parallel to axis, diverges from F
        c.strokeStyle = '#ef4444';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(objX, objTop);
        c.lineTo(lensX, objTop);
        c.stroke();

        // Extended ray (dashed, virtual)
        c.setLineDash([5, 5]);
        c.strokeStyle = 'rgba(239, 68, 68, 0.5)';
        const ray1EndX = lensX + 200;
        const ray1Slope = (objTop - centerY) / (lensX - (lensX + focalPx));
        const ray1EndY = objTop + ray1Slope * 200;
        c.beginPath();
        c.moveTo(lensX, objTop);
        c.lineTo(ray1EndX, ray1EndY);
        c.stroke();
        c.setLineDash([]);

        // Ray seems to come from F
        c.strokeStyle = 'rgba(239, 68, 68, 0.3)';
        c.beginPath();
        c.moveTo(lensX + focalPx, centerY);
        c.lineTo(lensX, objTop);
        c.stroke();

        // Ray 2: through center
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(objX, objTop);
        c.lineTo(lensX + 200, objTop + (200 / (lensX - objX)) * (objTop - objTop));
        c.stroke();

        // Virtual image (dotted)
        c.strokeStyle = 'rgba(239, 68, 68, 0.5)';
        c.setLineDash([5, 5]);
        c.lineWidth = 3;
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

    c.fillText(`Fokus masofasi: ${focalLength.value} cm`, 35, 45);
    c.fillText(`Predmet masofasi: ${objectDistance.value} cm`, 35, 65);

    if (imageDistance.value) {
        c.fillStyle = '#ef4444';
        c.fillText(`Tasvir masofasi: ${imageDistance.value.toFixed(1)} cm`, 35, 90);
        c.fillStyle = '#3b82f6';
        c.fillText(`Kattalashtirish: ${Math.abs(magnification.value).toFixed(2)}x`, 35, 110);
        c.fillText(`Tasvir: Mavhum, to'g'ri, kichik`, 35, 130);
    }

    c.fillStyle = '#f59e0b';
    c.fillText('Botiq linza - tarqatuvchi', 35, 155);

    c.fillStyle = '#94a3b8';
    c.font = '13px serif';
    c.fillText('1/f = 1/d + 1/d\'', 35, 180);
};

watch([focalLength, objectDistance, objectHeight], () => { draw(); });
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
