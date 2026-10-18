<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { parallelCircuit, toFixedSafe } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const r1 = ref(100);
const r2 = ref(200);
const r3 = ref(150);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let lastTs = null;
// Dot offsets [px] advanced by real elapsed time with speed ∝ the current in that wire:
// rail segments carry Iₜ, I₂+I₃, I₃ (KCL at each junction); branches carry Iₖ.
const railOffsets = ref([0, 0, 0]);
const branchOffsets = ref([0, 0, 0]);

// Exact parallel solution: 1/R = Σ1/Rk, Ik = U/Rk, Iₜ = ΣIk = U/R
const solution = computed(() => parallelCircuit(voltage.value, [r1.value, r2.value, r3.value]));
const totalResistance = computed(() => solution.value.totalResistance);
const totalCurrent = computed(() => solution.value.totalCurrent);
const i1 = computed(() => solution.value.branchCurrents[0]);
const i2 = computed(() => solution.value.branchCurrents[1]);
const i3 = computed(() => solution.value.branchCurrents[2]);

const canvasConfig = computed(() => ({ width: 900, height: 550 }));

const PX_PER_SEC_PER_AMP = 400; // visual drift speed scale
const DOT_SPACING = 30;         // px between dots
const railCurrents = computed(() => [totalCurrent.value, i2.value + i3.value, i3.value]);

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const measure = () => {
    emit('measurement', { name: 'Umumiy qarshilik', value: toFixedSafe(totalResistance.value, 2), unit: 'Ω' });
};

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused && Number.isFinite(totalCurrent.value)) {
        const step = (o, i) => (o + i * PX_PER_SEC_PER_AMP * dt) % DOT_SPACING;
        railOffsets.value = railOffsets.value.map((o, k) => step(o, railCurrents.value[k]));
        branchOffsets.value = branchOffsets.value.map((o, k) => step(o, solution.value.branchCurrents[k]));
    }
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

    // Top and bottom rails end at the last branch. (A vertical wire joining the
    // rails at the far right would be a 0 Ω branch that shorts the battery.)
    const branchXs = [startX + 150, startX + 280, startX + 410];
    c.beginPath();
    c.moveTo(startX, centerY - 150);
    c.lineTo(branchXs[2], centerY - 150);
    c.moveTo(startX, centerY + 150);
    c.lineTo(branchXs[2], centerY + 150);
    c.stroke();

    // Battery (+ at the top: conventional current leaves along the top rail)
    c.fillStyle = '#22c55e';
    c.fillRect(startX - 15, centerY - 25, 30, 50);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, startX, centerY + 5);
    c.fillText('+', startX, centerY - 35);
    c.fillText('−', startX, centerY + 45);

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
        c.fillText(`${toFixedSafe(current * 1000, 1)}mA`, x, y + 20);
    };

    drawBranch(branchXs[0], centerY, r1.value, i1.value, 'R₁');
    drawBranch(branchXs[1], centerY, r2.value, i2.value, 'R₂');
    drawBranch(branchXs[2], centerY, r3.value, i3.value, 'R₃');

    // Current dots: speed ∝ current in each wire, so KCL (Iₜ = I₁+I₂+I₃) and the
    // division Iₖ = U/Rₖ are visible. Top rail flows right, branches flow down,
    // bottom rail flows back left to the battery.
    const dot = (x, y, r) => { c.beginPath(); c.arc(x, y, r, 0, Math.PI * 2); c.fill(); };
    c.fillStyle = '#60a5fa';
    branchXs.forEach((bx, k) => {
        for (let d = branchOffsets.value[k]; d < 300; d += DOT_SPACING) {
            const by = centerY - 150 + d;
            if (by > centerY - 42 && by < centerY + 42) continue; // inside the resistor box
            dot(bx, by, 3);
        }
    });
    c.fillStyle = '#3b82f6';
    const railStarts = [startX, branchXs[0], branchXs[1]];
    const railEnds = branchXs;
    railStarts.forEach((x0, k) => {
        const len = railEnds[k] - x0;
        for (let d = railOffsets.value[k]; d < len; d += DOT_SPACING) {
            dot(x0 + d, centerY - 150, 4);            // top rail →
            dot(railEnds[k] - d, centerY + 150, 4);   // bottom rail ←
        }
    });

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 260, 20, 240, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`U = ${voltage.value} V (har bir tarmoqda)`, w - 245, 45);
    c.fillStyle = '#3b82f6';
    c.fillText(`I₁ = U/R₁ = ${toFixedSafe(i1.value * 1000, 1)} mA`, w - 245, 70);
    c.fillText(`I₂ = U/R₂ = ${toFixedSafe(i2.value * 1000, 1)} mA`, w - 245, 90);
    c.fillText(`I₃ = U/R₃ = ${toFixedSafe(i3.value * 1000, 1)} mA`, w - 245, 110);
    c.fillStyle = '#ef4444';
    c.fillText(`Iₜ = I₁+I₂+I₃ = ${toFixedSafe(totalCurrent.value * 1000, 1)} mA`, w - 245, 135);
    c.fillStyle = '#10b981';
    c.fillText(`Rₜ = ${toFixedSafe(totalResistance.value, 2)} Ω`, w - 245, 160);

    // Formula
    c.fillStyle = '#f59e0b';
    c.font = '12px serif';
    c.fillText('1/Rₜ = 1/R₁ + 1/R₂ + 1/R₃', w - 245, 185);
};

watch([voltage, r1, r2, r3], () => {
    draw();
    emit('update:state', {
        ...(props.state || {}),
        voltage: voltage.value,
        resistances: [r1.value, r2.value, r3.value],
        totalResistance: totalResistance.value,
        totalCurrent: totalCurrent.value,
        branchCurrents: [i1.value, i2.value, i3.value],
    });
});
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
