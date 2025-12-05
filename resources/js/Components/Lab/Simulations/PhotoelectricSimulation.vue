<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const lightIntensity = ref(50);
const frequency = ref(600); // THz
const metalWorkFunction = ref(2); // eV

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const h = 4.136e-15; // Planck constant in eV·s
const photonEnergy = computed(() => h * frequency.value * 1e12); // eV
const hasEmission = computed(() => photonEnergy.value > metalWorkFunction.value);
const electronKE = computed(() => hasEmission.value ? photonEnergy.value - metalWorkFunction.value : 0);
const thresholdFreq = computed(() => metalWorkFunction.value / h / 1e12); // THz

const electrons = ref([]);

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const frequencyToColor = (f) => {
    const wl = 299792 / f; // nm
    if (wl >= 380 && wl < 440) return '#8b5cf6';
    if (wl >= 440 && wl < 490) return '#3b82f6';
    if (wl >= 490 && wl < 510) return '#06b6d4';
    if (wl >= 510 && wl < 580) return '#22c55e';
    if (wl >= 580 && wl < 645) return '#eab308';
    if (wl >= 645) return '#ef4444';
    return '#a855f7';
};

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    time.value += 0.016;

    // Add new electrons if emission is happening
    if (hasEmission.value && Math.random() < 0.1 * lightIntensity.value / 50) {
        electrons.value.push({
            x: 300,
            y: 250 + Math.random() * 100 - 50,
            vx: 2 + electronKE.value * 2,
            vy: (Math.random() - 0.5) * 3,
        });
    }

    // Update electrons
    electrons.value = electrons.value.filter(e => {
        e.x += e.vx;
        e.y += e.vy;
        return e.x < canvasConfig.value.width && e.y > 0 && e.y < canvasConfig.value.height;
    });

    // Limit electrons
    if (electrons.value.length > 50) electrons.value.shift();

    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Light source
    const lightColor = frequencyToColor(frequency.value);

    // Photon beams
    for (let i = 0; i < 5; i++) {
        const offset = ((time.value * 200 + i * 60) % 200);
        const alpha = lightIntensity.value / 100;

        c.strokeStyle = lightColor;
        c.globalAlpha = alpha * (1 - offset / 200);
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(50 + offset, 150 + i * 30);
        c.lineTo(300, 250);
        c.stroke();
    }
    c.globalAlpha = 1;

    // Light source icon
    c.beginPath();
    c.arc(50, 250, 30, 0, Math.PI * 2);
    c.fillStyle = lightColor;
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText('hf', 50, 255);

    // Metal plate
    c.fillStyle = '#475569';
    c.fillRect(300, 150, 30, 200);
    c.fillStyle = '#64748b';
    c.fillRect(300, 150, 30, 10);
    c.fillRect(300, 340, 30, 10);

    // Electrons
    electrons.value.forEach(e => {
        c.beginPath();
        c.arc(e.x, e.y, 5, 0, Math.PI * 2);
        c.fillStyle = '#22c55e';
        c.fill();
    });

    // Collector plate
    c.fillStyle = '#334155';
    c.fillRect(w - 80, 150, 20, 200);

    // Circuit elements
    c.strokeStyle = '#64748b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(330, 350);
    c.lineTo(330, 400);
    c.lineTo(w - 70, 400);
    c.lineTo(w - 70, 350);
    c.stroke();

    // Ammeter
    c.beginPath();
    c.arc(w / 2, 400, 20, 0, Math.PI * 2);
    c.fillStyle = '#1e293b';
    c.fill();
    c.strokeStyle = '#3b82f6';
    c.stroke();
    c.fillStyle = '#3b82f6';
    c.font = '10px Inter';
    c.fillText('A', w / 2, 404);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 240, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Yorug'lik chastotasi: ${frequency.value} THz`, 35, 45);
    c.fillText(`Foton energiyasi: ${photonEnergy.value.toFixed(3)} eV`, 35, 65);
    c.fillText(`Ish funksiyasi (W): ${metalWorkFunction.value} eV`, 35, 85);
    c.fillText(`Chegaraviy ν: ${thresholdFreq.value.toFixed(0)} THz`, 35, 105);

    c.fillStyle = hasEmission.value ? '#22c55e' : '#ef4444';
    c.fillText(hasEmission.value ? '✓ Emissiya mavjud' : '✗ Emissiya yo\'q', 35, 130);

    if (hasEmission.value) {
        c.fillStyle = '#f59e0b';
        c.fillText(`Elektron KE: ${electronKE.value.toFixed(3)} eV`, 35, 150);
    }

    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('KE = hf - W', 35, 175);
};

watch([lightIntensity, frequency, metalWorkFunction], () => { electrons.value = []; });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Chastota: <strong>{{ frequency }} THz</strong></label>
                <input type="range" v-model.number="frequency" :min="400" :max="800" :step="20" />
            </div>
            <div class="control-group">
                <label>Intensivlik: <strong>{{ lightIntensity }}%</strong></label>
                <input type="range" v-model.number="lightIntensity" :min="10" :max="100" :step="10" />
            </div>
            <div class="control-group">
                <label>Ish funksiyasi: <strong>{{ metalWorkFunction }} eV</strong></label>
                <input type="range" v-model.number="metalWorkFunction" :min="1" :max="5" :step="0.5" />
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
