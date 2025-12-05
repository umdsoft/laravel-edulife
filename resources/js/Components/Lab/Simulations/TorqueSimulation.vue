<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const rodLength = ref(2);
const force = ref(20);
const forceAngle = ref(90);
const pivotPos = ref(50); // % from left

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const rotation = ref(0);
const angularVel = ref(0);
const time = ref(0);

const momentArm = computed(() => rodLength.value * (1 - pivotPos.value / 100));
const torque = computed(() => force.value * momentArm.value * Math.sin(forceAngle.value * Math.PI / 180));
const inertia = computed(() => (1 / 12) * 1 * rodLength.value ** 2); // Assuming uniform rod mass 1kg

const canvasConfig = computed(() => ({ width: 800, height: 500 }));
const centerX = 400;
const centerY = 250;
const scale = 100;

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    rotation.value = 0;
    angularVel.value = 0;
    time.value = 0;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    rotation.value = 0;
    angularVel.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Moment (F=${force.value}N, r=${momentArm.value.toFixed(2)}m)`, value: torque.value.toFixed(2), unit: 'N·m' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;

    const alpha = torque.value / inertia.value;
    angularVel.value += alpha * dt * 0.01;
    rotation.value += angularVel.value * dt;
    time.value += dt;

    if (Math.abs(rotation.value) > Math.PI / 2) {
        rotation.value = Math.sign(rotation.value) * Math.PI / 2;
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

    // Pivot point (fixed)
    const pivotX = centerX;
    const pivotY = centerY;

    c.save();
    c.translate(pivotX, pivotY);
    c.rotate(rotation.value);

    // Rod
    const leftEnd = -rodLength.value * scale * pivotPos.value / 100;
    const rightEnd = rodLength.value * scale * (1 - pivotPos.value / 100);

    c.fillStyle = '#64748b';
    c.fillRect(leftEnd, -10, rodLength.value * scale, 20);

    // Force arrow
    const forceX = rightEnd - 20;
    const forceY = 0;
    const fLen = force.value * 2;
    const fAngle = (forceAngle.value - 90) * Math.PI / 180;

    c.strokeStyle = '#22c55e';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(forceX, forceY);
    c.lineTo(forceX + fLen * Math.cos(fAngle), forceY + fLen * Math.sin(fAngle));
    c.stroke();

    // Force label
    c.fillStyle = '#22c55e';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`F = ${force.value} N`, forceX + fLen / 2 * Math.cos(fAngle), forceY + fLen / 2 * Math.sin(fAngle) - 10);

    // Moment arm indicator
    c.setLineDash([5, 5]);
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(0, 0);
    c.lineTo(forceX, 0);
    c.stroke();
    c.setLineDash([]);

    c.restore();

    // Pivot point
    c.beginPath();
    c.arc(pivotX, pivotY, 12, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Rotation arc indicator
    if (Math.abs(rotation.value) > 0.01) {
        c.beginPath();
        c.arc(pivotX, pivotY, 60, -Math.PI / 2, -Math.PI / 2 + rotation.value, rotation.value < 0);
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 3;
        c.stroke();
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Kuch (F): ${force.value} N`, 35, 45);
    c.fillText(`Burchak: ${forceAngle.value}°`, 35, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Yelka (r): ${momentArm.value.toFixed(2)} m`, 35, 90);
    c.fillStyle = '#10b981';
    c.fillText(`Moment (τ): ${torque.value.toFixed(2)} N·m`, 35, 115);
    c.fillStyle = '#3b82f6';
    c.fillText(`Burilish: ${(rotation.value * 180 / Math.PI).toFixed(1)}°`, 35, 140);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('τ = F × r × sin(θ)', 35, 160);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([force, forceAngle, pivotPos, rodLength], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Kuch: <strong>{{ force }} N</strong></label>
                <input type="range" v-model.number="force" :min="5" :max="50" :step="5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Burchak: <strong>{{ forceAngle }}°</strong></label>
                <input type="range" v-model.number="forceAngle" :min="30" :max="150" :step="15" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Pivot holati: <strong>{{ pivotPos }}%</strong></label>
                <input type="range" v-model.number="pivotPos" :min="20" :max="80" :step="10" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" class="btn measure">📏</button>
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
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 130px;
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
    align-items: flex-end;
}

.btn {
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #10b981;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}

.btn.measure {
    background: #3b82f6;
    color: white;
}
</style>
