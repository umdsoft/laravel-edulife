<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const prismAngle = ref(60);
const showColors = ref(true);

const canvas = ref(null);
const ctx = ref(null);

// Refractive indices for different wavelengths
const colors = [
    { name: 'Qizil', wavelength: 700, n: 1.513, color: '#ef4444' },
    { name: 'To\'q sariq', wavelength: 620, n: 1.517, color: '#f97316' },
    { name: 'Sariq', wavelength: 580, n: 1.519, color: '#eab308' },
    { name: 'Yashil', wavelength: 530, n: 1.523, color: '#22c55e' },
    { name: 'Ko\'k', wavelength: 470, n: 1.528, color: '#3b82f6' },
    { name: 'Binafsha', wavelength: 400, n: 1.536, color: '#8b5cf6' },
];

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Dispersiya', value: (colors[5].n - colors[0].n).toFixed(4), unit: 'Δn' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Prism
    const prismX = 350;
    const prismY = 250;
    const prismSize = 150;
    const angleRad = prismAngle.value * Math.PI / 180;

    c.beginPath();
    c.moveTo(prismX, prismY - prismSize * Math.sin(angleRad / 2));
    c.lineTo(prismX - prismSize * Math.cos(angleRad / 2), prismY + prismSize * 0.5);
    c.lineTo(prismX + prismSize * Math.cos(angleRad / 2), prismY + prismSize * 0.5);
    c.closePath();

    const prismGrad = c.createLinearGradient(prismX - prismSize, prismY, prismX + prismSize, prismY);
    prismGrad.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    prismGrad.addColorStop(0.5, 'rgba(139, 92, 246, 0.3)');
    prismGrad.addColorStop(1, 'rgba(59, 130, 246, 0.3)');
    c.fillStyle = prismGrad;
    c.fill();
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.stroke();

    // White light beam
    c.strokeStyle = 'white';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(50, prismY);
    c.lineTo(prismX - 60, prismY);
    c.stroke();

    // Arrow
    c.fillStyle = 'white';
    c.beginPath();
    c.moveTo(prismX - 60, prismY);
    c.lineTo(prismX - 75, prismY - 8);
    c.lineTo(prismX - 75, prismY + 8);
    c.closePath();
    c.fill();

    // Dispersed light
    if (showColors.value) {
        colors.forEach((color, i) => {
            const deviation = (color.n - 1.5) * 50;
            const startY = prismY + i * 8 - 20;
            const endY = prismY + 100 + deviation * 3;
            const endX = w - 100 + (5 - i) * 10;

            c.strokeStyle = color.color;
            c.lineWidth = 6;
            c.beginPath();
            c.moveTo(prismX + 60, startY);
            c.quadraticCurveTo(prismX + 150, prismY + 50, endX, endY);
            c.stroke();

            // Color label
            c.fillStyle = color.color;
            c.font = '11px Inter';
            c.textAlign = 'left';
            c.fillText(color.name, endX + 10, endY + 4);
        });
    }

    // Screen
    c.fillStyle = '#1e293b';
    c.fillRect(w - 80, 150, 30, 250);

    if (showColors.value) {
        const spectrumGrad = c.createLinearGradient(0, 180, 0, 380);
        spectrumGrad.addColorStop(0, '#8b5cf6');
        spectrumGrad.addColorStop(0.2, '#3b82f6');
        spectrumGrad.addColorStop(0.4, '#22c55e');
        spectrumGrad.addColorStop(0.6, '#eab308');
        spectrumGrad.addColorStop(0.8, '#f97316');
        spectrumGrad.addColorStop(1, '#ef4444');
        c.fillStyle = spectrumGrad;
        c.fillRect(w - 75, 180, 20, 200);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Prizma burchagi: ${prismAngle.value}°`, 35, 45);
    c.fillText('Sindirish ko\'rsatkichlari:', 35, 70);

    c.fillStyle = '#ef4444';
    c.fillText(`Qizil (700nm): n = 1.513`, 35, 90);
    c.fillStyle = '#8b5cf6';
    c.fillText(`Binafsha (400nm): n = 1.536`, 35, 110);
    c.fillStyle = '#f59e0b';
    c.fillText(`Δn = 0.023`, 35, 135);

    // Title
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText('Yorug\'lik dispersiyasi', 35, 160);
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
