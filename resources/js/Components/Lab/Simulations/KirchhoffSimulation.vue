<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const emf = ref(12);
const r1 = ref(4);
const r2 = ref(6);
const r3 = ref(3);

const canvas = ref(null);
const ctx = ref(null);

// Kirchhoff analysis
const totalR = computed(() => r1.value + (r2.value * r3.value) / (r2.value + r3.value));
const mainCurrent = computed(() => emf.value / totalR.value);
const i2 = computed(() => mainCurrent.value * r3.value / (r2.value + r3.value));
const i3 = computed(() => mainCurrent.value * r2.value / (r2.value + r3.value));
const v1 = computed(() => mainCurrent.value * r1.value);
const v23 = computed(() => emf.value - v1.value);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Asosiy tok', value: mainCurrent.value.toFixed(3), unit: 'A' });
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
    const startY = 150;

    // Main wire
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;

    // Top wire (from battery through R1)
    c.beginPath();
    c.moveTo(startX, startY);
    c.lineTo(startX + 100, startY);
    c.stroke();

    // R1
    c.fillStyle = '#f59e0b';
    c.fillRect(startX + 100, startY - 15, 80, 30);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`R₁=${r1.value}Ω`, startX + 140, startY + 5);

    // After R1 to junction
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + 180, startY);
    c.lineTo(startX + 280, startY);
    c.stroke();

    // Junction point
    c.beginPath();
    c.arc(startX + 280, startY, 5, 0, Math.PI * 2);
    c.fillStyle = '#22c55e';
    c.fill();

    // Upper branch (R2)
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + 280, startY);
    c.lineTo(startX + 280, startY - 60);
    c.lineTo(startX + 330, startY - 60);
    c.stroke();

    c.fillStyle = '#3b82f6';
    c.fillRect(startX + 330, startY - 75, 80, 30);
    c.fillStyle = 'white';
    c.fillText(`R₂=${r2.value}Ω`, startX + 370, startY - 55);

    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + 410, startY - 60);
    c.lineTo(startX + 480, startY - 60);
    c.lineTo(startX + 480, startY);
    c.stroke();

    // Lower branch (R3)
    c.beginPath();
    c.moveTo(startX + 280, startY);
    c.lineTo(startX + 280, startY + 60);
    c.lineTo(startX + 330, startY + 60);
    c.stroke();

    c.fillStyle = '#ef4444';
    c.fillRect(startX + 330, startY + 45, 80, 30);
    c.fillStyle = 'white';
    c.fillText(`R₃=${r3.value}Ω`, startX + 370, startY + 65);

    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + 410, startY + 60);
    c.lineTo(startX + 480, startY + 60);
    c.lineTo(startX + 480, startY);
    c.stroke();

    // Junction 2
    c.beginPath();
    c.arc(startX + 480, startY, 5, 0, Math.PI * 2);
    c.fillStyle = '#22c55e';
    c.fill();

    // Return wire
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + 480, startY);
    c.lineTo(startX + 550, startY);
    c.lineTo(startX + 550, startY + 150);
    c.lineTo(startX, startY + 150);
    c.lineTo(startX, startY + 50);
    c.stroke();

    // Battery
    c.fillStyle = '#22c55e';
    c.fillRect(startX - 5, startY + 10, 10, 40);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText(`${emf.value}V`, startX, startY + 70);

    // Current arrows
    const arrowSize = 8;
    c.fillStyle = '#f59e0b';

    // I main
    c.beginPath();
    c.moveTo(startX + 60, startY - 5);
    c.lineTo(startX + 60 + arrowSize, startY - 5 - arrowSize);
    c.lineTo(startX + 60 + arrowSize, startY - 5 + arrowSize);
    c.closePath();
    c.fill();
    c.font = '11px Inter';
    c.fillText(`I=${mainCurrent.value.toFixed(2)}A`, startX + 60, startY - 20);

    // I2
    c.fillStyle = '#3b82f6';
    c.fillText(`I₂=${i2.value.toFixed(2)}A`, startX + 340, startY - 90);

    // I3
    c.fillStyle = '#ef4444';
    c.fillText(`I₃=${i3.value.toFixed(2)}A`, startX + 340, startY + 100);

    // Kirchhoff's Laws info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 300, 350, 130);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Kirxgof qonunlari:', 35, 325);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText('1. Tugun qonuni: ΣI = 0', 35, 350);
    c.fillText(`   I = I₂ + I₃ → ${mainCurrent.value.toFixed(2)} = ${i2.value.toFixed(2)} + ${i3.value.toFixed(2)}`, 35, 370);
    c.fillText('2. Kontur qonuni: ΣV = 0', 35, 395);
    c.fillText(`   ${emf.value}V = ${v1.value.toFixed(2)}V + ${v23.value.toFixed(2)}V`, 35, 415);
};

watch([emf, r1, r2, r3], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>EMK: <strong>{{ emf }} V</strong></label>
                <input type="range" v-model.number="emf" :min="6" :max="24" :step="2" />
            </div>
            <div class="control-group">
                <label>R₁: <strong>{{ r1 }} Ω</strong></label>
                <input type="range" v-model.number="r1" :min="1" :max="10" :step="1" />
            </div>
            <div class="control-group">
                <label>R₂: <strong>{{ r2 }} Ω</strong></label>
                <input type="range" v-model.number="r2" :min="2" :max="12" :step="1" />
            </div>
            <div class="control-group">
                <label>R₃: <strong>{{ r3 }} Ω</strong></label>
                <input type="range" v-model.number="r3" :min="2" :max="12" :step="1" />
            </div>
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
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 80px;
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
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
