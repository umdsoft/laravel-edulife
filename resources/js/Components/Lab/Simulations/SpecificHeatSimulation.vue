<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const material = ref('water');
const mass = ref(0.2);
const heatPower = ref(100);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const temperature = ref(20);
const time = ref(0);
const heatAdded = ref(0);

const materials = {
    water: { c: 4186, name: 'Suv', color: '#3b82f6' },
    oil: { c: 2000, name: 'Moy', color: '#eab308' },
    iron: { c: 450, name: 'Temir', color: '#64748b' },
    copper: { c: 385, name: 'Mis', color: '#f97316' },
    aluminum: { c: 897, name: 'Alyuminiy', color: '#a8a29e' },
};

const specificHeat = computed(() => materials[material.value].c);
const theoreticalDeltaT = computed(() => heatAdded.value / (mass.value * specificHeat.value));

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    temperature.value = 20;
    heatAdded.value = 0;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `Issiqlik sig'imi (${materials[material.value].name})`, value: specificHeat.value.toFixed(0), unit: 'J/(kg·°C)' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    const dQ = heatPower.value * dt;
    heatAdded.value += dQ;

    const dT = dQ / (mass.value * specificHeat.value);
    temperature.value += dT;

    if (temperature.value >= 100) {
        temperature.value = 100;
        isRunning.value = false;
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

    // Beaker
    const beakerX = 150;
    const beakerY = 100;
    const beakerW = 150;
    const beakerH = 200;

    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(beakerX, beakerY);
    c.lineTo(beakerX, beakerY + beakerH);
    c.lineTo(beakerX + beakerW, beakerY + beakerH);
    c.lineTo(beakerX + beakerW, beakerY);
    c.stroke();

    // Liquid
    const liquidH = beakerH * 0.7;
    const liquidY = beakerY + beakerH - liquidH;
    c.fillStyle = materials[material.value].color;
    c.globalAlpha = 0.7;
    c.fillRect(beakerX + 3, liquidY, beakerW - 6, liquidH - 3);
    c.globalAlpha = 1;

    // Bubbles if hot
    if (temperature.value > 60) {
        const numBubbles = Math.floor((temperature.value - 60) / 10);
        for (let i = 0; i < numBubbles; i++) {
            const bx = beakerX + 20 + Math.random() * (beakerW - 40);
            const by = liquidY + Math.random() * liquidH;
            c.beginPath();
            c.arc(bx, by, 3 + Math.random() * 5, 0, Math.PI * 2);
            c.fillStyle = 'rgba(255,255,255,0.3)';
            c.fill();
        }
    }

    // Heat source
    c.fillStyle = '#ef4444';
    c.fillRect(beakerX - 10, beakerY + beakerH + 10, beakerW + 20, 20);
    if (isRunning.value) {
        for (let i = 0; i < 5; i++) {
            const fx = beakerX + 20 + i * 30;
            const fy = beakerY + beakerH + 5;
            c.fillStyle = '#f59e0b';
            c.beginPath();
            c.moveTo(fx, fy);
            c.lineTo(fx - 8, fy - 25 - Math.random() * 10);
            c.lineTo(fx + 8, fy - 25 - Math.random() * 10);
            c.closePath();
            c.fill();
        }
    }

    // Thermometer
    const thermX = beakerX + beakerW + 80;
    const thermY = 80;
    const thermH = 250;

    c.fillStyle = '#1e293b';
    c.fillRect(thermX - 15, thermY, 30, thermH);
    c.beginPath();
    c.arc(thermX, thermY + thermH + 15, 25, 0, Math.PI * 2);
    c.fill();

    const tempHeight = ((temperature.value - 0) / 100) * (thermH - 30);
    c.fillStyle = '#ef4444';
    c.fillRect(thermX - 8, thermY + thermH - tempHeight - 20, 16, tempHeight + 20);
    c.beginPath();
    c.arc(thermX, thermY + thermH + 15, 18, 0, Math.PI * 2);
    c.fill();

    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.textAlign = 'right';
    for (let t = 0; t <= 100; t += 20) {
        const y = thermY + thermH - 20 - (t / 100) * (thermH - 30);
        c.fillText(`${t}°`, thermX - 20, y + 3);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 280, 20, 260, 180);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Modda: ${materials[material.value].name}`, w - 265, 45);
    c.fillText(`Massa: ${mass.value} kg`, w - 265, 65);
    c.fillText(`c = ${specificHeat.value} J/(kg·°C)`, w - 265, 85);
    c.fillText(`Isitgich quvvati: ${heatPower.value} W`, w - 265, 105);
    c.fillStyle = '#ef4444';
    c.fillText(`T = ${temperature.value.toFixed(1)} °C`, w - 265, 130);
    c.fillStyle = '#f59e0b';
    c.fillText(`Q = ${heatAdded.value.toFixed(0)} J`, w - 265, 150);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s`, w - 265, 170);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('Q = mcΔT', w - 265, 192);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([material, mass, heatPower], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Modda:</label>
                <select v-model="material" :disabled="isRunning">
                    <option v-for="(m, key) in materials" :key="key" :value="key">{{ m.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Massa: <strong>{{ mass }} kg</strong></label>
                <input type="range" v-model.number="mass" :min="0.1" :max="1" :step="0.1" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Quvvat: <strong>{{ heatPower }} W</strong></label>
                <input type="range" v-model.number="heatPower" :min="50" :max="500" :step="50" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🔥 Isitish</button>
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
    background: #ef4444;
    color: white;
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
