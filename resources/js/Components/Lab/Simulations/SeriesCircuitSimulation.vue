<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const r1 = ref(100);
const r2 = ref(200);
const r3 = ref(150);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const totalResistance = computed(() => r1.value + r2.value + r3.value);
const current = computed(() => voltage.value / totalResistance.value);
const v1 = computed(() => current.value * r1.value);
const v2 = computed(() => current.value * r2.value);
const v3 = computed(() => current.value * r3.value);
const power = computed(() => voltage.value * current.value);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Umumiy qarshilik', value: totalResistance.value.toFixed(0), unit: 'Ω' });
};

const animate = () => {
    time.value += 0.05;
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

    // Circuit layout
    const startX = 100;
    const startY = 250;
    const wireLen = 120;

    // Wires and components
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;

    // Battery
    c.beginPath();
    c.moveTo(startX, startY - 80);
    c.lineTo(startX, startY + 80);
    c.stroke();
    c.fillStyle = '#22c55e';
    c.fillRect(startX - 15, startY - 25, 30, 50);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, startX, startY + 5);
    c.fillText('+', startX, startY - 35);
    c.fillText('-', startX, startY + 45);

    // Top wire
    c.beginPath();
    c.moveTo(startX, startY - 80);
    c.lineTo(startX + wireLen * 4, startY - 80);
    c.stroke();

    // Bottom wire
    c.beginPath();
    c.moveTo(startX, startY + 80);
    c.lineTo(startX + wireLen * 4, startY + 80);
    c.stroke();

    // Resistors
    const drawResistor = (x, y, resistance, voltageDrop, label) => {
        c.beginPath();
        c.moveTo(x, startY - 80);
        c.lineTo(x, y - 30);
        c.stroke();

        c.fillStyle = '#1e293b';
        c.fillRect(x - 20, y - 30, 40, 60);
        c.strokeStyle = '#64748b';
        c.strokeRect(x - 20, y - 30, 40, 60);

        c.fillStyle = '#94a3b8';
        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText(label, x, y - 45);
        c.fillStyle = 'white';
        c.fillText(`${resistance}Ω`, x, y);
        c.fillStyle = '#f59e0b';
        c.fillText(`${voltageDrop.toFixed(2)}V`, x, y + 18);

        c.beginPath();
        c.strokeStyle = '#64748b';
        c.moveTo(x, y + 30);
        c.lineTo(x, startY + 80);
        c.stroke();
    };

    drawResistor(startX + wireLen, startY, r1.value, v1.value, 'R₁');
    drawResistor(startX + wireLen * 2, startY, r2.value, v2.value, 'R₂');
    drawResistor(startX + wireLen * 3, startY, r3.value, v3.value, 'R₃');

    // Right connection
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + wireLen * 4, startY - 80);
    c.lineTo(startX + wireLen * 4, startY + 80);
    c.stroke();

    // Current flow animation
    const numDots = 12;
    c.fillStyle = '#3b82f6';
    for (let i = 0; i < numDots; i++) {
        const pos = (time.value + i / numDots) % 1;
        const totalLen = wireLen * 4 * 2 + 160;
        const dist = pos * totalLen;

        let dotX, dotY;
        if (dist < wireLen * 4) {
            dotX = startX + dist;
            dotY = startY - 80;
        } else if (dist < wireLen * 4 + 160) {
            dotX = startX + wireLen * 4;
            dotY = startY - 80 + (dist - wireLen * 4);
        } else {
            dotX = startX + wireLen * 4 - (dist - wireLen * 4 - 160);
            dotY = startY + 80;
        }

        c.beginPath();
        c.arc(dotX, dotY, 4, 0, Math.PI * 2);
        c.fill();
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 250, 20, 230, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`U = ${voltage.value} V`, w - 235, 45);
    c.fillText(`R₁ = ${r1.value} Ω, R₂ = ${r2.value} Ω, R₃ = ${r3.value} Ω`, w - 235, 65);
    c.fillStyle = '#ef4444';
    c.fillText(`Rₜ = R₁+R₂+R₃ = ${totalResistance.value} Ω`, w - 235, 90);
    c.fillStyle = '#3b82f6';
    c.fillText(`I = U/Rₜ = ${(current.value * 1000).toFixed(2)} mA`, w - 235, 115);
    c.fillStyle = '#f59e0b';
    c.fillText(`U₁+U₂+U₃ = ${(v1.value + v2.value + v3.value).toFixed(2)} V`, w - 235, 140);
    c.fillStyle = '#10b981';
    c.fillText(`P = ${(power.value * 1000).toFixed(2)} mW`, w - 235, 165);
};

watch([voltage, r1, r2, r3], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Kuchlanish: <strong>{{ voltage }} V</strong></label>
                <input type="range" v-model.number="voltage" :min="3" :max="24" :step="1" />
            </div>
            <div class="control-group">
                <label>R₁: <strong>{{ r1 }} Ω</strong></label>
                <input type="range" v-model.number="r1" :min="50" :max="500" :step="50" />
            </div>
            <div class="control-group">
                <label>R₂: <strong>{{ r2 }} Ω</strong></label>
                <input type="range" v-model.number="r2" :min="50" :max="500" :step="50" />
            </div>
            <div class="control-group">
                <label>R₃: <strong>{{ r3 }} Ω</strong></label>
                <input type="range" v-model.number="r3" :min="50" :max="500" :step="50" />
            </div>
            <button @click="measure" class="btn measure">📏 O'lchash</button>
        </div>
        <div class="formula">Rₜ = R₁ + R₂ + R₃</div>
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
    font-size: 0.75rem;
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

.formula {
    font-family: serif;
    font-size: 1.25rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
