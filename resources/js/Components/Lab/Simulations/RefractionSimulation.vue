<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const angle1 = ref(45); // degrees
const n1 = ref(1.0); // air
const n2 = ref(1.5); // glass

const canvas = ref(null);
const ctx = ref(null);

const angle1Rad = computed(() => angle1.value * Math.PI / 180);
const sinAngle2 = computed(() => (n1.value * Math.sin(angle1Rad.value)) / n2.value);
const angle2 = computed(() => Math.abs(sinAngle2.value) <= 1 ? Math.asin(sinAngle2.value) * 180 / Math.PI : null);
const isTotalReflection = computed(() => Math.abs(sinAngle2.value) > 1);
const criticalAngle = computed(() => n2.value < n1.value ? Math.asin(n2.value / n1.value) * 180 / Math.PI : null);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: `Sinish burchagi (n₁=${n1.value}, n₂=${n2.value})`, value: angle2.value?.toFixed(2) || 'TIR', unit: '°' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    const centerX = w / 2;
    const centerY = h / 2;

    // Background - two media
    c.fillStyle = '#1e3a5f'; // Top (air)
    c.fillRect(0, 0, w, centerY);
    c.fillStyle = '#1e293b'; // Bottom (glass)
    c.fillRect(0, centerY, w, centerY);

    // Interface line
    c.strokeStyle = '#64748b';
    c.setLineDash([10, 5]);
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(0, centerY);
    c.lineTo(w, centerY);
    c.stroke();
    c.setLineDash([]);

    // Normal line
    c.strokeStyle = '#475569';
    c.lineWidth = 1;
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(centerX, centerY - 200);
    c.lineTo(centerX, centerY + 200);
    c.stroke();
    c.setLineDash([]);

    // Incident ray
    const rayLen = 200;
    const incidentAngleRad = angle1Rad.value;
    const startX = centerX - rayLen * Math.sin(incidentAngleRad);
    const startY = centerY - rayLen * Math.cos(incidentAngleRad);

    c.strokeStyle = '#ef4444';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(startX, startY);
    c.lineTo(centerX, centerY);
    c.stroke();

    // Arrow
    c.fillStyle = '#ef4444';
    c.beginPath();
    const arrowX = centerX - 30 * Math.sin(incidentAngleRad);
    const arrowY = centerY - 30 * Math.cos(incidentAngleRad);
    c.arc(arrowX, arrowY, 6, 0, Math.PI * 2);
    c.fill();

    // Refracted ray or total internal reflection
    if (isTotalReflection.value) {
        // Reflected ray (total internal reflection)
        const reflectedX = centerX + rayLen * Math.sin(incidentAngleRad);
        const reflectedY = centerY - rayLen * Math.cos(incidentAngleRad);

        c.strokeStyle = '#f59e0b';
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(centerX, centerY);
        c.lineTo(reflectedX, reflectedY);
        c.stroke();

        c.fillStyle = '#f59e0b';
        c.font = '14px Inter';
        c.textAlign = 'center';
        c.fillText('To\'liq ichki qaytish!', centerX, centerY + 120);
    } else if (angle2.value !== null) {
        const refractedAngleRad = angle2.value * Math.PI / 180;
        const endX = centerX + rayLen * Math.sin(refractedAngleRad);
        const endY = centerY + rayLen * Math.cos(refractedAngleRad);

        c.strokeStyle = '#3b82f6';
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(centerX, centerY);
        c.lineTo(endX, endY);
        c.stroke();
    }

    // Angle arcs
    c.strokeStyle = '#ef4444';
    c.lineWidth = 2;
    c.beginPath();
    c.arc(centerX, centerY, 40, -Math.PI / 2, -Math.PI / 2 + incidentAngleRad, false);
    c.stroke();

    if (angle2.value !== null && !isTotalReflection.value) {
        c.strokeStyle = '#3b82f6';
        c.beginPath();
        c.arc(centerX, centerY, 50, Math.PI / 2 - angle2.value * Math.PI / 180, Math.PI / 2, false);
        c.stroke();
    }

    // Labels
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText(`n₁ = ${n1.value}`, 80, 50);
    c.fillText(`n₂ = ${n2.value}`, 80, centerY + 50);

    // Angle labels
    c.fillStyle = '#ef4444';
    c.fillText(`θ₁ = ${angle1.value}°`, centerX + 80, centerY - 60);
    if (angle2.value !== null && !isTotalReflection.value) {
        c.fillStyle = '#3b82f6';
        c.fillText(`θ₂ = ${angle2.value.toFixed(1)}°`, centerX + 80, centerY + 80);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 250, 20, 230, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tushish burchagi (θ₁): ${angle1.value}°`, w - 235, 45);
    c.fillText(`n₁ = ${n1.value} (yuqori muhit)`, w - 235, 65);
    c.fillText(`n₂ = ${n2.value} (pastki muhit)`, w - 235, 85);

    if (angle2.value !== null && !isTotalReflection.value) {
        c.fillStyle = '#3b82f6';
        c.fillText(`Sinish burchagi: ${angle2.value.toFixed(2)}°`, w - 235, 110);
    } else if (isTotalReflection.value) {
        c.fillStyle = '#f59e0b';
        c.fillText('To\'liq ichki qaytish!', w - 235, 110);
    }

    if (criticalAngle.value) {
        c.fillStyle = '#f59e0b';
        c.fillText(`Kritik burchak: ${criticalAngle.value.toFixed(1)}°`, w - 235, 135);
    }

    // Snell's Law
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('n₁ sin θ₁ = n₂ sin θ₂', w - 235, 160);
};

watch([angle1, n1, n2], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tushish burchagi: <strong>{{ angle1 }}°</strong></label>
                <input type="range" v-model.number="angle1" :min="0" :max="85" :step="1" />
            </div>
            <div class="control-group">
                <label>n₁ (yuqori): <strong>{{ n1 }}</strong></label>
                <input type="range" v-model.number="n1" :min="1" :max="2" :step="0.1" />
            </div>
            <div class="control-group">
                <label>n₂ (pastki): <strong>{{ n2 }}</strong></label>
                <input type="range" v-model.number="n2" :min="1" :max="2.5" :step="0.1" />
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
    font-weight: 600;
    cursor: pointer;
    background: #3b82f6;
    color: white;
}
</style>
