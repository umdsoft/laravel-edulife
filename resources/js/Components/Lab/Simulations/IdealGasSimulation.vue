<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const pressure = ref(1); // atm
const temperature = ref(300); // K
const volume = ref(1); // L
const moles = ref(1);
const R = 8.314; // J/(mol·K)

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
const time = ref(0);

const particles = ref([]);
const numParticles = 100;

const calculatedVolume = computed(() => (moles.value * R * temperature.value) / (pressure.value * 101325) * 1000); // L
const calculatedPressure = computed(() => (moles.value * R * temperature.value) / (volume.value / 1000) / 101325); // atm

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initParticles = () => {
    particles.value = [];
    for (let i = 0; i < numParticles; i++) {
        particles.value.push({
            x: 100 + Math.random() * 300,
            y: 100 + Math.random() * 250,
            vx: (Math.random() - 0.5) * 4,
            vy: (Math.random() - 0.5) * 4,
        });
    }
};

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        initParticles();
        draw();
    }
};

const start = () => {
    if (isRunning.value) return;
    isRunning.value = true;
    animate();
};

const pause = () => { isRunning.value = false; };

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    temperature.value = 300;
    pressure.value = 1;
    volume.value = 1;
    time.value = 0;
    initParticles();
    draw();
};

const measure = () => {
    emit('measurement', { name: 'Hisoblangan V', value: calculatedVolume.value.toFixed(3), unit: 'L' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;

    const speedFactor = Math.sqrt(temperature.value / 300);
    const containerW = 300 * volume.value;
    const containerH = 250;

    particles.value.forEach(p => {
        p.x += p.vx * speedFactor;
        p.y += p.vy * speedFactor;

        if (p.x < 100 || p.x > 100 + containerW) {
            p.vx *= -1;
            p.x = Math.max(100, Math.min(100 + containerW, p.x));
        }
        if (p.y < 100 || p.y > 100 + containerH) {
            p.vy *= -1;
            p.y = Math.max(100, Math.min(100 + containerH, p.y));
        }
    });

    time.value += 1 / 60;
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

    // Container
    const containerW = 300 * volume.value;
    const containerH = 250;

    c.strokeStyle = '#475569';
    c.lineWidth = 4;
    c.strokeRect(100, 100, containerW, containerH);

    // Piston (top)
    c.fillStyle = '#64748b';
    c.fillRect(100, 80, containerW, 20);

    // Particles
    const speedFactor = Math.sqrt(temperature.value / 300);
    particles.value.forEach(p => {
        if (p.x >= 100 && p.x <= 100 + containerW) {
            c.beginPath();
            c.arc(p.x, p.y, 4, 0, Math.PI * 2);
            c.fillStyle = `hsl(${60 - speedFactor * 30}, 90%, 60%)`;
            c.fill();
        }
    });

    // Labels
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`V = ${volume.value.toFixed(2)} L`, 100 + containerW / 2, 100 + containerH + 30);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 260, 20, 240, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Bosim (P): ${pressure.value.toFixed(2)} atm`, w - 245, 45);
    c.fillText(`Hajm (V): ${volume.value.toFixed(2)} L`, w - 245, 65);
    c.fillText(`Harorat (T): ${temperature.value} K`, w - 245, 85);
    c.fillText(`Mol soni (n): ${moles.value}`, w - 245, 105);

    c.fillStyle = '#10b981';
    c.fillText(`Hisoblangan V: ${calculatedVolume.value.toFixed(3)} L`, w - 245, 130);
    c.fillStyle = '#3b82f6';
    c.fillText(`Hisoblangan P: ${calculatedPressure.value.toFixed(3)} atm`, w - 245, 150);

    // Formula
    c.fillStyle = '#f59e0b';
    c.font = '16px serif';
    c.fillText('PV = nRT', w - 245, 180);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([pressure, temperature, volume, moles], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Bosim: <strong>{{ pressure.toFixed(2) }} atm</strong></label>
                <input type="range" v-model.number="pressure" :min="0.5" :max="3" :step="0.1" />
            </div>
            <div class="control-group">
                <label>Harorat: <strong>{{ temperature }} K</strong></label>
                <input type="range" v-model.number="temperature" :min="200" :max="500" :step="10" />
            </div>
            <div class="control-group">
                <label>Hajm: <strong>{{ volume.toFixed(2) }} L</strong></label>
                <input type="range" v-model.number="volume" :min="0.5" :max="2" :step="0.1" />
            </div>
            <div class="control-group">
                <label>Mol: <strong>{{ moles }}</strong></label>
                <input type="range" v-model.number="moles" :min="0.5" :max="2" :step="0.1" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️</button>
                <button @click="pause" :disabled="!isRunning" class="btn pause">⏸️</button>
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
    flex-wrap: wrap;
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 120px;
}

.control-group label {
    font-size: 0.75rem;
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
</style>
