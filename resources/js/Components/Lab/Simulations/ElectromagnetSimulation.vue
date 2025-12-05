<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const coilTurns = ref(100);
const current = ref(2);
const coreType = ref('iron');

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const cores = {
    air: { permeability: 1, name: 'Havo', color: 'transparent' },
    iron: { permeability: 5000, name: 'Temir', color: '#475569' },
    ferrite: { permeability: 2000, name: 'Ferrit', color: '#1e293b' },
};

const magneticField = computed(() => {
    const mu0 = 4 * Math.PI * 1e-7;
    const mu = cores[coreType.value].permeability;
    return mu0 * mu * coilTurns.value * current.value / 0.1; // 10 cm length
});

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: 'Magnit maydon', value: (magneticField.value * 1000).toFixed(3), unit: 'mT' });
};

const animate = () => {
    time.value += 0.02;
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

    const centerX = 300;
    const centerY = 225;
    const coilWidth = 200;
    const coilHeight = 80;

    // Core
    if (coreType.value !== 'air') {
        c.fillStyle = cores[coreType.value].color;
        c.fillRect(centerX - coilWidth / 2 + 20, centerY - 25, coilWidth - 40, 50);
    }

    // Coil windings
    const numVisible = Math.min(20, coilTurns.value / 5);
    for (let i = 0; i < numVisible; i++) {
        const x = centerX - coilWidth / 2 + 20 + (i / numVisible) * (coilWidth - 40);

        // Back of coil
        c.strokeStyle = '#b45309';
        c.lineWidth = 3;
        c.beginPath();
        c.ellipse(x, centerY, 8, 40, 0, Math.PI, 0);
        c.stroke();

        // Front of coil
        c.strokeStyle = '#f59e0b';
        c.lineWidth = 4;
        c.beginPath();
        c.ellipse(x, centerY, 8, 40, 0, 0, Math.PI);
        c.stroke();
    }

    // Current flow indicators
    if (current.value > 0) {
        const speed = current.value * 30;
        for (let i = 0; i < 5; i++) {
            const offset = ((time.value * speed + i * 40) % 200);

            c.fillStyle = '#3b82f6';
            c.beginPath();
            c.arc(centerX - coilWidth / 2 + 20 + offset, centerY - 45, 4, 0, Math.PI * 2);
            c.fill();

            c.beginPath();
            c.arc(centerX + coilWidth / 2 - 20 - offset, centerY + 45, 4, 0, Math.PI * 2);
            c.fill();
        }
    }

    // Magnetic field lines
    const fieldStrength = Math.min(1, magneticField.value * 100);
    const numLines = Math.floor(3 + fieldStrength * 5);

    for (let i = 0; i < numLines; i++) {
        const yOffset = (i - numLines / 2 + 0.5) * 15;
        const alpha = 0.3 + fieldStrength * 0.4;

        c.strokeStyle = `rgba(239, 68, 68, ${alpha})`;
        c.lineWidth = 2;

        // Inside coil (straight)
        c.beginPath();
        c.moveTo(centerX - coilWidth / 2, centerY + yOffset);
        c.lineTo(centerX + coilWidth / 2, centerY + yOffset);
        c.stroke();

        // Arrow inside
        c.fillStyle = `rgba(239, 68, 68, ${alpha})`;
        c.beginPath();
        c.moveTo(centerX + 20, centerY + yOffset);
        c.lineTo(centerX + 10, centerY + yOffset - 5);
        c.lineTo(centerX + 10, centerY + yOffset + 5);
        c.closePath();
        c.fill();
    }

    // External field lines (curved)
    c.strokeStyle = 'rgba(239, 68, 68, 0.3)';
    for (let i = 0; i < 4; i++) {
        const radius = 80 + i * 30;
        c.beginPath();
        c.ellipse(centerX, centerY, coilWidth / 2 + 20, radius, 0, 0, Math.PI, true);
        c.stroke();
        c.beginPath();
        c.ellipse(centerX, centerY, coilWidth / 2 + 20, radius, 0, Math.PI, 0, true);
        c.stroke();
    }

    // N and S poles
    c.font = 'bold 20px Inter';
    c.fillStyle = '#ef4444';
    c.textAlign = 'center';
    c.fillText('N', centerX + coilWidth / 2 + 40, centerY + 7);
    c.fillStyle = '#3b82f6';
    c.fillText('S', centerX - coilWidth / 2 - 40, centerY + 7);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 240, 20, 220, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`O'ramlar soni: ${coilTurns.value}`, w - 225, 45);
    c.fillText(`Tok kuchi: ${current.value} A`, w - 225, 65);
    c.fillText(`Yadro: ${cores[coreType.value].name}`, w - 225, 85);
    c.fillText(`µᵣ = ${cores[coreType.value].permeability}`, w - 225, 105);
    c.fillStyle = '#ef4444';
    c.fillText(`B = ${(magneticField.value * 1000).toFixed(3)} mT`, w - 225, 130);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('B = µ₀µᵣnI', w - 225, 155);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="50" :max="500" :step="50" />
            </div>
            <div class="control-group">
                <label>Tok: <strong>{{ current }} A</strong></label>
                <input type="range" v-model.number="current" :min="0.5" :max="5" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Yadro:</label>
                <select v-model="coreType">
                    <option v-for="(core, key) in cores" :key="key" :value="key">{{ core.name }}</option>
                </select>
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
    min-width: 120px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.control-group select {
    padding: 0.5rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
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
