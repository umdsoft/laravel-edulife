<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const capacitance = ref(100); // microfarads
const resistance = ref(1000); // ohms

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const isCharging = ref(true);
const animationId = ref(null);

const charge = ref(0);
const current = ref(0);
const time = ref(0);

const maxCharge = computed(() => capacitance.value * 1e-6 * voltage.value);
const timeConstant = computed(() => resistance.value * capacitance.value * 1e-6);
const capacitorVoltage = computed(() => charge.value / (capacitance.value * 1e-6));

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const startCharge = () => {
    isCharging.value = true;
    charge.value = 0;
    current.value = voltage.value / resistance.value;
    time.value = 0;
    isRunning.value = true;
    animate();
};

const startDischarge = () => {
    isCharging.value = false;
    charge.value = maxCharge.value;
    current.value = -voltage.value / resistance.value;
    time.value = 0;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    charge.value = 0;
    current.value = 0;
    time.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    if (isCharging.value) {
        charge.value = maxCharge.value * (1 - Math.exp(-time.value / timeConstant.value));
        current.value = (voltage.value / resistance.value) * Math.exp(-time.value / timeConstant.value);
    } else {
        charge.value = maxCharge.value * Math.exp(-time.value / timeConstant.value);
        current.value = -(voltage.value / resistance.value) * Math.exp(-time.value / timeConstant.value);
    }

    if (time.value > timeConstant.value * 5) {
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

    // Circuit
    const cx = 200;
    const cy = 200;

    // Battery
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(cx - 80, cy - 80);
    c.lineTo(cx - 80, cy + 80);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.fillRect(cx - 95, cy - 30, 30, 60);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, cx - 80, cy + 5);

    // Wires
    c.strokeStyle = '#64748b';
    c.beginPath();
    c.moveTo(cx - 80, cy - 80);
    c.lineTo(cx + 150, cy - 80);
    c.lineTo(cx + 150, cy - 30);
    c.stroke();

    c.beginPath();
    c.moveTo(cx - 80, cy + 80);
    c.lineTo(cx + 150, cy + 80);
    c.lineTo(cx + 150, cy + 30);
    c.stroke();

    // Capacitor
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(cx + 130, cy - 30);
    c.lineTo(cx + 170, cy - 30);
    c.moveTo(cx + 130, cy + 30);
    c.lineTo(cx + 170, cy + 30);
    c.stroke();

    // Charge level
    const chargePercent = charge.value / maxCharge.value;
    c.fillStyle = '#3b82f6';
    c.globalAlpha = chargePercent;
    c.fillRect(cx + 135, cy - 25, 30, 20);
    c.fillStyle = '#ef4444';
    c.fillRect(cx + 135, cy + 5, 30, 20);
    c.globalAlpha = 1;

    // Current arrows
    if (Math.abs(current.value) > 0.0001) {
        const arrowDir = current.value > 0 ? 1 : -1;
        c.fillStyle = '#f59e0b';
        for (let i = 0; i < 3; i++) {
            const offset = ((time.value * 100 * arrowDir + i * 50) % 150);
            c.beginPath();
            if (arrowDir > 0) {
                c.moveTo(cx - 60 + offset, cy - 80);
                c.lineTo(cx - 70 + offset, cy - 90);
                c.lineTo(cx - 70 + offset, cy - 70);
            } else {
                c.moveTo(cx + 140 - offset, cy + 80);
                c.lineTo(cx + 150 - offset, cy + 90);
                c.lineTo(cx + 150 - offset, cy + 70);
            }
            c.fill();
        }
    }

    // Graphs
    const graphX = 400;

    // Voltage graph
    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, 30, 180, 150);
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.beginPath();
    for (let t = 0; t <= 5 * timeConstant.value; t += 0.01) {
        const v = isCharging.value
            ? voltage.value * (1 - Math.exp(-t / timeConstant.value))
            : voltage.value * Math.exp(-t / timeConstant.value);
        const x = graphX + 20 + (t / (5 * timeConstant.value)) * 140;
        const y = 170 - (v / voltage.value) * 120;
        if (t === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current dot
    if (time.value < 5 * timeConstant.value) {
        const dotX = graphX + 20 + (time.value / (5 * timeConstant.value)) * 140;
        const dotY = 170 - chargePercent * 120;
        c.beginPath();
        c.arc(dotX, dotY, 5, 0, Math.PI * 2);
        c.fillStyle = '#3b82f6';
        c.fill();
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('Vc(t)', graphX + 10, 50);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, 200, 180, 140);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`C = ${capacitance.value} µF`, graphX + 15, 225);
    c.fillText(`R = ${resistance.value} Ω`, graphX + 15, 245);
    c.fillText(`τ = ${(timeConstant.value * 1000).toFixed(1)} ms`, graphX + 15, 265);
    c.fillStyle = '#3b82f6';
    c.fillText(`Vc = ${capacitorVoltage.value.toFixed(2)} V`, graphX + 15, 290);
    c.fillStyle = '#f59e0b';
    c.fillText(`I = ${(current.value * 1000).toFixed(2)} mA`, graphX + 15, 310);
    c.fillStyle = '#64748b';
    c.fillText(`t = ${(time.value * 1000).toFixed(0)} ms`, graphX + 15, 330);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Sig'im: <strong>{{ capacitance }} µF</strong></label>
                <input type="range" v-model.number="capacitance" :min="10" :max="500" :step="10"
                    :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Qarshilik: <strong>{{ resistance }} Ω</strong></label>
                <input type="range" v-model.number="resistance" :min="100" :max="5000" :step="100"
                    :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="startCharge" :disabled="isRunning" class="btn charge">⚡ Zaryadlash</button>
                <button @click="startDischarge" :disabled="isRunning" class="btn discharge">🔋 Razryad</button>
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
    min-width: 140px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.charge {
    background: #3b82f6;
    color: white;
}

.btn.discharge {
    background: #f59e0b;
    color: #1e293b;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
