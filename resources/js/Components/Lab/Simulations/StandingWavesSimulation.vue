<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const frequency = ref(2);
const harmonicMode = ref(1);
const amplitude = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const wavelength = computed(() => 700 / harmonicMode.value);
const waveSpeed = computed(() => wavelength.value * frequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: `Harmonik ${harmonicMode.value}`, value: frequency.value.toFixed(2), unit: 'Hz' });
};

const animate = () => {
    time.value += 0.016;
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

    const startX = 100;
    const endX = 800;
    const centerY = 200;
    const length = endX - startX;

    // Fixed ends
    c.fillStyle = '#475569';
    c.fillRect(startX - 15, centerY - 80, 15, 160);
    c.fillRect(endX, centerY - 80, 15, 160);

    // Equilibrium line
    c.strokeStyle = '#334155';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(startX, centerY);
    c.lineTo(endX, centerY);
    c.stroke();
    c.setLineDash([]);

    // Standing wave
    c.beginPath();
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 3;

    for (let x = startX; x <= endX; x++) {
        const relX = (x - startX) / length;
        const spatialPart = Math.sin(harmonicMode.value * Math.PI * relX);
        const temporalPart = Math.cos(2 * Math.PI * frequency.value * time.value);
        const y = centerY - amplitude.value * spatialPart * temporalPart;

        if (x === startX) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Nodes and antinodes
    c.font = '11px Inter';
    c.textAlign = 'center';

    for (let i = 0; i <= harmonicMode.value; i++) {
        const nodeX = startX + (i / harmonicMode.value) * length;
        c.beginPath();
        c.arc(nodeX, centerY, 6, 0, Math.PI * 2);
        c.fillStyle = '#22c55e';
        c.fill();
        c.fillStyle = '#22c55e';
        c.fillText('N', nodeX, centerY + 25);
    }

    for (let i = 0; i < harmonicMode.value; i++) {
        const antiX = startX + ((i + 0.5) / harmonicMode.value) * length;
        c.beginPath();
        c.arc(antiX, centerY - amplitude.value, 6, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
        c.beginPath();
        c.arc(antiX, centerY + amplitude.value, 6, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
        c.fillStyle = '#ef4444';
        c.fillText('A', antiX, centerY + 85);
    }

    // Harmonic mode visualization
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(w - 200, 30, 180, 80);
    c.font = '14px Inter';
    c.fillStyle = 'white';
    c.textAlign = 'left';
    c.fillText(`Harmonik: n = ${harmonicMode.value}`, w - 185, 55);
    c.fillStyle = '#f59e0b';
    c.fillText(`λ = 2L/${harmonicMode.value}`, w - 185, 80);
    c.fillText(`f = ${harmonicMode.value}f₁`, w - 185, 100);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`Chastota: ${frequency.value.toFixed(1)} Hz`, 35, 45);
    c.fillText(`Amplituda: ${amplitude.value} px`, 35, 65);
    c.fillStyle = '#22c55e';
    c.fillText(`Tugunlar: ${harmonicMode.value + 1}`, 35, 90);
    c.fillStyle = '#ef4444';
    c.fillText(`Antitugunlar: ${harmonicMode.value}`, 35, 110);
    c.fillStyle = '#3b82f6';
    c.fillText(`λ = ${wavelength.value.toFixed(0)} px`, 35, 130);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Harmonik: <strong>n = {{ harmonicMode }}</strong></label>
                <input type="range" v-model.number="harmonicMode" :min="1" :max="6" :step="1" />
            </div>
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency.toFixed(1) }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="0.5" :max="4" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }} px</strong></label>
                <input type="range" v-model.number="amplitude" :min="20" :max="80" :step="10" />
            </div>
            <button @click="measure" class="btn">📏</button>
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

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}
</style>
