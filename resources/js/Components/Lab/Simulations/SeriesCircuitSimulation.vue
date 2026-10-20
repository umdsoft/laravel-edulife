<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { seriesCircuit, toFixedSafe, createDmm } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const r1 = ref(100);
const r2 = ref(200);
const r3 = ref(150);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const phase = ref(0); // position of the current dots along the loop (0…1)
let lastTs = null;

// Exact series solution: R = ΣR, I = U/R, Uk = I·Rk, P = U·I
const solution = computed(() => seriesCircuit(voltage.value, [r1.value, r2.value, r3.value]));
const totalResistance = computed(() => solution.value.totalResistance);
const current = computed(() => solution.value.current);
const v1 = computed(() => solution.value.voltageDrops[0]);
const v2 = computed(() => solution.value.voltageDrops[1]);
const v3 = computed(() => solution.value.voltageDrops[2]);
const power = computed(() => solution.value.totalPower);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

// Visual drift speed ∝ I (loops per second per ampere); advanced by real elapsed time.
const DOT_LOOPS_PER_AMP = 5;

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

// The total resistance is measured like in a real lab: power off, a 3½-digit
// auto-ranging ohmmeter (DMM, Ω function) across the whole R₁–R₂–R₃ chain.
const ohmmeter = createDmm('resistance', 'series-circuit');
const measure = () => {
    const r = ohmmeter.read(totalResistance.value, [r1.value, r2.value, r3.value]);
    if (r.overload) return;
    emit('measurement', { name: 'Umumiy qarshilik', value: r.text, unit: r.unit });
};

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused && Number.isFinite(current.value)) {
        phase.value = (phase.value + current.value * DOT_LOOPS_PER_AMP * dt) % 1;
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

    // Resistors — in SERIES: each one sits inline on the top wire, so the same
    // current flows through all of them. (Previously they were drawn between the
    // top and bottom rails, which is a parallel connection.)
    const drawResistor = (x, resistance, voltageDrop, label) => {
        const y = startY - 80;
        c.fillStyle = '#1e293b';
        c.fillRect(x - 30, y - 15, 60, 30);
        c.strokeStyle = '#64748b';
        c.lineWidth = 3;
        c.strokeRect(x - 30, y - 15, 60, 30);

        c.fillStyle = '#94a3b8';
        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText(label, x, y - 25);
        c.fillStyle = 'white';
        c.fillText(`${resistance}Ω`, x, y + 4);
        c.fillStyle = '#f59e0b';
        c.fillText(`${toFixedSafe(voltageDrop, 2)}V`, x, y + 35);
    };

    drawResistor(startX + wireLen, r1.value, v1.value, 'R₁');
    drawResistor(startX + wireLen * 2, r2.value, v2.value, 'R₂');
    drawResistor(startX + wireLen * 3, r3.value, v3.value, 'R₃');

    // Right connection
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(startX + wireLen * 4, startY - 80);
    c.lineTo(startX + wireLen * 4, startY + 80);
    c.stroke();

    // Current flow animation (conventional current: out of + along the top wire,
    // down the right side, back along the bottom wire and up through the battery)
    const numDots = 16;
    const loopW = wireLen * 4;
    const totalLen = 2 * loopW + 2 * 160;
    c.fillStyle = '#3b82f6';
    for (let i = 0; i < numDots; i++) {
        const pos = (phase.value + i / numDots) % 1;
        const dist = pos * totalLen;

        let dotX, dotY;
        if (dist < loopW) {
            dotX = startX + dist;
            dotY = startY - 80;
        } else if (dist < loopW + 160) {
            dotX = startX + loopW;
            dotY = startY - 80 + (dist - loopW);
        } else if (dist < 2 * loopW + 160) {
            dotX = startX + loopW - (dist - loopW - 160);
            dotY = startY + 80;
        } else {
            dotX = startX;
            dotY = startY + 80 - (dist - 2 * loopW - 160);
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
    c.fillText(`I = U/Rₜ = ${toFixedSafe(current.value * 1000, 2)} mA`, w - 235, 115);
    c.fillStyle = '#f59e0b';
    c.fillText(`U₁+U₂+U₃ = ${toFixedSafe(v1.value + v2.value + v3.value, 2)} V`, w - 235, 140);
    c.fillStyle = '#10b981';
    c.fillText(`P = ${toFixedSafe(power.value * 1000, 2)} mW`, w - 235, 165);
};

watch([voltage, r1, r2, r3], () => {
    draw();
    emit('update:state', {
        ...(props.state || {}),
        voltage: voltage.value,
        resistances: [r1.value, r2.value, r3.value],
        totalResistance: totalResistance.value,
        current: current.value,
        voltageDrops: [v1.value, v2.value, v3.value],
        power: power.value,
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
