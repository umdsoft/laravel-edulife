<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const hotTemp = ref(600);
const coldTemp = ref(300);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);
const stage = ref(0);

const efficiency = computed(() => 1 - coldTemp.value / hotTemp.value);
const work = computed(() => efficiency.value * 100);
const qHot = computed(() => 100);
const qCold = computed(() => qHot.value - work.value);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    time.value += 0.02;
    stage.value = Math.floor(time.value * 0.5) % 4;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const stageNames = ['Izotermik kengayish', 'Adiabatik kengayish', 'Izotermik siqilish', 'Adiabatik siqilish'];

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // PV Diagram
    const pvX = 50;
    const pvY = 50;
    const pvW = 350;
    const pvH = 280;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(pvX, pvY, pvW, pvH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(pvX + 40, pvY + pvH - 30);
    c.lineTo(pvX + 40, pvY + 20);
    c.lineTo(pvX + pvW - 20, pvY + 20);
    c.stroke();

    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('V', pvX + pvW - 30, pvY + 35);
    c.fillText('P', pvX + 55, pvY + pvH - 15);

    // Carnot cycle path
    const points = [
        { x: pvX + 100, y: pvY + 80 },   // 1 - hot, compressed
        { x: pvX + 280, y: pvY + 130 },  // 2 - hot, expanded
        { x: pvX + 300, y: pvY + 220 },  // 3 - cold, expanded
        { x: pvX + 120, y: pvY + 180 },  // 4 - cold, compressed
    ];

    // Fill cycle area
    c.fillStyle = 'rgba(34, 197, 94, 0.2)';
    c.beginPath();
    c.moveTo(points[0].x, points[0].y);
    c.quadraticCurveTo(pvX + 190, pvY + 90, points[1].x, points[1].y);
    c.lineTo(points[2].x, points[2].y);
    c.quadraticCurveTo(pvX + 210, pvY + 210, points[3].x, points[3].y);
    c.closePath();
    c.fill();

    // Draw cycle lines
    const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6'];

    // 1→2 Isothermal expansion (hot)
    c.strokeStyle = colors[0];
    c.lineWidth = stage.value === 0 ? 4 : 2;
    c.beginPath();
    c.moveTo(points[0].x, points[0].y);
    c.quadraticCurveTo(pvX + 190, pvY + 90, points[1].x, points[1].y);
    c.stroke();

    // 2→3 Adiabatic expansion
    c.strokeStyle = colors[1];
    c.lineWidth = stage.value === 1 ? 4 : 2;
    c.beginPath();
    c.moveTo(points[1].x, points[1].y);
    c.lineTo(points[2].x, points[2].y);
    c.stroke();

    // 3→4 Isothermal compression (cold)
    c.strokeStyle = colors[2];
    c.lineWidth = stage.value === 2 ? 4 : 2;
    c.beginPath();
    c.moveTo(points[2].x, points[2].y);
    c.quadraticCurveTo(pvX + 210, pvY + 210, points[3].x, points[3].y);
    c.stroke();

    // 4→1 Adiabatic compression
    c.strokeStyle = colors[3];
    c.lineWidth = stage.value === 3 ? 4 : 2;
    c.beginPath();
    c.moveTo(points[3].x, points[3].y);
    c.lineTo(points[0].x, points[0].y);
    c.stroke();

    // Point labels
    c.fillStyle = 'white';
    c.font = 'bold 12px Inter';
    ['1', '2', '3', '4'].forEach((label, i) => {
        c.beginPath();
        c.arc(points[i].x, points[i].y, 8, 0, Math.PI * 2);
        c.fillStyle = '#1e293b';
        c.fill();
        c.fillStyle = 'white';
        c.fillText(label, points[i].x, points[i].y + 4);
    });

    // Energy flow diagram
    const flowX = 480;

    // Hot reservoir
    c.fillStyle = '#ef4444';
    c.fillRect(flowX, 60, 150, 60);
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText('Issiq manba', flowX + 75, 85);
    c.fillText(`TH = ${hotTemp.value} K`, flowX + 75, 105);

    // Engine
    c.fillStyle = '#22c55e';
    c.fillRect(flowX + 25, 180, 100, 80);
    c.fillStyle = 'white';
    c.fillText('Issiqlik', flowX + 75, 210);
    c.fillText('dvigatel', flowX + 75, 230);

    // Cold reservoir
    c.fillStyle = '#3b82f6';
    c.fillRect(flowX, 320, 150, 60);
    c.fillStyle = 'white';
    c.fillText('Sovuq manba', flowX + 75, 345);
    c.fillText(`TC = ${coldTemp.value} K`, flowX + 75, 365);

    // Arrows
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(flowX + 75, 120);
    c.lineTo(flowX + 75, 180);
    c.stroke();
    c.fillStyle = '#ef4444';
    c.fillText(`QH = ${qHot.value}`, flowX + 75, 155);

    c.strokeStyle = '#3b82f6';
    c.beginPath();
    c.moveTo(flowX + 75, 260);
    c.lineTo(flowX + 75, 320);
    c.stroke();
    c.fillStyle = '#3b82f6';
    c.fillText(`QC = ${qCold.value.toFixed(1)}`, flowX + 75, 295);

    // Work output
    c.strokeStyle = '#f59e0b';
    c.beginPath();
    c.moveTo(flowX + 125, 220);
    c.lineTo(flowX + 200, 220);
    c.stroke();
    c.fillStyle = '#f59e0b';
    c.fillText(`W = ${work.value.toFixed(1)}`, flowX + 180, 205);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillStyle = colors[stage.value];
    c.font = 'bold 12px Inter';
    c.fillText(`Bosqich: ${stageNames[stage.value]}`, w - 205, 45);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText(`TH = ${hotTemp.value} K`, w - 205, 75);
    c.fillText(`TC = ${coldTemp.value} K`, w - 205, 95);
    c.fillStyle = '#22c55e';
    c.font = 'bold 14px Inter';
    c.fillText(`η = ${(efficiency.value * 100).toFixed(1)}%`, w - 205, 120);

    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('η = 1 - TC/TH', w - 205, 150);
};

watch([hotTemp, coldTemp], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Issiq TH: <strong>{{ hotTemp }} K</strong></label>
                <input type="range" v-model.number="hotTemp" :min="400" :max="800" :step="50" />
            </div>
            <div class="control-group">
                <label>Sovuq TC: <strong>{{ coldTemp }} K</strong></label>
                <input type="range" v-model.number="coldTemp" :min="200" :max="400" :step="50" />
            </div>
            <div class="efficiency">
                <span>Samaradorlik:</span>
                <strong>{{ (efficiency * 100).toFixed(1) }}%</strong>
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
    align-items: center;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 140px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.efficiency {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #22c55e;
    border-radius: 8px;
}

.efficiency span {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

.efficiency strong {
    font-size: 1.25rem;
    color: white;
}
</style>
