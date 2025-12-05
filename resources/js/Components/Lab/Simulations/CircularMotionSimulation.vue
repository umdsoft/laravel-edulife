<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const radius = ref(props.config?.radius?.default || 1.5);
const angularVelocity = ref(props.config?.omega?.default || 2);
const mass = ref(props.config?.mass?.default || 1);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const angle = ref(0);
const time = ref(0);

const period = computed(() => (2 * Math.PI) / angularVelocity.value);
const linearVelocity = computed(() => angularVelocity.value * radius.value);
const centripetalAcc = computed(() => angularVelocity.value ** 2 * radius.value);
const centripetalForce = computed(() => mass.value * centripetalAcc.value);

const canvasConfig = computed(() => ({ width: 700, height: 600 }));
const scale = 100;
const centerX = 350;
const centerY = 300;

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    angle.value = 0;
    time.value = 0;
    isRunning.value = true;
    animate();
};

const pause = () => { isRunning.value = false; };

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    angle.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Markazga intilma tezlanish`, value: centripetalAcc.value.toFixed(3), unit: 'm/s²' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    angle.value += angularVelocity.value * dt;
    time.value += dt;
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

    // Orbit circle
    c.strokeStyle = '#334155';
    c.lineWidth = 2;
    c.setLineDash([5, 5]);
    c.beginPath();
    c.arc(centerX, centerY, radius.value * scale, 0, Math.PI * 2);
    c.stroke();
    c.setLineDash([]);

    // Center point
    c.beginPath();
    c.arc(centerX, centerY, 8, 0, Math.PI * 2);
    c.fillStyle = '#64748b';
    c.fill();

    // Object position
    const objX = centerX + radius.value * scale * Math.cos(angle.value);
    const objY = centerY + radius.value * scale * Math.sin(angle.value);

    // Radius line
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(centerX, centerY);
    c.lineTo(objX, objY);
    c.stroke();

    // Object
    c.beginPath();
    c.arc(objX, objY, 15, 0, Math.PI * 2);
    c.fillStyle = '#3b82f6';
    c.fill();

    // Velocity vector (tangential)
    const vLen = linearVelocity.value * 15;
    const vAngle = angle.value + Math.PI / 2;
    c.strokeStyle = '#22c55e';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(objX, objY);
    c.lineTo(objX + vLen * Math.cos(vAngle), objY + vLen * Math.sin(vAngle));
    c.stroke();

    // Centripetal acceleration vector (toward center)
    const aLen = centripetalAcc.value * 3;
    const aAngle = angle.value + Math.PI;
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(objX, objY);
    c.lineTo(objX + aLen * Math.cos(aAngle), objY + aLen * Math.sin(aAngle));
    c.stroke();

    // Legend
    c.fillStyle = '#22c55e';
    c.fillRect(w - 150, 20, 15, 15);
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText('Tezlik (v)', w - 130, 32);

    c.fillStyle = '#ef4444';
    c.fillRect(w - 150, 45, 15, 15);
    c.fillText('Tezlanish (a)', w - 130, 57);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Radius (r): ${radius.value.toFixed(2)} m`, 35, 45);
    c.fillText(`Burchak tezlik (ω): ${angularVelocity.value.toFixed(2)} rad/s`, 35, 65);
    c.fillText(`Massa: ${mass.value} kg`, 35, 85);
    c.fillStyle = '#22c55e';
    c.fillText(`Chiziqli v: ${linearVelocity.value.toFixed(2)} m/s`, 35, 110);
    c.fillStyle = '#ef4444';
    c.fillText(`Markazga a: ${centripetalAcc.value.toFixed(2)} m/s²`, 35, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Kuch (F): ${centripetalForce.value.toFixed(2)} N`, 35, 150);
    c.fillStyle = '#3b82f6';
    c.fillText(`Davr (T): ${period.value.toFixed(3)} s`, 35, 170);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([radius, angularVelocity, mass], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Radius: <strong>{{ radius }} m</strong></label>
                <input type="range" v-model.number="radius" :min="0.5" :max="2.5" :step="0.1" />
            </div>
            <div class="control-group">
                <label>ω: <strong>{{ angularVelocity }} rad/s</strong></label>
                <input type="range" v-model.number="angularVelocity" :min="0.5" :max="5" :step="0.25" />
            </div>
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.5" :max="5" :step="0.5" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="pause" :disabled="!isRunning" class="btn pause">⏸️</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" class="btn measure">📏</button>
            </div>
        </div>
        <div class="formulas">
            <span>a = ω²r</span>
            <span>v = ωr</span>
            <span>F = ma</span>
            <span>T = 2π/ω</span>
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

.btn.pause {
    background: #f59e0b;
    color: #1e293b;
}

.btn.reset {
    background: #475569;
    color: white;
}

.btn.measure {
    background: #3b82f6;
    color: white;
}

.formulas {
    display: flex;
    gap: 1.5rem;
    font-family: serif;
    font-size: 1rem;
    color: #94a3b8;
}
</style>
