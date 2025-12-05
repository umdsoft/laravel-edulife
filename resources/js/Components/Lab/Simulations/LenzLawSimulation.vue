<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetSpeed = ref(3);
const coilTurns = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const magnetPos = ref(200);
const magnetDir = ref(1);
const inducedCurrent = ref(0);
const time = ref(0);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    magnetPos.value = 150;
    magnetDir.value = 1;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    magnetPos.value = 200;
    inducedCurrent.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value) return;
    time.value += 0.016;

    magnetPos.value += magnetSpeed.value * magnetDir.value;

    // Calculate induced current based on flux change
    const coilCenter = 400;
    const distToCoil = magnetPos.value - coilCenter;
    const fluxChange = -magnetSpeed.value * magnetDir.value * Math.exp(-Math.abs(distToCoil) / 80);
    inducedCurrent.value = coilTurns.value * fluxChange * 0.05;

    if (magnetPos.value > 600) {
        magnetDir.value = -1;
    } else if (magnetPos.value < 200) {
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
    const centerY = 225;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Lenz's Law explanation
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(20, 20, 300, 80);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Lents qonuni:', 35, 45);
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.fillText('Induksion tok magnit oqimi', 35, 65);
    c.fillText('o\'zgarishiga qarshi yo\'naladi', 35, 85);

    // Coil
    const coilX = 400;
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 4;
    for (let i = 0; i < 8; i++) {
        const x = coilX - 30 + i * 8;
        c.beginPath();
        c.ellipse(x, centerY, 6, 50, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Induced current direction
    if (Math.abs(inducedCurrent.value) > 0.1) {
        const arrowY = inducedCurrent.value > 0 ? centerY - 60 : centerY + 60;
        const arrowDir = inducedCurrent.value > 0 ? 1 : -1;

        c.fillStyle = '#22c55e';
        c.beginPath();
        c.moveTo(coilX + 50 * arrowDir, arrowY);
        c.lineTo(coilX + 30 * arrowDir, arrowY - 8);
        c.lineTo(coilX + 30 * arrowDir, arrowY + 8);
        c.closePath();
        c.fill();

        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText('I (induksion)', coilX, arrowY + (arrowDir > 0 ? -15 : 25));
    }

    // Magnet
    const magY = centerY;
    c.fillStyle = '#ef4444';
    c.fillRect(magnetPos.value - 50, magY - 20, 50, 40);
    c.fillStyle = '#3b82f6';
    c.fillRect(magnetPos.value, magY - 20, 50, 40);

    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText('N', magnetPos.value - 25, magY + 6);
    c.fillText('S', magnetPos.value + 25, magY + 6);

    // Movement arrow
    if (isRunning.value) {
        c.fillStyle = '#f59e0b';
        c.beginPath();
        if (magnetDir.value > 0) {
            c.moveTo(magnetPos.value + 70, magY);
            c.lineTo(magnetPos.value + 55, magY - 10);
            c.lineTo(magnetPos.value + 55, magY + 10);
        } else {
            c.moveTo(magnetPos.value - 70, magY);
            c.lineTo(magnetPos.value - 55, magY - 10);
            c.lineTo(magnetPos.value - 55, magY + 10);
        }
        c.closePath();
        c.fill();
    }

    // Induced magnetic field (opposing)
    if (Math.abs(inducedCurrent.value) > 0.5) {
        const fieldDir = inducedCurrent.value > 0 ? -1 : 1;
        c.strokeStyle = 'rgba(139, 92, 246, 0.5)';
        c.lineWidth = 2;
        c.setLineDash([5, 5]);
        for (let i = 0; i < 3; i++) {
            const offset = i * 15;
            c.beginPath();
            c.moveTo(coilX - 60 + offset, centerY);
            c.lineTo(coilX + 60 + offset * fieldDir, centerY);
            c.stroke();
        }
        c.setLineDash([]);

        c.fillStyle = '#8b5cf6';
        c.font = '11px Inter';
        c.fillText('B\' (qarshi)', coilX, centerY - 80);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 110);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Magnit tezligi: ${magnetSpeed.value} m/s`, w - 205, 45);
    c.fillText(`O'ramlar: ${coilTurns.value}`, w - 205, 65);
    c.fillStyle = '#22c55e';
    c.fillText(`I = ${inducedCurrent.value.toFixed(2)} A`, w - 205, 90);
    c.fillStyle = '#8b5cf6';
    c.fillText(magnetDir.value > 0 ? '→ Yaqinlashmoqda' : '← Uzoqlashmoqda', w - 205, 115);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tezlik: <strong>{{ magnetSpeed }} m/s</strong></label>
                <input type="range" v-model.number="magnetSpeed" :min="1" :max="6" :step="0.5" :disabled="isRunning" />
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
