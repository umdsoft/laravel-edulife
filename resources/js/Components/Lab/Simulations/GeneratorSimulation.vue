<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const rotationSpeed = ref(60);
const coilTurns = ref(100);
const magnetStrength = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);

const angle = ref(0);
const time = ref(0);

const maxEMF = computed(() => coilTurns.value * magnetStrength.value * 0.02);
const currentEMF = computed(() => maxEMF.value * Math.sin(angle.value));
const frequency = computed(() => rotationSpeed.value / 60);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    if (!isRunning.value) return;
    time.value += 0.016;
    angle.value += (rotationSpeed.value / 60) * 2 * Math.PI * 0.016;
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

    const genX = 200;
    const genY = 200;

    // Magnets
    c.fillStyle = '#ef4444';
    c.fillRect(genX - 100, genY - 80, 40, 160);
    c.fillStyle = '#3b82f6';
    c.fillRect(genX + 60, genY - 80, 40, 160);
    c.fillStyle = 'white';
    c.font = 'bold 20px Inter';
    c.textAlign = 'center';
    c.fillText('N', genX - 80, genY + 7);
    c.fillText('S', genX + 80, genY + 7);

    // Rotating coil
    c.save();
    c.translate(genX, genY);
    c.rotate(angle.value);

    c.strokeStyle = '#f59e0b';
    c.lineWidth = 4;
    c.strokeRect(-40, -50, 80, 100);

    c.restore();

    // Axis
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(genX, genY, 10, 0, Math.PI * 2);
    c.fill();

    // Slip rings
    c.save();
    c.translate(genX, genY + 120);
    c.fillStyle = '#f59e0b';
    c.fillRect(-25, 0, 20, 30);
    c.fillRect(5, 0, 20, 30);
    c.restore();

    // Brushes
    c.fillStyle = '#64748b';
    c.fillRect(genX - 30, genY + 155, 25, 20);
    c.fillRect(genX + 5, genY + 155, 25, 20);

    // AC output wave
    const graphX = 420;
    const graphY = 50;
    const graphW = 450;
    const graphH = 180;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 30, graphY + graphH / 2);
    c.lineTo(graphX + graphW - 10, graphY + graphH / 2);
    c.moveTo(graphX + 30, graphY + 10);
    c.lineTo(graphX + 30, graphY + graphH - 10);
    c.stroke();

    // AC wave
    c.strokeStyle = '#22c55e';
    c.lineWidth = 2;
    c.beginPath();
    for (let x = 0; x < graphW - 40; x++) {
        const t = x / 100;
        const emf = maxEMF.value * Math.sin(2 * Math.PI * frequency.value * t);
        const y = graphY + graphH / 2 - (emf / maxEMF.value) * 70;
        if (x === 0) c.moveTo(graphX + 30 + x, y);
        else c.lineTo(graphX + 30 + x, y);
    }
    c.stroke();

    // Current point
    const currentT = (angle.value / (2 * Math.PI)) % 1;
    const currentX = graphX + 30 + currentT * (graphW - 40);
    const currentY = graphY + graphH / 2 - (currentEMF.value / maxEMF.value) * 70;
    c.beginPath();
    c.arc(currentX, currentY, 6, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Labels
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('t', graphX + graphW - 20, graphY + graphH / 2 + 15);
    c.fillText('ε', graphX + 20, graphY + 20);
    c.fillText('+ε₀', graphX + 15, graphY + graphH / 2 - 60);
    c.fillText('-ε₀', graphX + 15, graphY + graphH / 2 + 70);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 30, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Aylanish: ${rotationSpeed.value} RPM`, graphX + 15, graphY + graphH + 55);
    c.fillText(`Chastota: ${frequency.value.toFixed(1)} Hz`, graphX + 15, graphY + graphH + 75);
    c.fillText(`O'ramlar: ${coilTurns.value}`, graphX + 15, graphY + graphH + 95);
    c.fillStyle = '#22c55e';
    c.fillText(`ε₀ = ${maxEMF.value.toFixed(1)} V`, graphX + 15, graphY + graphH + 120);
    c.fillStyle = '#f59e0b';
    c.fillText(`ε = ${currentEMF.value.toFixed(1)} V`, graphX + 15, graphY + graphH + 145);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Aylanish: <strong>{{ rotationSpeed }} RPM</strong></label>
                <input type="range" v-model.number="rotationSpeed" :min="30" :max="120" :step="10" />
            </div>
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="50" :max="200" :step="25" />
            </div>
            <button @click="isRunning = !isRunning" class="btn">
                {{ isRunning ? '⏸️' : '▶️' }}
            </button>
        </div>
        <div class="formula">ε = ε₀ sin(ωt) = NBA⍵ sin(ωt)</div>
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
    font-size: 1.2rem;
}

.formula {
    font-family: serif;
    font-size: 1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
