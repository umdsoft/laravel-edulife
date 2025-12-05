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

const totalResistance = computed(() => 1 / (1 / r1.value + 1 / r2.value + 1 / r3.value));
const totalCurrent = computed(() => voltage.value / totalResistance.value);
const i1 = computed(() => voltage.value / r1.value);
const i2 = computed(() => voltage.value / r2.value);
const i3 = computed(() => voltage.value / r3.value);

const canvasConfig = computed(() => ({ width: 900, height: 550 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Umumiy qarshilik', value: totalResistance.value.toFixed(2), unit: 'Ω' });
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

    const startX = 120;
    const centerY = h / 2;
    const branchSpacing = 100;

    // Main wires
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;

    // Left side
    c.beginPath();
    c.moveTo(startX, centerY - 150);
    c.lineTo(startX, centerY + 150);
    c.stroke();

    // Top and bottom horizontal
    c.beginPath();
    c.moveTo(startX, centerY - 150);
    c.lineTo(startX + 500, centerY - 150);
    c.moveTo(startX, centerY + 150);
    c.lineTo(startX + 500, centerY + 150);
    c.stroke();

    // Right side
    c.beginPath();
    c.moveTo(startX + 500, centerY - 150);
    c.lineTo(startX + 500, centerY + 150);
    c.stroke();

    // Battery
    c.fillStyle = '#22c55e';
    c.fillRect(startX - 15, centerY - 25, 30, 50);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, startX, centerY + 5);

    // Draw parallel resistors
    const drawBranch = (x, y, resistance, current, label) => {
        c.strokeStyle = '#64748b';
        c.lineWidth = 3;

        // Vertical connections
        c.beginPath();
        c.moveTo(x, centerY - 150);
        c.lineTo(x, y - 40);
        c.moveTo(x, y + 40);
        c.lineTo(x, centerY + 150);
        c.stroke();

        // Resistor box
        c.fillStyle = '#1e293b';
        c.fillRect(x - 25, y - 40, 50, 80);
        c.strokeStyle = '#475569';
        c.strokeRect(x - 25, y - 40, 50, 80);

        c.fillStyle = '#94a3b8';
        c.font = '10px Inter';
        c.textAlign = 'center';
        c.fillText(label, x, y - 50);
        c.fillStyle = 'white';
        c.font = '12px Inter';
        c.fillText(`${resistance}Ω`, x, y - 5);
        c.fillStyle = '#3b82f6';
        c.font = '10px Inter';
        c.fillText(`${(current * 1000).toFixed(1)}mA`, x, y + 20);
    };

    drawBranch(startX + 150, centerY, r1.value, i1.value, 'R₁');
    drawBranch(startX + 280, centerY, r2.value, i2.value, 'R₂');
    drawBranch(startX + 410, centerY, r3.value, i3.value, 'R₃');

    // Current dots animation
    c.fillStyle = '#3b82f6';
    for (let i = 0; i < 8; i++) {
        const pos = (time.value + i / 8) % 1;
        // Top wire
        c.beginPath();
        c.arc(startX + pos * 500, centerY - 150, 4, 0, Math.PI * 2);
        c.fill();
        // Bottom wire
        c.beginPath();
        c.arc(startX + 500 - pos * 500, centerY + 150, 4, 0, Math.PI * 2);
        c.fill();
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 260, 20, 240, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`U = ${voltage.value} V (har bir tarmoqda)`, w - 245, 45);
    c.fillStyle = '#3b82f6';
    c.fillText(`I₁ = U/R₁ = ${(i1.value * 1000).toFixed(1)} mA`, w - 245, 70);
    c.fillText(`I₂ = U/R₂ = ${(i2.value * 1000).toFixed(1)} mA`, w - 245, 90);
    c.fillText(`I₃ = U/R₃ = ${(i3.value * 1000).toFixed(1)} mA`, w - 245, 110);
    c.fillStyle = '#ef4444';
    c.fillText(`Iₜ = I₁+I₂+I₃ = ${(totalCurrent.value * 1000).toFixed(1)} mA`, w - 245, 135);
    c.fillStyle = '#10b981';
    c.fillText(`Rₜ = ${totalResistance.value.toFixed(2)} Ω`, w - 245, 160);

    // Formula
    c.fillStyle = '#f59e0b';
    c.font = '12px serif';
    c.fillText('1/Rₜ = 1/R₁ + 1/R₂ + 1/R₃', w - 245, 185);
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
            <button @click="measure" class="btn measure">📏</button>
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
</style>
