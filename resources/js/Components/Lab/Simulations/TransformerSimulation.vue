<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const primaryTurns = ref(100);
const secondaryTurns = ref(200);
const primaryVoltage = ref(220);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const turnsRatio = computed(() => secondaryTurns.value / primaryTurns.value);
const secondaryVoltage = computed(() => primaryVoltage.value * turnsRatio.value);
const isStepUp = computed(() => turnsRatio.value > 1);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Ikkilamchi kuchlanish', value: secondaryVoltage.value.toFixed(0), unit: 'V' });
};

const animate = () => {
    time.value += 0.02;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const centerX = w / 2;
    const centerY = h / 2;

    // Iron core
    c.fillStyle = '#475569';
    c.fillRect(centerX - 80, centerY - 100, 160, 30);
    c.fillRect(centerX - 80, centerY + 70, 160, 30);
    c.fillRect(centerX - 80, centerY - 100, 30, 200);
    c.fillRect(centerX + 50, centerY - 100, 30, 200);

    // Primary coil (left)
    const primaryX = centerX - 120;
    for (let i = 0; i < 8; i++) {
        const y = centerY - 60 + i * 15;
        c.strokeStyle = '#f59e0b';
        c.lineWidth = 4;
        c.beginPath();
        c.ellipse(primaryX, y, 25, 8, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Secondary coil (right)
    const secondaryX = centerX + 120;
    const numSecondary = isStepUp.value ? 12 : 5;
    for (let i = 0; i < numSecondary; i++) {
        const y = centerY - 70 + i * (140 / numSecondary);
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 4;
        c.beginPath();
        c.ellipse(secondaryX, y, 25, 8, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Magnetic flux animation
    const fluxPhase = time.value * 3;
    c.strokeStyle = `rgba(239, 68, 68, ${0.3 + 0.2 * Math.sin(fluxPhase)})`;
    c.lineWidth = 2;
    for (let i = 0; i < 4; i++) {
        const offset = i * 20;
        c.setLineDash([10 + Math.sin(fluxPhase + i) * 5, 5]);
        c.beginPath();
        c.moveTo(centerX - 40, centerY - 80 + offset);
        c.lineTo(centerX + 40, centerY - 80 + offset);
        c.stroke();
    }
    c.setLineDash([]);

    // Input AC symbol
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText('AC', primaryX - 60, centerY - 20);
    c.fillText(`${primaryVoltage.value}V`, primaryX - 60, centerY + 5);

    // AC wave
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < 40; x++) {
        const y = centerY + 40 + Math.sin((x / 5) + time.value * 5) * 10;
        if (x === 0) c.moveTo(primaryX - 80 + x, y);
        else c.lineTo(primaryX - 80 + x, y);
    }
    c.stroke();

    // Output
    c.fillStyle = 'white';
    c.fillText('AC', secondaryX + 60, centerY - 20);
    c.fillStyle = isStepUp.value ? '#22c55e' : '#ef4444';
    c.fillText(`${secondaryVoltage.value.toFixed(0)}V`, secondaryX + 60, centerY + 5);

    // Output wave
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < 40; x++) {
        const y = centerY + 40 + Math.sin((x / 5) + time.value * 5) * 10 * turnsRatio.value * 0.5;
        if (x === 0) c.moveTo(secondaryX + 40 + x, y);
        else c.lineTo(secondaryX + 40 + x, y);
    }
    c.stroke();

    // Labels
    c.font = '12px Inter';
    c.fillStyle = '#f59e0b';
    c.fillText(`N₁ = ${primaryTurns.value}`, primaryX, centerY + 100);
    c.fillStyle = '#3b82f6';
    c.fillText(`N₂ = ${secondaryTurns.value}`, secondaryX, centerY + 100);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Birlamchi o'ramlar: ${primaryTurns.value}`, 35, 45);
    c.fillText(`Ikkilamchi o'ramlar: ${secondaryTurns.value}`, 35, 65);
    c.fillText(`Nisbat: ${turnsRatio.value.toFixed(2)}`, 35, 85);
    c.fillStyle = '#f59e0b';
    c.fillText(`V₁ = ${primaryVoltage.value} V`, 35, 110);
    c.fillStyle = '#3b82f6';
    c.fillText(`V₂ = ${secondaryVoltage.value.toFixed(0)} V`, 35, 130);

    // Type label
    c.fillStyle = isStepUp.value ? '#22c55e' : '#ef4444';
    c.font = 'bold 14px Inter';
    c.textAlign = 'center';
    c.fillText(isStepUp.value ? '⬆️ Ko\'taruvchi' : '⬇️ Tushiruvchi', centerX, h - 30);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>N₁: <strong>{{ primaryTurns }}</strong></label>
                <input type="range" v-model.number="primaryTurns" :min="50" :max="300" :step="25" />
            </div>
            <div class="control-group">
                <label>N₂: <strong>{{ secondaryTurns }}</strong></label>
                <input type="range" v-model.number="secondaryTurns" :min="50" :max="500" :step="25" />
            </div>
            <div class="control-group">
                <label>V₁: <strong>{{ primaryVoltage }} V</strong></label>
                <input type="range" v-model.number="primaryVoltage" :min="110" :max="380" :step="10" />
            </div>
            <button @click="measure" class="btn">📏</button>
        </div>
        <div class="formula">V₂/V₁ = N₂/N₁</div>
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
    min-width: 100px;
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

.formula {
    font-family: serif;
    font-size: 1.1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
