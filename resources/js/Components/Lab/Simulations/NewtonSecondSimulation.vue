<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Parameters
const mass = ref(props.config?.mass?.default || 2);
const force = ref(props.config?.force?.default || 10);
const friction = ref(props.config?.friction?.default || 0);

// State
const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// Physics
const position = ref(50);
const velocity = ref(0);
const acceleration = ref(0);
const time = ref(0);

// Computed
const theoreticalAcceleration = computed(() => (force.value - friction.value) / mass.value);
const canvasConfig = computed(() => props.config?.canvas || { width: 900, height: 500 });

// Methods
const initCanvas = () => {
    if (!canvas.value) return;
    ctx.value = canvas.value.getContext('2d');
    draw();
};

const start = () => {
    if (isRunning.value) return;
    position.value = 50;
    velocity.value = 0;
    time.value = 0;
    acceleration.value = theoreticalAcceleration.value;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    position.value = 50;
    velocity.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', {
        name: `Tezlanish (F=${force.value}N, m=${mass.value}kg)`,
        value: acceleration.value.toFixed(3),
        unit: 'm/s²',
    });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;

    const dt = 1 / 60;
    acceleration.value = (force.value - friction.value * Math.sign(velocity.value)) / mass.value;
    velocity.value += acceleration.value * dt;
    position.value += velocity.value * dt * 50; // Scale
    time.value += dt;

    if (position.value > canvasConfig.value.width - 80) {
        position.value = canvasConfig.value.width - 80;
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
    c.fillRect(0, h - 60, w, 60);

    // Grid lines
    c.strokeStyle = '#1e293b';
    c.lineWidth = 1;
    for (let x = 0; x < w; x += 50) {
        c.beginPath();
        c.moveTo(x, 0);
        c.lineTo(x, h - 60);
        c.stroke();
    }

    // Block
    const blockSize = 60;
    const blockX = position.value;
    const blockY = h - 60 - blockSize;

    c.fillStyle = '#3b82f6';
    c.fillRect(blockX, blockY, blockSize, blockSize);
    c.strokeStyle = '#60a5fa';
    c.lineWidth = 2;
    c.strokeRect(blockX, blockY, blockSize, blockSize);

    // Mass label on block
    c.fillStyle = 'white';
    c.font = 'bold 14px Inter';
    c.textAlign = 'center';
    c.fillText(`${mass.value} kg`, blockX + blockSize / 2, blockY + blockSize / 2 + 5);

    // Force arrow
    if (force.value > 0) {
        const arrowLen = force.value * 4;
        c.strokeStyle = '#22c55e';
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(blockX + blockSize + 10, blockY + blockSize / 2);
        c.lineTo(blockX + blockSize + 10 + arrowLen, blockY + blockSize / 2);
        c.stroke();

        // Arrow head
        c.fillStyle = '#22c55e';
        c.beginPath();
        c.moveTo(blockX + blockSize + 10 + arrowLen, blockY + blockSize / 2);
        c.lineTo(blockX + blockSize + arrowLen, blockY + blockSize / 2 - 8);
        c.lineTo(blockX + blockSize + arrowLen, blockY + blockSize / 2 + 8);
        c.closePath();
        c.fill();

        c.fillStyle = '#22c55e';
        c.font = '12px Inter';
        c.fillText(`F = ${force.value} N`, blockX + blockSize + 10 + arrowLen / 2, blockY + blockSize / 2 - 15);
    }

    // Measurements
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 140);
    c.strokeStyle = '#334155';
    c.strokeRect(20, 20, 200, 140);

    c.textAlign = 'left';
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.fillText(`Massa (m): ${mass.value} kg`, 35, 45);
    c.fillText(`Kuch (F): ${force.value} N`, 35, 65);
    c.fillText(`Ishqalanish: ${friction.value} N`, 35, 85);

    c.fillStyle = '#10b981';
    c.fillText(`Tezlanish (a): ${acceleration.value.toFixed(3)} m/s²`, 35, 110);
    c.fillStyle = '#3b82f6';
    c.fillText(`Tezlik (v): ${velocity.value.toFixed(2)} m/s`, 35, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Vaqt: ${time.value.toFixed(2)} s`, 35, 150);

    // Formula
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 180, 20, 160, 50);
    c.fillStyle = '#94a3b8';
    c.font = '16px serif';
    c.textAlign = 'center';
    c.fillText('F = m × a', w - 100, 52);
};

watch(() => props.isPaused, (paused) => {
    if (!paused && isRunning.value) animate();
});

watch([mass, force, friction], () => {
    if (!isRunning.value) draw();
    emit('update:state', { mass: mass.value, force: force.value, friction: friction.value });
});

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />

        <div class="controls">
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.5" :max="10" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Kuch: <strong>{{ force }} N</strong></label>
                <input type="range" v-model.number="force" :min="1" :max="50" :step="1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Ishqalanish: <strong>{{ friction }} N</strong></label>
                <input type="range" v-model.number="friction" :min="0" :max="20" :step="1" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Boshlash</button>
                <button @click="reset" class="btn reset">🔄 Qayta</button>
                <button @click="measure" :disabled="!isRunning && acceleration === 0" class="btn measure">📏
                    O'lchash</button>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
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

.control-group strong {
    color: white;
}

.control-group input {
    width: 100%;
}

.buttons {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

.btn {
    padding: 0.625rem 1rem;
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
