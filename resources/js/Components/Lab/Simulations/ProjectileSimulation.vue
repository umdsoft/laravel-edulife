<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const velocity = ref(props.config?.velocity?.default || 20);
const angle = ref(props.config?.angle?.default || 45);
const height = ref(props.config?.height?.default || 0);
const gravity = 9.8;

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const posX = ref(0);
const posY = ref(0);
const velX = ref(0);
const velY = ref(0);
const time = ref(0);
const trajectory = ref([]);
const maxHeight = ref(0);
const range = ref(0);

const angleRad = computed(() => angle.value * Math.PI / 180);
const theoreticalRange = computed(() => (velocity.value ** 2 * Math.sin(2 * angleRad.value)) / gravity);
const theoreticalMaxHeight = computed(() => (velocity.value ** 2 * Math.sin(angleRad.value) ** 2) / (2 * gravity) + height.value);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));
const scale = 8;

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    posX.value = 50;
    posY.value = height.value;
    velX.value = velocity.value * Math.cos(angleRad.value);
    velY.value = velocity.value * Math.sin(angleRad.value);
    time.value = 0;
    trajectory.value = [];
    maxHeight.value = height.value;
    range.value = 0;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    posX.value = 50;
    posY.value = height.value;
    trajectory.value = [];
    maxHeight.value = 0;
    range.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Uzoqlik (v=${velocity.value}, θ=${angle.value}°)`, value: range.value.toFixed(2), unit: 'm' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;

    velY.value -= gravity * dt;
    posX.value += velX.value * dt * scale;
    posY.value += velY.value * dt * scale;
    time.value += dt;

    const realX = (posX.value - 50) / scale;
    const realY = posY.value / scale;

    trajectory.value.push({ x: posX.value, y: posY.value });
    if (realY > maxHeight.value) maxHeight.value = realY;

    if (posY.value <= 0 || posX.value > canvasConfig.value.width - 50) {
        posY.value = Math.max(0, posY.value);
        range.value = realX;
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

    // Ground
    c.fillStyle = '#334155';
    c.fillRect(0, h - 50, w, 50);

    // Grid
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let x = 50; x < w; x += 50) {
        c.beginPath(); c.moveTo(x, 0); c.lineTo(x, h - 50); c.stroke();
    }

    // Trajectory
    if (trajectory.value.length > 1) {
        c.beginPath();
        c.strokeStyle = '#3b82f6';
        c.lineWidth = 2;
        c.setLineDash([5, 3]);
        trajectory.value.forEach((p, i) => {
            const screenY = h - 50 - p.y;
            if (i === 0) c.moveTo(p.x, screenY);
            else c.lineTo(p.x, screenY);
        });
        c.stroke();
        c.setLineDash([]);
    }

    // Projectile
    const screenY = h - 50 - posY.value;
    c.beginPath();
    c.arc(posX.value, screenY, 10, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Initial velocity vector
    if (!isRunning.value && trajectory.value.length === 0) {
        const arrowLen = velocity.value * 2;
        c.strokeStyle = '#22c55e';
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(50, h - 50 - height.value * scale);
        c.lineTo(50 + arrowLen * Math.cos(angleRad.value), h - 50 - height.value * scale - arrowLen * Math.sin(angleRad.value));
        c.stroke();
    }

    // Info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 160);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Tezlik: ${velocity.value} m/s`, 35, 45);
    c.fillText(`Burchak: ${angle.value}°`, 35, 65);
    c.fillText(`Boshlang'ich h: ${height.value} m`, 35, 85);
    c.fillStyle = '#10b981';
    c.fillText(`Masofа: ${range.value.toFixed(2)} m`, 35, 110);
    c.fillText(`Max h: ${maxHeight.value.toFixed(2)} m`, 35, 130);
    c.fillStyle = '#3b82f6';
    c.fillText(`Nazariy R: ${theoreticalRange.value.toFixed(2)} m`, 35, 155);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 175);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([velocity, angle, height], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tezlik: <strong>{{ velocity }} m/s</strong></label>
                <input type="range" v-model.number="velocity" :min="5" :max="40" :step="1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Burchak: <strong>{{ angle }}°</strong></label>
                <input type="range" v-model.number="angle" :min="10" :max="80" :step="5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Balandlik: <strong>{{ height }} m</strong></label>
                <input type="range" v-model.number="height" :min="0" :max="10" :step="1" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🚀</button>
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
