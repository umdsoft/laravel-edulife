<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(5);
const resistance = ref(100);
const diodeType = ref('silicon');

const canvas = ref(null);
const ctx = ref(null);

const diodes = {
    silicon: { name: 'Kremniy', forwardDrop: 0.7, color: '#3b82f6' },
    germanium: { name: 'Germaniy', forwardDrop: 0.3, color: '#22c55e' },
    led: { name: 'LED', forwardDrop: 1.8, color: '#ef4444' },
};

const forwardVoltage = computed(() => diodes[diodeType.value].forwardDrop);
const isForwardBiased = computed(() => voltage.value > forwardVoltage.value);
const current = computed(() => isForwardBiased.value ? (voltage.value - forwardVoltage.value) / resistance.value * 1000 : 0);

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Circuit
    const circuitX = 100;
    const circuitY = 200;

    // Battery
    c.fillStyle = '#22c55e';
    c.fillRect(circuitX, circuitY - 30, 15, 60);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, circuitX + 7, circuitY + 50);
    c.fillText('+', circuitX + 7, circuitY - 40);

    // Wires
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(circuitX + 15, circuitY);
    c.lineTo(circuitX + 100, circuitY);
    c.stroke();

    // Diode
    const diodeColor = diodes[diodeType.value].color;
    c.fillStyle = diodeColor;
    c.beginPath();
    c.moveTo(circuitX + 100, circuitY - 30);
    c.lineTo(circuitX + 100, circuitY + 30);
    c.lineTo(circuitX + 140, circuitY);
    c.closePath();
    c.fill();

    c.strokeStyle = diodeColor;
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(circuitX + 140, circuitY - 30);
    c.lineTo(circuitX + 140, circuitY + 30);
    c.stroke();

    // Glow if LED and conducting
    if (diodeType.value === 'led' && isForwardBiased.value) {
        const glowGrad = c.createRadialGradient(circuitX + 120, circuitY, 0, circuitX + 120, circuitY, 40);
        glowGrad.addColorStop(0, 'rgba(239, 68, 68, 0.8)');
        glowGrad.addColorStop(1, 'transparent');
        c.fillStyle = glowGrad;
        c.fillRect(circuitX + 80, circuitY - 40, 80, 80);
    }

    // Continue wire
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(circuitX + 140, circuitY);
    c.lineTo(circuitX + 200, circuitY);
    c.stroke();

    // Resistor
    c.fillStyle = '#f59e0b';
    c.fillRect(circuitX + 200, circuitY - 15, 60, 30);
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.fillText(`${resistance.value}Ω`, circuitX + 230, circuitY + 5);

    // Complete circuit
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(circuitX + 260, circuitY);
    c.lineTo(circuitX + 320, circuitY);
    c.lineTo(circuitX + 320, circuitY + 100);
    c.lineTo(circuitX, circuitY + 100);
    c.lineTo(circuitX, circuitY + 30);
    c.stroke();

    // Current arrows
    if (isForwardBiased.value && current.value > 0) {
        c.fillStyle = '#22c55e';
        for (let i = 0; i < 3; i++) {
            const offset = i * 80;
            c.beginPath();
            c.moveTo(circuitX + 50 + offset, circuitY - 10);
            c.lineTo(circuitX + 60 + offset, circuitY);
            c.lineTo(circuitX + 50 + offset, circuitY + 10);
            c.fill();
        }
    }

    // IV Characteristic curve
    const graphX = 500;
    const graphY = 50;
    const graphW = 350;
    const graphH = 250;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + graphW / 2, graphY + 20);
    c.lineTo(graphX + graphW / 2, graphY + graphH - 20);
    c.moveTo(graphX + 20, graphY + graphH / 2);
    c.lineTo(graphX + graphW - 20, graphY + graphH / 2);
    c.stroke();

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText('V', graphX + graphW - 30, graphY + graphH / 2 - 10);
    c.fillText('I', graphX + graphW / 2 + 10, graphY + 35);

    // IV curve
    c.strokeStyle = diodeColor;
    c.lineWidth = 2;
    c.beginPath();
    const vf = forwardVoltage.value;
    for (let v = -2; v <= 3; v += 0.05) {
        let i;
        if (v < vf) {
            i = 0;
        } else {
            i = (v - vf) * 20;
        }
        const x = graphX + graphW / 2 + v * 40;
        const y = graphY + graphH / 2 - i * 2;
        if (v === -2) c.moveTo(x, y);
        else c.lineTo(x, Math.max(graphY + 20, y));
    }
    c.stroke();

    // Current operating point
    const opX = graphX + graphW / 2 + voltage.value * 40;
    const opY = graphY + graphH / 2 - current.value * 2;
    c.beginPath();
    c.arc(opX, Math.max(graphY + 20, opY), 6, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Vf marker
    c.strokeStyle = '#f59e0b';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(graphX + graphW / 2 + vf * 40, graphY + graphH / 2);
    c.lineTo(graphX + graphW / 2 + vf * 40, graphY + 20);
    c.stroke();
    c.setLineDash([]);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vf=${vf}V`, graphX + graphW / 2 + vf * 40, graphY + graphH / 2 + 15);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 20, 200, 100);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Diod: ${diodes[diodeType.value].name}`, graphX + 15, graphY + graphH + 45);
    c.fillText(`Vf = ${forwardVoltage.value} V`, graphX + 15, graphY + graphH + 65);
    c.fillStyle = isForwardBiased.value ? '#22c55e' : '#ef4444';
    c.fillText(isForwardBiased.value ? '✓ O\'tkazadi' : '✗ O\'tkazmaydi', graphX + 15, graphY + graphH + 85);
    c.fillText(`I = ${current.value.toFixed(2)} mA`, graphX + 15, graphY + graphH + 105);
};

watch([voltage, resistance, diodeType], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Diod turi:</label>
                <select v-model="diodeType">
                    <option v-for="(d, key) in diodes" :key="key" :value="key">{{ d.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Kuchlanish: <strong>{{ voltage }} V</strong></label>
                <input type="range" v-model.number="voltage" :min="0" :max="10" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Qarshilik: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="50" :max="500" :step="50" />
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
