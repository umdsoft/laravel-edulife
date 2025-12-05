<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const voltage = ref(12);
const current = ref(2);

const canvas = ref(null);
const ctx = ref(null);

const power = computed(() => voltage.value * current.value);
const resistance = computed(() => voltage.value / current.value);
const energy1h = computed(() => power.value * 1); // Wh
const cost = computed(() => (energy1h.value / 1000) * 500); // 500 so'm per kWh

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    emit('measurement', { name: 'Quvvat', value: power.value.toFixed(1), unit: 'W' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Light bulb visualization
    const bulbX = 200;
    const bulbY = 200;
    const brightness = Math.min(1, power.value / 100);

    // Glow effect
    const glowGrad = c.createRadialGradient(bulbX, bulbY, 0, bulbX, bulbY, 100 * brightness);
    glowGrad.addColorStop(0, `rgba(253, 224, 71, ${brightness})`);
    glowGrad.addColorStop(0.5, `rgba(253, 224, 71, ${brightness * 0.3})`);
    glowGrad.addColorStop(1, 'transparent');
    c.fillStyle = glowGrad;
    c.fillRect(bulbX - 100, bulbY - 100, 200, 200);

    // Bulb glass
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.arc(bulbX, bulbY - 20, 50, Math.PI * 0.2, Math.PI * 0.8, true);
    c.lineTo(bulbX - 25, bulbY + 30);
    c.lineTo(bulbX - 25, bulbY + 50);
    c.lineTo(bulbX + 25, bulbY + 50);
    c.lineTo(bulbX + 25, bulbY + 30);
    c.closePath();
    c.stroke();

    // Filament
    if (brightness > 0.1) {
        c.strokeStyle = `rgba(253, 224, 71, ${brightness})`;
        c.lineWidth = 2;
        c.beginPath();
        c.moveTo(bulbX - 15, bulbY + 30);
        for (let i = 0; i < 6; i++) {
            c.lineTo(bulbX - 10 + i * 5, bulbY - 20 + (i % 2) * 20);
        }
        c.lineTo(bulbX + 15, bulbY + 30);
        c.stroke();
    }

    // Base
    c.fillStyle = '#334155';
    c.fillRect(bulbX - 25, bulbY + 50, 50, 20);

    // Power meter display
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(350, 50, 400, 350);

    // Title
    c.fillStyle = '#f59e0b';
    c.font = 'bold 18px Inter';
    c.textAlign = 'center';
    c.fillText('Elektr Quvvati', 550, 90);

    // Formulas
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'left';

    c.fillText('Asosiy formulalar:', 380, 130);
    c.fillStyle = '#94a3b8';
    c.font = '16px serif';
    c.fillText('P = U × I', 400, 160);
    c.fillText('P = I² × R', 400, 185);
    c.fillText('P = U² / R', 400, 210);

    // Current values
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.fillText('Hisoblangan qiymatlar:', 380, 250);

    c.fillStyle = '#3b82f6';
    c.fillText(`Kuchlanish (U): ${voltage.value} V`, 400, 280);
    c.fillStyle = '#22c55e';
    c.fillText(`Tok kuchi (I): ${current.value} A`, 400, 305);
    c.fillStyle = '#f59e0b';
    c.fillText(`Qarshilik (R): ${resistance.value.toFixed(1)} Ω`, 400, 330);

    c.fillStyle = '#ef4444';
    c.font = 'bold 18px Inter';
    c.fillText(`Quvvat (P): ${power.value.toFixed(1)} W`, 400, 365);

    // Energy and cost
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.fillText(`1 soatda: ${energy1h.value.toFixed(1)} Wh = ${(energy1h.value / 1000).toFixed(3)} kWh`, 400, 395);
    c.fillText(`Narxi (1 soat): ${cost.value.toFixed(0)} so'm`, 400, 415);

    // Power bar
    const barWidth = 300;
    const barHeight = 30;
    const barX = 50;
    const barY = 380;

    c.fillStyle = '#334155';
    c.fillRect(barX, barY, barWidth, barHeight);

    const powerPercent = Math.min(1, power.value / 200);
    const barGrad = c.createLinearGradient(barX, 0, barX + barWidth * powerPercent, 0);
    barGrad.addColorStop(0, '#22c55e');
    barGrad.addColorStop(0.5, '#f59e0b');
    barGrad.addColorStop(1, '#ef4444');
    c.fillStyle = barGrad;
    c.fillRect(barX, barY, barWidth * powerPercent, barHeight);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText(`${power.value.toFixed(0)}W / 200W`, barX + barWidth / 2, barY + 20);
};

watch([voltage, current], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Kuchlanish: <strong>{{ voltage }} V</strong></label>
                <input type="range" v-model.number="voltage" :min="1" :max="24" :step="1" />
            </div>
            <div class="control-group">
                <label>Tok: <strong>{{ current }} A</strong></label>
                <input type="range" v-model.number="current" :min="0.5" :max="5" :step="0.5" />
            </div>
            <div class="power-display">
                <span>Quvvat:</span>
                <strong>{{ power.toFixed(1) }} W</strong>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
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

.power-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #0f172a;
    border-radius: 8px;
}

.power-display span {
    font-size: 0.75rem;
    color: #94a3b8;
}

.power-display strong {
    font-size: 1.25rem;
    color: #f59e0b;
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
