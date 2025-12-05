<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const naturalFreq = ref(2);
const drivingFreq = ref(2);
const damping = ref(0.1);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);

const amplitude = ref(0);
const phase = ref(0);
const time = ref(0);

const frequencyRatio = computed(() => drivingFreq.value / naturalFreq.value);
const isResonance = computed(() => Math.abs(frequencyRatio.value - 1) < 0.1);
const theoreticalAmp = computed(() => {
    const r = frequencyRatio.value;
    const z = damping.value;
    return 1 / Math.sqrt((1 - r * r) ** 2 + (2 * z * r) ** 2);
});

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    if (!isRunning.value) return;
    const dt = 1 / 60;
    time.value += dt;

    // Simplified forced oscillation
    const w = drivingFreq.value * 2 * Math.PI;
    const w0 = naturalFreq.value * 2 * Math.PI;
    const gamma = damping.value * w0;

    amplitude.value = theoreticalAmp.value * 50;
    phase.value = w * time.value;

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

    // Spring system
    const baseX = 150;
    const baseY = 50;
    const massY = 200 + amplitude.value * Math.sin(phase.value);

    // Fixed support
    c.fillStyle = '#475569';
    c.fillRect(baseX - 30, baseY - 10, 60, 20);

    // Spring
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    const springTop = baseY + 10;
    const springBottom = massY - 25;
    const numCoils = 10;
    for (let i = 0; i <= numCoils * 4; i++) {
        const t = i / (numCoils * 4);
        const y = springTop + t * (springBottom - springTop);
        const x = baseX + Math.sin(i * Math.PI / 2) * 15;
        if (i === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Mass
    c.fillStyle = isResonance.value ? '#ef4444' : '#3b82f6';
    c.fillRect(baseX - 25, massY - 25, 50, 50);

    // Driving force visualization
    c.fillStyle = '#22c55e';
    const forceY = 350;
    const forceOffset = 50 * Math.sin(drivingFreq.value * 2 * Math.PI * time.value);
    c.beginPath();
    c.arc(baseX, forceY + forceOffset * 0.5, 15, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('F(t)', baseX, forceY + 40);

    // Frequency response curve
    const graphX = 400;
    const graphY = 50;
    const graphW = 450;
    const graphH = 250;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 50, graphY + graphH - 30);
    c.lineTo(graphX + 50, graphY + 20);
    c.lineTo(graphX + graphW - 20, graphY + 20);
    c.stroke();

    // Response curve
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let f = 0.1; f <= 4; f += 0.05) {
        const r = f / naturalFreq.value;
        const z = damping.value;
        const amp = 1 / Math.sqrt((1 - r * r) ** 2 + (2 * z * r) ** 2);
        const x = graphX + 50 + (f / 4) * (graphW - 70);
        const y = graphY + graphH - 30 - Math.min(amp, 10) * 20;
        if (f === 0.1) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current point
    const currentX = graphX + 50 + (drivingFreq.value / 4) * (graphW - 70);
    const currentY = graphY + graphH - 30 - Math.min(theoreticalAmp.value, 10) * 20;
    c.beginPath();
    c.arc(currentX, currentY, 8, 0, Math.PI * 2);
    c.fillStyle = isResonance.value ? '#ef4444' : '#22c55e';
    c.fill();

    // Resonance marker
    const resX = graphX + 50 + (naturalFreq.value / 4) * (graphW - 70);
    c.strokeStyle = '#f59e0b';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(resX, graphY + graphH - 30);
    c.lineTo(resX, graphY + 30);
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText('f₀', resX, graphY + graphH - 10);

    // Labels
    c.fillStyle = 'white';
    c.textAlign = 'left';
    c.fillText('A', graphX + 30, graphY + 30);
    c.textAlign = 'center';
    c.fillText('f', graphX + graphW - 30, graphY + graphH - 10);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 20, 200, 110);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tabiiy chastota: ${naturalFreq.value.toFixed(1)} Hz`, graphX + 15, graphY + graphH + 45);
    c.fillText(`Majburlovchi: ${drivingFreq.value.toFixed(1)} Hz`, graphX + 15, graphY + graphH + 65);
    c.fillText(`So'nish: ${damping.value.toFixed(2)}`, graphX + 15, graphY + graphH + 85);
    c.fillStyle = isResonance.value ? '#ef4444' : '#94a3b8';
    c.font = 'bold 12px Inter';
    c.fillText(isResonance.value ? '⚠️ REZONANS!' : 'Normal holat', graphX + 15, graphY + graphH + 110);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tabiiy f₀: <strong>{{ naturalFreq.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="naturalFreq" :min="1" :max="4" :step="0.2" />
            </div>
            <div class="control-group">
                <label>Majburlovchi f: <strong>{{ drivingFreq.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="drivingFreq" :min="0.5" :max="4" :step="0.1" />
            </div>
            <div class="control-group">
                <label>So'nish: <strong>{{ damping.toFixed(2) }}</strong></label>
                <input type="range" v-model.number="damping" :min="0.05" :max="0.5" :step="0.05" />
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
</style>
