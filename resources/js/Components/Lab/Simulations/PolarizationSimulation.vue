<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const angle = ref(0);

const canvas = ref(null);
const ctx = ref(null);

const brewsterAngle = computed(() => Math.atan(1.5) * 180 / Math.PI);
const reflectedIntensity = computed(() => {
    const angleRad = angle.value * Math.PI / 180;
    const n = 1.5;
    const rs = Math.pow((Math.cos(angleRad) - n * Math.cos(Math.asin(Math.sin(angleRad) / n))) /
        (Math.cos(angleRad) + n * Math.cos(Math.asin(Math.sin(angleRad) / n))), 2);
    const rp = Math.pow((n * Math.cos(angleRad) - Math.cos(Math.asin(Math.sin(angleRad) / n))) /
        (n * Math.cos(angleRad) + Math.cos(Math.asin(Math.sin(angleRad) / n))), 2);
    return { s: rs, p: rp };
});

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

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

    const angleRad = angle.value * Math.PI / 180;
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

    // Polarization indicator on incident ray
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

    // Reflected ray (partially polarized)
    const refX = centerX + rayLen * Math.sin(angleRad);
    const refY = centerY - rayLen * Math.cos(angleRad);

    const reflectAlpha = (reflectedIntensity.value.s + reflectedIntensity.value.p) / 2;
    c.strokeStyle = `rgba(34, 197, 94, ${0.3 + reflectAlpha * 0.7})`;
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(refX, refY);
    c.stroke();

    // Polarization for reflected ray (more S-polarized at Brewster angle)
    const refMidX = (refX + centerX) / 2;
    const refMidY = (refY + centerY) / 2;
    if (Math.abs(angle.value - brewsterAngle.value) < 3) {
        c.strokeStyle = '#22c55e';
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(refMidX - 15, refMidY);
        c.lineTo(refMidX + 15, refMidY);
        c.stroke();
        c.fillStyle = '#22c55e';
        c.fillText('S-qutblangan', refMidX + 30, refMidY);
    }

    // Transmitted ray
    const transAngleRad = Math.asin(Math.sin(angleRad) / 1.5);
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
    const brewRad = brewsterAngle.value * Math.PI / 180;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(centerX - 100 * Math.sin(brewRad), centerY - 100 * Math.cos(brewRad));
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText(`θB = ${brewsterAngle.value.toFixed(1)}°`, centerX - 120 * Math.sin(brewRad), centerY - 120 * Math.cos(brewRad));

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 240, 20, 220, 160);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tushish burchagi: ${angle.value}°`, w - 225, 45);
    c.fillText(`Bryuster burchagi: ${brewsterAngle.value.toFixed(1)}°`, w - 225, 65);
    c.fillStyle = '#22c55e';
    c.fillText(`S-qaytish: ${(reflectedIntensity.value.s * 100).toFixed(1)}%`, w - 225, 90);
    c.fillStyle = '#3b82f6';
    c.fillText(`P-qaytish: ${(reflectedIntensity.value.p * 100).toFixed(1)}%`, w - 225, 110);

    if (Math.abs(angle.value - brewsterAngle.value) < 3) {
        c.fillStyle = '#f59e0b';
        c.font = 'bold 12px Inter';
        c.fillText('⚡ Bryuster burchagida!', w - 225, 140);
        c.font = '11px Inter';
        c.fillText('P-to\'lqin qaytmaydi', w - 225, 160);
    }
};

watch(angle, () => { draw(); });
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
            <div class="info">
                <span>tan θB = n₂/n₁</span>
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
</style>
