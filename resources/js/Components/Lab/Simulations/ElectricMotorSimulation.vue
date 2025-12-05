<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const current = ref(2);
const magnetStrength = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);

const rotorAngle = ref(0);
const time = ref(0);

const rotationSpeed = computed(() => current.value * magnetStrength.value * 0.02);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    if (!isRunning.value) return;
    time.value += 0.016;
    rotorAngle.value += rotationSpeed.value;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerX = 350;
    const centerY = 225;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Magnets (stator)
    // N pole (top)
    c.fillStyle = '#ef4444';
    c.beginPath();
    c.arc(centerX, centerY - 110, 40, Math.PI, 0);
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 20px Inter';
    c.textAlign = 'center';
    c.fillText('N', centerX, centerY - 95);

    // S pole (bottom)
    c.fillStyle = '#3b82f6';
    c.beginPath();
    c.arc(centerX, centerY + 110, 40, 0, Math.PI);
    c.fill();
    c.fillStyle = 'white';
    c.fillText('S', centerX, centerY + 125);

    // Magnetic field lines
    c.strokeStyle = 'rgba(239, 68, 68, 0.3)';
    c.lineWidth = 1;
    for (let i = 0; i < 5; i++) {
        const x = centerX - 30 + i * 15;
        c.beginPath();
        c.moveTo(x, centerY - 70);
        c.lineTo(x, centerY + 70);
        c.stroke();
    }

    // Rotor (coil)
    c.save();
    c.translate(centerX, centerY);
    c.rotate(rotorAngle.value);

    // Coil
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 6;
    c.strokeRect(-60, -20, 120, 40);

    // Current direction indicators
    const currentPhase = Math.sin(rotorAngle.value);
    if (currentPhase > 0) {
        c.fillStyle = '#22c55e';
        c.font = '16px Inter';
        c.fillText('⊙', -50, 5);
        c.fillText('⊗', 50, 5);
    } else {
        c.fillStyle = '#22c55e';
        c.font = '16px Inter';
        c.fillText('⊗', -50, 5);
        c.fillText('⊙', 50, 5);
    }

    c.restore();

    // Axis
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(centerX, centerY, 10, 0, Math.PI * 2);
    c.fill();

    // Commutator
    c.fillStyle = '#f59e0b';
    c.save();
    c.translate(centerX, centerY);
    c.rotate(rotorAngle.value);
    c.fillRect(-15, 60, 10, 20);
    c.fillRect(5, 60, 10, 20);
    c.restore();

    // Brushes
    c.fillStyle = '#64748b';
    c.fillRect(centerX - 25, centerY + 85, 15, 25);
    c.fillRect(centerX + 10, centerY + 85, 15, 25);

    // Power source
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(centerX - 17, centerY + 110);
    c.lineTo(centerX - 17, centerY + 140);
    c.lineTo(centerX - 60, centerY + 140);
    c.moveTo(centerX + 17, centerY + 110);
    c.lineTo(centerX + 17, centerY + 140);
    c.lineTo(centerX + 60, centerY + 140);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.fillRect(centerX - 80, centerY + 130, 40, 25);
    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.fillText('DC', centerX - 60, centerY + 147);

    // Force arrows
    c.save();
    c.translate(centerX, centerY);
    c.rotate(rotorAngle.value);

    c.fillStyle = '#22c55e';
    c.beginPath();
    c.moveTo(-70, 0);
    c.lineTo(-55, -10);
    c.lineTo(-55, 10);
    c.closePath();
    c.fill();

    c.beginPath();
    c.moveTo(70, 0);
    c.lineTo(55, -10);
    c.lineTo(55, 10);
    c.closePath();
    c.fill();

    c.restore();

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tok kuchi: ${current.value} A`, w - 205, 45);
    c.fillText(`Magnit kuchi: ${magnetStrength.value}%`, w - 205, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Aylanish tezligi: ${(rotationSpeed.value * 60).toFixed(0)} RPM`, w - 205, 90);

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText('F = BIL (Lorentz kuchi)', w - 205, 120);
    c.fillText('τ = F × r (Moment)', w - 205, 140);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tok: <strong>{{ current }} A</strong></label>
                <input type="range" v-model.number="current" :min="0.5" :max="5" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Magnit kuchi: <strong>{{ magnetStrength }}%</strong></label>
                <input type="range" v-model.number="magnetStrength" :min="20" :max="100" :step="10" />
            </div>
            <button @click="isRunning = !isRunning" class="btn">
                {{ isRunning ? '⏸️ Pauza' : '▶️ Davom' }}
            </button>
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-weight: 600;
}
</style>
