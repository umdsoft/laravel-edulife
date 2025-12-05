<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const charge1 = ref(10); // microcoulombs
const charge2 = ref(-10);
const showFieldLines = ref(true);
const showEquipotential = ref(true);

const canvas = ref(null);
const ctx = ref(null);

const pos1 = ref({ x: 300, y: 250 });
const pos2 = ref({ x: 500, y: 250 });

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Maydon kuchlanganligi', value: 'N/A', unit: 'V/m' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Grid
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let x = 0; x < w; x += 50) { c.beginPath(); c.moveTo(x, 0); c.lineTo(x, h); c.stroke(); }
    for (let y = 0; y < h; y += 50) { c.beginPath(); c.moveTo(0, y); c.lineTo(w, y); c.stroke(); }

    // Electric field lines
    if (showFieldLines.value) {
        const numLines = 16;
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
        for (let r = 40; r < 200; r += 40) {
            c.strokeStyle = 'rgba(139, 92, 246, 0.2)';
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

    // Charges
    drawCharge(c, pos1.value.x, pos1.value.y, charge1.value);
    drawCharge(c, pos2.value.x, pos2.value.y, charge2.value);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 100);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`q₁ = ${charge1.value} µC`, 35, 45);
    c.fillText(`q₂ = ${charge2.value} µC`, 35, 65);
    c.fillStyle = '#94a3b8';
    c.fillText('Elektr maydon chiziqlari', 35, 90);
};

const drawCharge = (c, x, y, q) => {
    const radius = 25;
    c.beginPath();
    c.arc(x, y, radius, 0, Math.PI * 2);
    c.fillStyle = q > 0 ? '#ef4444' : '#3b82f6';
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 18px Inter';
    c.textAlign = 'center';
    c.fillText(q > 0 ? '+' : '−', x, y + 6);
};

const drawFieldLine = (c, x, y, angle, isPositive) => {
    c.strokeStyle = isPositive ? 'rgba(239, 68, 68, 0.5)' : 'rgba(59, 130, 246, 0.5)';
    c.lineWidth = 2;
    c.beginPath();

    let px = x + 25 * Math.cos(angle);
    let py = y + 25 * Math.sin(angle);
    c.moveTo(px, py);

    for (let i = 0; i < 150; i++) {
        const dx = isPositive ? Math.cos(angle) : -Math.cos(angle);
        const dy = isPositive ? Math.sin(angle) : -Math.sin(angle);

        // Simple curve approximation towards other charge
        const distTo2 = Math.sqrt((px - pos2.value.x) ** 2 + (py - pos2.value.y) ** 2);
        const distTo1 = Math.sqrt((px - pos1.value.x) ** 2 + (py - pos1.value.y) ** 2);

        // Very basic field logic for visualization
        let fx = dx;
        let fy = dy;

        if (isPositive) {
            // Attracted to negative charge
            if (charge2.value < 0) {
                const angleTo2 = Math.atan2(pos2.value.y - py, pos2.value.x - px);
                const weight = Math.min(1, 2000 / (distTo2 ** 2 + 1));
                fx = fx * (1 - weight) + Math.cos(angleTo2) * weight;
                fy = fy * (1 - weight) + Math.sin(angleTo2) * weight;
            }
        }

        px += fx * 4;
        py += fy * 4;

        if (px < 0 || px > 800 || py < 0 || py > 500) break;
        if (distTo2 < 25 || distTo1 < 25 && i > 10) break;

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
                <label>Zaryad 1: <strong :style="{ color: charge1 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge1 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge1" :min="-20" :max="20" :step="2" />
            </div>
            <div class="control-group">
                <label>Zaryad 2: <strong :style="{ color: charge2 > 0 ? '#ef4444' : '#3b82f6' }">{{ charge2 }}
                        µC</strong></label>
                <input type="range" v-model.number="charge2" :min="-20" :max="20" :step="2" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlari</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" v-model="showEquipotential" />
                <span>Ekvipotensial sirtlar</span>
            </label>
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
    min-width: 150px;
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
</style>
