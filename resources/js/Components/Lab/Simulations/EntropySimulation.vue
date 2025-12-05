<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const processType = ref('isothermal');
const initialTemp = ref(300);
const heatAdded = ref(500);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const entropyChange = computed(() => {
    if (processType.value === 'isothermal') {
        return heatAdded.value / initialTemp.value;
    } else if (processType.value === 'isobaric') {
        return heatAdded.value / initialTemp.value * 1.2;
    } else {
        return 0;
    }
});

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

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

    // TS Diagram
    const graphX = 50;
    const graphY = 50;
    const graphW = 400;
    const graphH = 300;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 50, graphY + graphH - 40);
    c.lineTo(graphX + 50, graphY + 20);
    c.lineTo(graphX + graphW - 20, graphY + 20);
    c.stroke();

    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('S (J/K)', graphX + graphW - 40, graphY + 35);
    c.fillText('T (K)', graphX + 30, graphY + graphH - 50);

    // Process visualization
    const startS = 100;
    const startT = initialTemp.value;
    const endS = startS + entropyChange.value * 50;

    const scaleS = (s) => graphX + 50 + s * 2;
    const scaleT = (t) => graphY + graphH - 40 - (t - 200) * 0.5;

    // Process path
    c.strokeStyle = '#22c55e';
    c.lineWidth = 3;
    c.beginPath();

    if (processType.value === 'isothermal') {
        c.moveTo(scaleS(startS), scaleT(startT));
        c.lineTo(scaleS(endS), scaleT(startT));
    } else if (processType.value === 'isobaric') {
        c.moveTo(scaleS(startS), scaleT(startT));
        const endT = startT + heatAdded.value * 0.3;
        c.quadraticCurveTo(
            scaleS((startS + endS) / 2), scaleT((startT + endT) / 2),
            scaleS(endS), scaleT(endT)
        );
    } else {
        c.moveTo(scaleS(startS), scaleT(startT));
        c.lineTo(scaleS(startS), scaleT(startT + 100));
    }
    c.stroke();

    // Area under curve (heat)
    if (entropyChange.value > 0) {
        c.fillStyle = 'rgba(34, 197, 94, 0.3)';
        c.beginPath();
        c.moveTo(scaleS(startS), graphY + graphH - 40);
        c.lineTo(scaleS(startS), scaleT(startT));
        c.lineTo(scaleS(endS), scaleT(startT));
        c.lineTo(scaleS(endS), graphY + graphH - 40);
        c.closePath();
        c.fill();

        c.fillStyle = '#22c55e';
        c.font = '14px Inter';
        c.fillText('Q = T·ΔS', scaleS((startS + endS) / 2), scaleT(startT) + 30);
    }

    // Entropy visualization (particles)
    const particleArea = { x: 550, y: 100, w: 300, h: 200 };

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(particleArea.x, particleArea.y, particleArea.w, particleArea.h);
    c.strokeStyle = '#475569';
    c.strokeRect(particleArea.x, particleArea.y, particleArea.w, particleArea.h);

    // Draw particles with disorder based on entropy
    const numParticles = 30;
    const disorder = entropyChange.value / 2;

    for (let i = 0; i < numParticles; i++) {
        const baseX = particleArea.x + 40 + (i % 6) * 40;
        const baseY = particleArea.y + 30 + Math.floor(i / 6) * 35;

        const offsetX = Math.sin(time.value * 3 + i) * disorder * 10;
        const offsetY = Math.cos(time.value * 2 + i * 0.5) * disorder * 10;

        c.beginPath();
        c.arc(baseX + offsetX, baseY + offsetY, 8, 0, Math.PI * 2);
        c.fillStyle = `hsl(${200 + disorder * 50}, 70%, 50%)`;
        c.fill();
    }

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('Molekulalar tarqalishi', particleArea.x + particleArea.w / 2, particleArea.y - 10);
    c.fillText(entropyChange.value > 0 ? '↑ Ko\'proq tartibsizlik' : '= Tartibsizlik o\'zgarmaydi',
        particleArea.x + particleArea.w / 2, particleArea.y + particleArea.h + 25);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(550, 320, 300, 110);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Jarayon: ${processType.value === 'isothermal' ? 'Izotermik' :
        processType.value === 'isobaric' ? 'Izobarik' : 'Adiabatik'}`, 565, 345);
    c.fillText(`T = ${initialTemp.value} K`, 565, 365);
    c.fillText(`Q = ${heatAdded.value} J`, 565, 385);
    c.fillStyle = '#22c55e';
    c.font = 'bold 14px Inter';
    c.fillText(`ΔS = ${entropyChange.value.toFixed(2)} J/K`, 565, 410);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('ΔS = Q/T (izotermik)', 700, 385);
};

watch([processType, initialTemp, heatAdded], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Jarayon turi:</label>
                <select v-model="processType">
                    <option value="isothermal">Izotermik</option>
                    <option value="isobaric">Izobarik</option>
                    <option value="adiabatic">Adiabatik</option>
                </select>
            </div>
            <div class="control-group">
                <label>Harorat: <strong>{{ initialTemp }} K</strong></label>
                <input type="range" v-model.number="initialTemp" :min="200" :max="500" :step="50" />
            </div>
            <div class="control-group">
                <label>Issiqlik: <strong>{{ heatAdded }} J</strong></label>
                <input type="range" v-model.number="heatAdded" :min="100" :max="1000" :step="100"
                    :disabled="processType === 'adiabatic'" />
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

.control-group select {
    padding: 0.5rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
}
</style>
