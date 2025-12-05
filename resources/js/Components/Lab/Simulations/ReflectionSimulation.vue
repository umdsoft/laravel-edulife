<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const incidentAngle = ref(45);
const surfaceType = ref('mirror');

const canvas = ref(null);
const ctx = ref(null);

const reflectedAngle = computed(() => incidentAngle.value);

const surfaces = {
    mirror: { name: 'Oyna', reflectivity: 0.95, color: '#94a3b8' },
    glass: { name: 'Shisha', reflectivity: 0.04, color: '#06b6d4' },
    water: { name: 'Suv', reflectivity: 0.02, color: '#3b82f6' },
};

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Qaytish burchagi', value: reflectedAngle.value.toFixed(1), unit: '°' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    const centerX = w / 2;
    const centerY = h / 2;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Surface
    c.fillStyle = surfaces[surfaceType.value].color;
    c.fillRect(100, centerY, w - 200, 100);

    // Surface reflection effect
    const grad = c.createLinearGradient(0, centerY, 0, centerY + 20);
    grad.addColorStop(0, 'rgba(255,255,255,0.3)');
    grad.addColorStop(1, 'transparent');
    c.fillStyle = grad;
    c.fillRect(100, centerY, w - 200, 20);

    // Normal line
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.setLineDash([8, 8]);
    c.beginPath();
    c.moveTo(centerX, centerY - 200);
    c.lineTo(centerX, centerY + 50);
    c.stroke();
    c.setLineDash([]);

    c.fillStyle = '#64748b';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('Normal', centerX, centerY - 210);

    // Incident ray
    const incRad = incidentAngle.value * Math.PI / 180;
    const rayLen = 180;
    const incStartX = centerX - rayLen * Math.sin(incRad);
    const incStartY = centerY - rayLen * Math.cos(incRad);

    c.strokeStyle = '#ef4444';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(incStartX, incStartY);
    c.lineTo(centerX, centerY);
    c.stroke();

    // Arrow on incident ray
    const arrowPos = 0.5;
    const arrowX = incStartX + (centerX - incStartX) * arrowPos;
    const arrowY = incStartY + (centerY - incStartY) * arrowPos;
    c.fillStyle = '#ef4444';
    c.beginPath();
    c.arc(arrowX + 20 * Math.sin(incRad), arrowY + 20 * Math.cos(incRad), 6, 0, Math.PI * 2);
    c.fill();

    // Reflected ray
    const refRad = reflectedAngle.value * Math.PI / 180;
    const refEndX = centerX + rayLen * Math.sin(refRad);
    const refEndY = centerY - rayLen * Math.cos(refRad);

    const reflectivity = surfaces[surfaceType.value].reflectivity;
    c.strokeStyle = `rgba(34, 197, 94, ${reflectivity})`;
    c.lineWidth = 4 * reflectivity;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(refEndX, refEndY);
    c.stroke();

    // Angle arcs
    const arcRadius = 50;

    // Incident angle arc
    c.strokeStyle = '#ef4444';
    c.lineWidth = 2;
    c.beginPath();
    c.arc(centerX, centerY, arcRadius, -Math.PI / 2, -Math.PI / 2 + incRad, false);
    c.stroke();

    // Reflected angle arc
    c.strokeStyle = '#22c55e';
    c.beginPath();
    c.arc(centerX, centerY, arcRadius + 10, -Math.PI / 2, -Math.PI / 2 - refRad, true);
    c.stroke();

    // Angle labels
    c.fillStyle = '#ef4444';
    c.font = '14px Inter';
    c.fillText(`θᵢ = ${incidentAngle.value}°`, centerX - 90, centerY - 100);
    c.fillStyle = '#22c55e';
    c.fillText(`θᵣ = ${reflectedAngle.value}°`, centerX + 50, centerY - 100);

    // Law statement
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 80);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Qaytish qonuni:', w - 205, 45);
    c.fillStyle = 'white';
    c.font = '16px serif';
    c.fillText('θᵢ = θᵣ', w - 205, 75);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 100);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`Sirt: ${surfaces[surfaceType.value].name}`, 35, 45);
    c.fillStyle = '#ef4444';
    c.fillText(`Tushish burchagi: ${incidentAngle.value}°`, 35, 70);
    c.fillStyle = '#22c55e';
    c.fillText(`Qaytish burchagi: ${reflectedAngle.value}°`, 35, 95);
};

watch([incidentAngle, surfaceType], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tushish burchagi: <strong>{{ incidentAngle }}°</strong></label>
                <input type="range" v-model.number="incidentAngle" :min="10" :max="80" :step="5" />
            </div>
            <div class="control-group">
                <label>Sirt turi:</label>
                <select v-model="surfaceType">
                    <option v-for="(s, key) in surfaces" :key="key" :value="key">{{ s.name }}</option>
                </select>
            </div>
            <button @click="measure" class="btn">📏 O'lchash</button>
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
    min-width: 150px;
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
