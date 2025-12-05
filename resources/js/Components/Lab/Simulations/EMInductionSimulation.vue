<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetSpeed = ref(2);
const coilTurns = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const magnetPos = ref(0);
const magnetDir = ref(1);
const inducedEMF = ref(0);
const fluxChange = ref(0);
const time = ref(0);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    magnetPos.value = -100;
    magnetDir.value = 1;
    time.value = 0;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    magnetPos.value = 0;
    inducedEMF.value = 0;
    fluxChange.value = 0;
    time.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    const prevPos = magnetPos.value;
    magnetPos.value += magnetSpeed.value * magnetDir.value;

    // Calculate flux change (simplified)
    const coilCenter = 400;
    const distToCoil = Math.abs(magnetPos.value - coilCenter);
    fluxChange.value = -magnetSpeed.value * magnetDir.value * Math.exp(-distToCoil / 100);
    inducedEMF.value = -coilTurns.value * fluxChange.value * 0.1;

    if (magnetPos.value > 700) {
        magnetDir.value = -1;
    } else if (magnetPos.value < 100) {
        magnetDir.value = 1;
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

    const coilX = 400;
    const coilY = 225;

    // Coil
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 4;
    for (let i = 0; i < 8; i++) {
        const x = coilX - 40 + i * 10;
        c.beginPath();
        c.ellipse(x, coilY, 8, 60, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Galvanometer connection
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(coilX - 50, coilY - 65);
    c.lineTo(coilX - 50, coilY - 120);
    c.lineTo(coilX + 50, coilY - 120);
    c.lineTo(coilX + 50, coilY - 65);
    c.stroke();

    // Galvanometer
    c.beginPath();
    c.arc(coilX, coilY - 150, 40, 0, Math.PI * 2);
    c.fillStyle = '#1e293b';
    c.fill();
    c.strokeStyle = '#475569';
    c.stroke();

    // Needle
    const needleAngle = Math.max(-0.8, Math.min(0.8, inducedEMF.value / 5));
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(coilX, coilY - 150);
    c.lineTo(coilX + 30 * Math.sin(needleAngle), coilY - 150 - 30 * Math.cos(needleAngle));
    c.stroke();

    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.textAlign = 'center';
    c.fillText('G', coilX, coilY - 145);

    // Magnet
    const magX = magnetPos.value;
    const magY = coilY;

    // N pole
    c.fillStyle = '#ef4444';
    c.fillRect(magX - 60, magY - 20, 60, 40);
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.fillText('N', magX - 30, magY + 6);

    // S pole
    c.fillStyle = '#3b82f6';
    c.fillRect(magX, magY - 20, 60, 40);
    c.fillStyle = 'white';
    c.fillText('S', magX + 30, magY + 6);

    // Direction arrow
    if (isRunning.value) {
        c.fillStyle = '#22c55e';
        c.beginPath();
        if (magnetDir.value > 0) {
            c.moveTo(magX + 80, magY);
            c.lineTo(magX + 65, magY - 10);
            c.lineTo(magX + 65, magY + 10);
        } else {
            c.moveTo(magX - 80, magY);
            c.lineTo(magX - 65, magY - 10);
            c.lineTo(magX - 65, magY + 10);
        }
        c.closePath();
        c.fill();
    }

    // EMF visualization
    const emfColor = inducedEMF.value >= 0 ? '#22c55e' : '#ef4444';
    if (Math.abs(inducedEMF.value) > 0.1) {
        c.strokeStyle = emfColor;
        c.lineWidth = 2;
        c.setLineDash([5, 5]);
        c.beginPath();
        c.arc(coilX, coilY, 80, 0, Math.PI * 2);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`O'ramlar soni: ${coilTurns.value}`, 35, 45);
    c.fillText(`Magnit tezligi: ${magnetSpeed.value} m/s`, 35, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Oqim o'zgarishi: ${fluxChange.value.toFixed(3)}`, 35, 90);
    c.fillStyle = emfColor;
    c.fillText(`ε = ${inducedEMF.value.toFixed(2)} V`, 35, 115);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('ε = -N × dΦ/dt', 35, 140);
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
                <label>Tezlik: <strong>{{ magnetSpeed }} m/s</strong></label>
                <input type="range" v-model.number="magnetSpeed" :min="1" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="20" :max="100" :step="10" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Boshlash</button>
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
}

.btn {
    padding: 0.5rem 1rem;
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
</style>
