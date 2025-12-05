<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const mirrorType = ref('concave');
const focalLength = ref(20);
const objectDistance = ref(40);

const canvas = ref(null);
const ctx = ref(null);

const imageDistance = computed(() => {
    const f = mirrorType.value === 'concave' ? focalLength.value : -focalLength.value;
    const inv = 1 / f - 1 / objectDistance.value;
    return inv !== 0 ? 1 / inv : null;
});
const magnification = computed(() => imageDistance.value ? -imageDistance.value / objectDistance.value : 0);
const isReal = computed(() => imageDistance.value > 0);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));
const scale = 5;

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = h / 2;
    const mirrorX = 700;

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

    // Mirror
    c.strokeStyle = '#94a3b8';
    c.lineWidth = 4;
    c.beginPath();
    if (mirrorType.value === 'concave') {
        c.arc(mirrorX + 80, centerY, 120, 2.6, 3.7);
    } else {
        c.arc(mirrorX - 80, centerY, 120, -0.5, 0.5);
    }
    c.stroke();

    // Center and focal point
    c.fillStyle = '#f59e0b';
    const fDist = focalLength.value * scale;
    c.beginPath();
    c.arc(mirrorX - fDist, centerY, 5, 0, Math.PI * 2);
    c.fill();
    c.beginPath();
    c.arc(mirrorX - fDist * 2, centerY, 5, 0, Math.PI * 2);
    c.fill();

    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('F', mirrorX - fDist, centerY + 20);
    c.fillText('C', mirrorX - fDist * 2, centerY + 20);

    // Object
    const objX = mirrorX - objectDistance.value * scale;
    const objHeight = 40;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(objX, centerY);
    c.lineTo(objX, centerY - objHeight);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.beginPath();
    c.moveTo(objX, centerY - objHeight);
    c.lineTo(objX - 8, centerY - objHeight + 15);
    c.lineTo(objX + 8, centerY - objHeight + 15);
    c.closePath();
    c.fill();

    // Ray tracing
    if (imageDistance.value) {
        const imgX = mirrorX - imageDistance.value * scale;
        const imgHeight = objHeight * magnification.value;

        // Ray 1: parallel to axis, reflects through F
        c.strokeStyle = '#ef4444';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(objX, centerY - objHeight);
        c.lineTo(mirrorX, centerY - objHeight);
        if (mirrorType.value === 'concave') {
            c.lineTo(mirrorX - fDist, centerY);
            if (isReal.value) {
                c.lineTo(imgX, centerY - imgHeight);
            }
        }
        c.stroke();

        // Ray 2: through center C, reflects back
        c.strokeStyle = '#3b82f6';
        c.beginPath();
        c.moveTo(objX, centerY - objHeight);
        c.lineTo(mirrorX, centerY - objHeight * (mirrorX - mirrorX + fDist * 2) / (mirrorX - objX + fDist * 2));
        c.stroke();

        // Image
        if (isReal.value) {
            c.strokeStyle = '#ef4444';
            c.lineWidth = 3;
            c.beginPath();
            c.moveTo(imgX, centerY);
            c.lineTo(imgX, centerY - imgHeight);
            c.stroke();
        } else {
            c.strokeStyle = 'rgba(239, 68, 68, 0.5)';
            c.setLineDash([5, 5]);
            c.lineWidth = 3;
            c.beginPath();
            c.moveTo(imgX, centerY);
            c.lineTo(imgX, centerY - imgHeight);
            c.stroke();
            c.setLineDash([]);
        }
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Oyna turi: ${mirrorType.value === 'concave' ? 'Botiq' : 'Qavariq'}`, 35, 45);
    c.fillText(`f = ${focalLength.value} cm`, 35, 65);
    c.fillText(`d = ${objectDistance.value} cm`, 35, 85);

    if (imageDistance.value) {
        c.fillStyle = '#ef4444';
        c.fillText(`d' = ${imageDistance.value.toFixed(1)} cm`, 35, 110);
        c.fillStyle = '#3b82f6';
        c.fillText(`M = ${Math.abs(magnification.value).toFixed(2)}x`, 35, 130);
        c.fillText(`Tasvir: ${isReal.value ? 'Haqiqiy' : 'Mavhum'}`, 35, 150);
    }
};

watch([mirrorType, focalLength, objectDistance], () => { draw(); });
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
</style>
