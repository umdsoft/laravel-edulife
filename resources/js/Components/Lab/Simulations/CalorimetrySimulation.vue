<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const hotMass = ref(200);
const hotTemp = ref(80);
const coldMass = ref(300);
const coldTemp = ref(20);
const isMixed = ref(false);

const canvas = ref(null);
const ctx = ref(null);

// Specific heat of water
const c = 4186;
const finalTemp = computed(() => {
    return (hotMass.value * hotTemp.value + coldMass.value * coldTemp.value) /
        (hotMass.value + coldMass.value);
});
const heatTransferred = computed(() => {
    return hotMass.value * c * (hotTemp.value - finalTemp.value) / 1000;
});

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const mix = () => { isMixed.value = true; draw(); };
const reset = () => { isMixed.value = false; draw(); };

const measure = () => {
    emit('measurement', { name: 'Yakuniy harorat', value: finalTemp.value.toFixed(1), unit: '°C' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    if (!isMixed.value) {
        // Hot water container
        c.fillStyle = '#334155';
        c.fillRect(100, 150, 150, 200);
        const hotGrad = c.createLinearGradient(100, 350, 100, 200);
        hotGrad.addColorStop(0, '#ef4444');
        hotGrad.addColorStop(1, '#f97316');
        c.fillStyle = hotGrad;
        c.fillRect(105, 200, 140, 145);

        c.fillStyle = 'white';
        c.font = 'bold 18px Inter';
        c.textAlign = 'center';
        c.fillText(`${hotTemp.value}°C`, 175, 280);
        c.font = '12px Inter';
        c.fillText(`${hotMass.value}g`, 175, 310);
        c.fillText('Issiq suv', 175, 380);

        // Cold water container
        c.fillStyle = '#334155';
        c.fillRect(550, 150, 150, 200);
        const coldGrad = c.createLinearGradient(550, 350, 550, 200);
        coldGrad.addColorStop(0, '#3b82f6');
        coldGrad.addColorStop(1, '#06b6d4');
        c.fillStyle = coldGrad;
        c.fillRect(555, 220, 140, 125);

        c.fillStyle = 'white';
        c.font = 'bold 18px Inter';
        c.fillText(`${coldTemp.value}°C`, 625, 280);
        c.font = '12px Inter';
        c.fillText(`${coldMass.value}g`, 625, 310);
        c.fillText('Sovuq suv', 625, 380);

        // Arrow
        c.fillStyle = '#f59e0b';
        c.beginPath();
        c.moveTo(350, 250);
        c.lineTo(400, 250);
        c.lineTo(400, 240);
        c.lineTo(430, 255);
        c.lineTo(400, 270);
        c.lineTo(400, 260);
        c.lineTo(350, 260);
        c.closePath();
        c.fill();
        c.font = '14px Inter';
        c.textAlign = 'center';
        c.fillText('Aralashtirish', 390, 300);
    } else {
        // Mixed container
        c.fillStyle = '#334155';
        c.fillRect(300, 120, 200, 250);

        const mixGrad = c.createLinearGradient(300, 370, 300, 180);
        const tempRatio = (finalTemp.value - 20) / 60;
        mixGrad.addColorStop(0, '#f59e0b');
        mixGrad.addColorStop(1, `rgb(${Math.round(239 * tempRatio + 59 * (1 - tempRatio))}, ${Math.round(68 * tempRatio + 130 * (1 - tempRatio))}, ${Math.round(68 * tempRatio + 246 * (1 - tempRatio))})`);
        c.fillStyle = mixGrad;
        c.fillRect(305, 180, 190, 185);

        c.fillStyle = 'white';
        c.font = 'bold 24px Inter';
        c.textAlign = 'center';
        c.fillText(`${finalTemp.value.toFixed(1)}°C`, 400, 280);
        c.font = '14px Inter';
        c.fillText(`${hotMass.value + coldMass.value}g`, 400, 320);
        c.fillText('Aralashma', 400, 400);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 240, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText('Kalorimetriya tenglamasi:', 35, 45);
    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('m₁c(T₁ - Tf) = m₂c(Tf - T₂)', 35, 70);

    c.fillStyle = '#f59e0b';
    c.font = '12px Inter';
    c.fillText(`Yakuniy T: ${finalTemp.value.toFixed(1)}°C`, 35, 100);
    c.fillText(`Q = ${heatTransferred.value.toFixed(1)} kJ`, 35, 120);
};

watch([hotMass, hotTemp, coldMass, coldTemp], () => { isMixed.value = false; draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Issiq: <strong>{{ hotMass }}g, {{ hotTemp }}°C</strong></label>
                <input type="range" v-model.number="hotMass" :min="100" :max="500" :step="50" />
                <input type="range" v-model.number="hotTemp" :min="50" :max="100" :step="5" />
            </div>
            <div class="control-group">
                <label>Sovuq: <strong>{{ coldMass }}g, {{ coldTemp }}°C</strong></label>
                <input type="range" v-model.number="coldMass" :min="100" :max="500" :step="50" />
                <input type="range" v-model.number="coldTemp" :min="5" :max="30" :step="5" />
            </div>
            <div class="buttons">
                <button v-if="!isMixed" @click="mix" class="btn mix">🧪 Aralashtirish</button>
                <button v-else @click="reset" class="btn reset">🔄</button>
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
    gap: 1.5rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 150px;
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
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn.mix {
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
