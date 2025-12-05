<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const charge1 = ref(5); // microcoulombs
const charge2 = ref(-3);
const showFieldLines = ref(true);
const showEquipotential = ref(false);

const canvas = ref(null);
const ctx = ref(null);

const pos1 = ref({ x: 250, y: 250 });
const pos2 = ref({ x: 550, y: 250 });

const k = 8.99e9;
const distance = computed(() => Math.sqrt((pos2.value.x - pos1.value.x) ** 2 + (pos2.value.y - pos1.value.y) ** 2) / 100); // meters
const force = computed(() => k * Math.abs(charge1.value * charge2.value * 1e-12) / distance.value ** 2);
const isAttractive = computed(() => charge1.value * charge2.value < 0);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Kulon kuchi', value: (force.value * 1e6).toFixed(3), unit: 'µN' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Electric field lines
    if (showFieldLines.value) {
        const numLines = 12;
        for (let i = 0; i < numLines; i++) {
            const angle = (i / numLines) * Math.PI * 2;
            drawFieldLine(c, pos1.value.x, pos1.value.y, angle, charge1.value > 0);
        }
        for (let i = 0; i < numLines; i++) {
            const angle = (i / numLines) * Math.PI * 2;
            drawFieldLine(c, pos2.value.x, pos2.value.y, angle, charge2.value > 0);
        }
    }

    // Equipotential lines
    if (showEquipotential.value) {
        for (let r = 30; r < 150; r += 30) {
            c.strokeStyle = 'rgba(139, 92, 246, 0.3)';
            c.lineWidth = 1;
            c.setLineDash([5, 5]);
            c.beginPath();
            c.arc(pos1.value.x, pos1.value.y, r, 0, Math.PI * 2);
            c.stroke();
            c.beginPath();
            c.arc(pos2.value.x, pos2.value.y, r, 0, Math.PI * 2);
            c.stroke();
            c.setLineDash([]);
        }
    }

    // Force arrows between charges
    const dx = pos2.value.x - pos1.value.x;
    const dy = pos2.value.y - pos1.value.y;
    const dist = Math.sqrt(dx * dx + dy * dy);
    const ux = dx / dist;
    const uy = dy / dist;

    const arrowLen = Math.min(80, force.value * 1e8);
    c.strokeStyle = isAttractive.value ? '#22c55e' : '#ef4444';
    c.lineWidth = 3;

    // Arrow from charge 1
    const dir1 = isAttractive.value ? 1 : -1;
    c.beginPath();
    c.moveTo(pos1.value.x + 25 * ux, pos1.value.y + 25 * uy);
    c.lineTo(pos1.value.x + (25 + arrowLen * dir1) * ux, pos1.value.y + (25 + arrowLen * dir1) * uy);
    c.stroke();

    // Arrow from charge 2
    const dir2 = isAttractive.value ? -1 : 1;
    c.beginPath();
    c.moveTo(pos2.value.x - 25 * ux, pos2.value.y - 25 * uy);
    c.lineTo(pos2.value.x + (-25 + arrowLen * dir2) * ux, pos2.value.y + (-25 + arrowLen * dir2) * uy);
    c.stroke();

    // Charges
    drawCharge(c, pos1.value.x, pos1.value.y, charge1.value);
    drawCharge(c, pos2.value.x, pos2.value.y, charge2.value);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 140);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`q₁ = ${charge1.value} µC`, 35, 45);
    c.fillText(`q₂ = ${charge2.value} µC`, 35, 65);
    c.fillText(`r = ${(distance.value * 100).toFixed(0)} cm`, 35, 85);
    c.fillStyle = isAttractive.value ? '#22c55e' : '#ef4444';
    c.fillText(`F = ${(force.value * 1e6).toFixed(3)} µN`, 35, 110);
    c.fillText(isAttractive.value ? '↔ Tortishish' : '↔ Itarishish', 35, 130);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('F = kq₁q₂/r²', 35, 150);
};

const drawCharge = (c, x, y, q) => {
    const radius = 20 + Math.abs(q) * 2;
    c.beginPath();
    c.arc(x, y, radius, 0, Math.PI * 2);
    c.fillStyle = q > 0 ? '#ef4444' : '#3b82f6';
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText(q > 0 ? '+' : '−', x, y + 6);
};

const drawFieldLine = (c, x, y, angle, isPositive) => {
    c.strokeStyle = isPositive ? 'rgba(239, 68, 68, 0.4)' : 'rgba(59, 130, 246, 0.4)';
    c.lineWidth = 1.5;
    c.beginPath();

    let px = x + 25 * Math.cos(angle);
    let py = y + 25 * Math.sin(angle);
    c.moveTo(px, py);

    for (let i = 0; i < 100; i++) {
        const dx = isPositive ? Math.cos(angle) : -Math.cos(angle);
        const dy = isPositive ? Math.sin(angle) : -Math.sin(angle);
        px += dx * 3;
        py += dy * 3;

        if (px < 0 || px > 800 || py < 0 || py > 500) break;
        c.lineTo(px, py);
    }
    c.stroke();
};

watch([charge1, charge2, showFieldLines, showEquipotential], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>q₁: <strong :style="{ color: charge1 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge1 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge1" :min="-10" :max="10" :step="1" />
            </div>
            <div class="control-group">
                <label>q₂: <strong :style="{ color: charge2 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge2 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge2" :min="-10" :max="10" :step="1" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlari</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" v-model="showEquipotential" />
                <span>Ekvipotensial</span>
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
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
    flex-wrap: wrap;
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

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.85rem;
    cursor: pointer;
}

.checkbox input {
    width: 16px;
    height: 16px;
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
