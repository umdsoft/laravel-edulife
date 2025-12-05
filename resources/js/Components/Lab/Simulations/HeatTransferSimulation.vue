<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const hotTemp = ref(100);
const coldTemp = ref(20);
const material = ref('copper');
const length = ref(0.1);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const materials = {
    copper: { k: 385, name: 'Mis', color: '#f97316' },
    aluminum: { k: 205, name: 'Alyuminiy', color: '#94a3b8' },
    iron: { k: 80, name: 'Temir', color: '#64748b' },
    glass: { k: 1.05, name: 'Shisha', color: '#06b6d4' },
    wood: { k: 0.15, name: 'Yog\'och', color: '#a16207' },
};

const thermalConductivity = computed(() => materials[material.value].k);
const heatFlux = computed(() => thermalConductivity.value * (hotTemp.value - coldTemp.value) / length.value);

const canvasConfig = computed(() => ({ width: 800, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const measure = () => {
    emit('measurement', { name: `Issiqlik oqimi (${materials[material.value].name})`, value: heatFlux.value.toFixed(1), unit: 'W/m²' });
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

    // Hot side
    const hotGrad = c.createLinearGradient(50, 0, 200, 0);
    hotGrad.addColorStop(0, '#ef4444');
    hotGrad.addColorStop(1, '#f97316');
    c.fillStyle = hotGrad;
    c.fillRect(50, 100, 150, 200);
    c.fillStyle = 'white';
    c.font = 'bold 24px Inter';
    c.textAlign = 'center';
    c.fillText(`${hotTemp.value}°C`, 125, 210);
    c.font = '12px Inter';
    c.fillText('Issiq tomon', 125, 330);

    // Material bar
    c.fillStyle = materials[material.value].color;
    c.fillRect(200, 150, 400, 100);

    // Heat flow arrows
    const numArrows = Math.min(10, Math.floor(heatFlux.value / 500) + 3);
    for (let i = 0; i < numArrows; i++) {
        const offset = ((time.value * 100 + i * 40) % 400);
        const alpha = 1 - Math.abs(offset - 200) / 200;

        c.fillStyle = `rgba(239, 68, 68, ${alpha * 0.8})`;
        c.beginPath();
        c.moveTo(200 + offset, 200);
        c.lineTo(200 + offset - 15, 190);
        c.lineTo(200 + offset - 15, 210);
        c.closePath();
        c.fill();
    }

    // Cold side
    const coldGrad = c.createLinearGradient(600, 0, 750, 0);
    coldGrad.addColorStop(0, '#0ea5e9');
    coldGrad.addColorStop(1, '#3b82f6');
    c.fillStyle = coldGrad;
    c.fillRect(600, 100, 150, 200);
    c.fillStyle = 'white';
    c.font = 'bold 24px Inter';
    c.fillText(`${coldTemp.value}°C`, 675, 210);
    c.font = '12px Inter';
    c.fillText('Sovuq tomon', 675, 330);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 220, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Material: ${materials[material.value].name}`, 35, 45);
    c.fillText(`k = ${thermalConductivity.value} W/(m·K)`, 35, 65);
    c.fillText(`ΔT = ${hotTemp.value - coldTemp.value} °C`, 35, 85);
    c.fillText(`Uzunlik: ${(length.value * 100).toFixed(0)} cm`, 35, 105);
    c.fillStyle = '#ef4444';
    c.fillText(`q = ${heatFlux.value.toFixed(1)} W/m²`, 35, 130);
};

watch([hotTemp, coldTemp, material, length], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Material:</label>
                <select v-model="material">
                    <option v-for="(m, key) in materials" :key="key" :value="key">{{ m.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Issiq: <strong>{{ hotTemp }}°C</strong></label>
                <input type="range" v-model.number="hotTemp" :min="50" :max="200" :step="10" />
            </div>
            <div class="control-group">
                <label>Sovuq: <strong>{{ coldTemp }}°C</strong></label>
                <input type="range" v-model.number="coldTemp" :min="0" :max="50" :step="5" />
            </div>
            <button @click="measure" class="btn">📏</button>
        </div>
        <div class="formula">q = k × ΔT / L</div>
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

.formula {
    font-family: serif;
    font-size: 1.25rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}
</style>
