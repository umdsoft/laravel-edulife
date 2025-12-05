<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const frequency = ref(5); // Hz
const separation = ref(200); // px
const amplitude = ref(1);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    time.value += 0.1;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    // Water background
    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const source1 = { x: w / 2 - separation.value / 2, y: h / 2 };
    const source2 = { x: w / 2 + separation.value / 2, y: h / 2 };

    // Draw interference pattern
    // We'll draw circles for wavefronts
    c.lineWidth = 2;

    const maxRadius = Math.sqrt(w * w + h * h);
    const wavelength = 400 / frequency.value;

    for (let r = 0; r < maxRadius; r += wavelength / 2) {
        const phase = (r - time.value * 10) / wavelength * Math.PI * 2;
        const alpha = Math.max(0, 1 - r / 500) * amplitude.value;

        // Crests (constructive)
        c.strokeStyle = `rgba(56, 189, 248, ${alpha})`;

        // Source 1
        let r1 = r + (time.value * 10) % (wavelength);
        c.beginPath();
        c.arc(source1.x, source1.y, r1, 0, Math.PI * 2);
        c.stroke();

        // Source 2
        let r2 = r + (time.value * 10) % (wavelength);
        c.beginPath();
        c.arc(source2.x, source2.y, r2, 0, Math.PI * 2);
        c.stroke();
    }

    // Sources
    c.fillStyle = '#fbbf24';
    c.beginPath();
    c.arc(source1.x, source1.y, 8, 0, Math.PI * 2);
    c.fill();
    c.beginPath();
    c.arc(source2.x, source2.y, 8, 0, Math.PI * 2);
    c.fill();

    // Info
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.fillText('Manba 1', source1.x - 25, source1.y - 15);
    c.fillText('Manba 2', source2.x - 25, source2.y - 15);
};

watch([frequency, separation, amplitude], () => { });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency }} Hz</strong></label>
                <input type="range" v-model.number="frequency" :min="1" :max="10" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Manbalar orasi: <strong>{{ separation }} px</strong></label>
                <input type="range" v-model.number="separation" :min="50" :max="400" :step="10" />
            </div>
            <div class="control-group">
                <label>Amplituda: <strong>{{ amplitude }}</strong></label>
                <input type="range" v-model.number="amplitude" :min="0.2" :max="2" :step="0.1" />
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
    align-items: center;
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
</style>
