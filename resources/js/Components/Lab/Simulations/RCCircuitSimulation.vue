<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(10);
const resistance = ref(1000);
const capacitance = ref(100);
const circuitType = ref('charge');

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const time = ref(0);
const voltageC = ref(0);
const current = ref(0);

const tau = computed(() => resistance.value * capacitance.value * 1e-6);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    time.value = 0;
    if (circuitType.value === 'charge') {
        voltageC.value = 0;
        current.value = voltage.value / resistance.value;
    } else {
        voltageC.value = voltage.value;
        current.value = -voltage.value / resistance.value;
    }
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    time.value = 0;
    voltageC.value = 0;
    current.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value) return;
    const dt = 0.016;
    time.value += dt;

    if (circuitType.value === 'charge') {
        voltageC.value = voltage.value * (1 - Math.exp(-time.value / tau.value));
        current.value = (voltage.value / resistance.value) * Math.exp(-time.value / tau.value);
    } else {
        voltageC.value = voltage.value * Math.exp(-time.value / tau.value);
        current.value = -(voltage.value / resistance.value) * Math.exp(-time.value / tau.value);
    }

    if (time.value > tau.value * 5) {
        isRunning.value = false;
    }

    draw();
    if (isRunning.value) animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Voltage graph
    const graphX = 50;
    const graphY = 50;
    const graphW = 380;
    const graphH = 150;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 40, graphY + graphH - 20);
    c.lineTo(graphX + 40, graphY + 10);
    c.lineTo(graphX + graphW - 10, graphY + 10);
    c.stroke();

    // Voltage curve
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let t = 0; t <= tau.value * 5; t += 0.01) {
        const v = circuitType.value === 'charge'
            ? voltage.value * (1 - Math.exp(-t / tau.value))
            : voltage.value * Math.exp(-t / tau.value);
        const x = graphX + 40 + (t / (tau.value * 5)) * (graphW - 60);
        const y = graphY + graphH - 20 - (v / voltage.value) * (graphH - 40);
        if (t === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current point
    if (time.value > 0 && time.value < tau.value * 5) {
        const x = graphX + 40 + (time.value / (tau.value * 5)) * (graphW - 60);
        const y = graphY + graphH - 20 - (voltageC.value / voltage.value) * (graphH - 40);
        c.beginPath();
        c.arc(x, y, 5, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
    }

    c.fillStyle = '#3b82f6';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText('Vc(t)', graphX + 10, graphY + 30);

    // Current graph
    const graphY2 = 230;
    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY2, graphW, graphH);

    c.strokeStyle = '#475569';
    c.beginPath();
    c.moveTo(graphX + 40, graphY2 + graphH / 2);
    c.lineTo(graphX + graphW - 10, graphY2 + graphH / 2);
    c.moveTo(graphX + 40, graphY2 + 10);
    c.lineTo(graphX + 40, graphY2 + graphH - 10);
    c.stroke();

    // Current curve
    c.strokeStyle = '#22c55e';
    c.lineWidth = 2;
    c.beginPath();
    const maxI = voltage.value / resistance.value;
    for (let t = 0; t <= tau.value * 5; t += 0.01) {
        const i = circuitType.value === 'charge'
            ? maxI * Math.exp(-t / tau.value)
            : -maxI * Math.exp(-t / tau.value);
        const x = graphX + 40 + (t / (tau.value * 5)) * (graphW - 60);
        const y = graphY2 + graphH / 2 - (i / maxI) * (graphH / 2 - 20);
        if (t === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    c.fillStyle = '#22c55e';
    c.fillText('I(t)', graphX + 10, graphY2 + 30);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(500, 50, 350, 200);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`R = ${resistance.value} Ω`, 520, 80);
    c.fillText(`C = ${capacitance.value} µF`, 520, 100);
    c.fillStyle = '#f59e0b';
    c.fillText(`τ = RC = ${(tau.value * 1000).toFixed(1)} ms`, 520, 125);

    c.fillStyle = '#3b82f6';
    c.fillText(`Vc = ${voltageC.value.toFixed(2)} V`, 520, 155);
    c.fillStyle = '#22c55e';
    c.fillText(`I = ${(current.value * 1000).toFixed(2)} mA`, 520, 175);
    c.fillStyle = '#64748b';
    c.fillText(`t = ${(time.value * 1000).toFixed(0)} ms`, 520, 195);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText(circuitType.value === 'charge' ? 'Vc = V₀(1 - e^(-t/τ))' : 'Vc = V₀ × e^(-t/τ)', 520, 225);
    c.fillText(circuitType.value === 'charge' ? 'I = I₀ × e^(-t/τ)' : 'I = -I₀ × e^(-t/τ)', 520, 245);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>R: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="100" :max="5000" :step="100"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>C: <strong>{{ capacitance }} µF</strong></label>
                <input type="range" v-model.number="capacitance" :min="10" :max="500" :step="10"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Jarayon:</label>
                <select v-model="circuitType" :disabled="isRunning">
                    <option value="charge">Zaryadlash</option>
                    <option value="discharge">Razryad</option>
                </select>
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="reset" class="btn reset">🔄</button>
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

.control-group select {
    padding: 0.5rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
}

.buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #22c55e;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
