<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const material = ref('metal');
const initialLength = ref(1);
const temperature = ref(20);
const isHeating = ref(false);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);
const time = ref(0);

const materials = {
    metal: { alpha: 0.000012, color: '#94a3b8', name: 'Metall' },
    aluminum: { alpha: 0.000023, color: '#a8a29e', name: 'Alyuminiy' },
    copper: { alpha: 0.000017, color: '#f97316', name: 'Mis' },
    glass: { alpha: 0.000009, color: '#0ea5e9', name: 'Shisha' },
};

const alpha = computed(() => materials[material.value].alpha);
const deltaT = computed(() => temperature.value - 20);
const deltaL = computed(() => alpha.value * initialLength.value * deltaT.value);
const finalLength = computed(() => initialLength.value + deltaL.value);

const canvasConfig = computed(() => ({ width: 800, height: 400 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const startHeating = () => { isHeating.value = true; isRunning.value = true; animate(); };
const startCooling = () => { isHeating.value = false; isRunning.value = true; animate(); };

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    temperature.value = 20;
    time.value = 0;
    draw();
};

const measure = () => {
    emit('measurement', { name: `ΔL (${materials[material.value].name}, ΔT=${deltaT.value}°C)`, value: (deltaL.value * 1000).toFixed(3), unit: 'mm' });
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    if (isHeating.value) {
        temperature.value = Math.min(100, temperature.value + 0.5);
        if (temperature.value >= 100) isRunning.value = false;
    } else {
        temperature.value = Math.max(-20, temperature.value - 0.5);
        if (temperature.value <= -20) isRunning.value = false;
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

    // Temperature gradient background
    const tempNorm = (temperature.value + 20) / 120;
    c.fillStyle = `hsl(${240 - tempNorm * 240}, 70%, 15%)`;
    c.fillRect(0, 0, w, h);

    // Rod visualization
    const rodY = h / 2;
    const rodH = 40;
    const baseLen = 300;
    const visualDelta = deltaL.value * 5000;
    const rodLen = baseLen + visualDelta;
    const rodX = w / 2 - rodLen / 2;

    // Rod shadow
    c.fillStyle = 'rgba(0,0,0,0.3)';
    c.fillRect(rodX + 5, rodY + 5, rodLen, rodH);

    // Rod
    c.fillStyle = materials[material.value].color;
    c.fillRect(rodX, rodY, rodLen, rodH);

    // Gradient overlay
    const grad = c.createLinearGradient(0, rodY, 0, rodY + rodH);
    grad.addColorStop(0, isHeating.value ? 'rgba(239,68,68,0.3)' : 'rgba(59,130,246,0.3)');
    grad.addColorStop(1, 'transparent');
    c.fillStyle = grad;
    c.fillRect(rodX, rodY, rodLen, rodH);

    // Length markers
    c.strokeStyle = '#475569';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(w / 2 - baseLen / 2, rodY + rodH + 30);
    c.lineTo(w / 2 - baseLen / 2, rodY + rodH + 50);
    c.moveTo(w / 2 + baseLen / 2, rodY + rodH + 30);
    c.lineTo(w / 2 + baseLen / 2, rodY + rodH + 50);
    c.stroke();
    c.setLineDash([]);

    c.fillStyle = '#64748b';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText(`L₀ = ${initialLength.value} m`, w / 2, rodY + rodH + 70);

    // Temperature display
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 160);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Material: ${materials[material.value].name}`, 35, 45);
    c.fillText(`α = ${alpha.value.toExponential(2)} /°C`, 35, 65);
    c.fillStyle = isHeating.value ? '#ef4444' : '#3b82f6';
    c.fillText(`T = ${temperature.value.toFixed(1)} °C`, 35, 90);
    c.fillStyle = '#ffffff';
    c.fillText(`ΔT = ${deltaT.value.toFixed(1)} °C`, 35, 110);
    c.fillStyle = '#10b981';
    c.fillText(`ΔL = ${(deltaL.value * 1000).toFixed(3)} mm`, 35, 135);
    c.fillText(`L = ${finalLength.value.toFixed(6)} m`, 35, 155);

    // Temperature bar
    const barX = w - 60;
    const barH = 200;
    const barY = (h - barH) / 2;

    c.fillStyle = '#1e293b';
    c.fillRect(barX, barY, 30, barH);

    const tempHeight = ((temperature.value + 20) / 120) * barH;
    const tempGrad = c.createLinearGradient(0, barY + barH, 0, barY);
    tempGrad.addColorStop(0, '#3b82f6');
    tempGrad.addColorStop(0.5, '#f59e0b');
    tempGrad.addColorStop(1, '#ef4444');
    c.fillStyle = tempGrad;
    c.fillRect(barX, barY + barH - tempHeight, 30, tempHeight);

    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.fillText('100°C', barX - 5, barY - 5);
    c.fillText('-20°C', barX - 5, barY + barH + 12);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
watch([material, initialLength], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Material:</label>
                <select v-model="material" :disabled="isRunning">
                    <option v-for="(m, key) in materials" :key="key" :value="key">{{ m.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>Uzunlik: <strong>{{ initialLength }} m</strong></label>
                <input type="range" v-model.number="initialLength" :min="0.5" :max="2" :step="0.1"
                    :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="startHeating" :disabled="isRunning" class="btn heat">🔥 Isitish</button>
                <button @click="startCooling" :disabled="isRunning" class="btn cool">❄️ Sovutish</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" class="btn measure">📏</button>
            </div>
        </div>
        <div class="formula">ΔL = α × L₀ × ΔT</div>
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

.btn.heat {
    background: #ef4444;
    color: white;
}

.btn.cool {
    background: #3b82f6;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}

.btn.measure {
    background: #10b981;
    color: white;
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
