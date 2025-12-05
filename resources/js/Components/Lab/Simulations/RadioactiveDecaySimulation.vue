<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const halfLife = ref(10); // seconds
const initialAtoms = ref(1000);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const atoms = ref([]);
const decayedCount = ref(0);
const time = ref(0);

const decayConstant = computed(() => Math.log(2) / halfLife.value);
const expectedRemaining = computed(() => initialAtoms.value * Math.exp(-decayConstant.value * time.value));

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        initAtoms();
        draw();
    }
};

const initAtoms = () => {
    atoms.value = [];
    for (let i = 0; i < initialAtoms.value; i++) {
        atoms.value.push({
            x: 100 + Math.random() * 300,
            y: 100 + Math.random() * 300,
            decayed: false,
        });
    }
    decayedCount.value = 0;
};

const start = () => {
    if (isRunning.value) return;
    initAtoms();
    time.value = 0;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    initAtoms();
    time.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    // Decay probability per atom per frame
    const decayProb = 1 - Math.exp(-decayConstant.value * dt);

    atoms.value.forEach(atom => {
        if (!atom.decayed && Math.random() < decayProb) {
            atom.decayed = true;
            decayedCount.value++;
        }
    });

    // Stop when most atoms decayed
    if (decayedCount.value >= initialAtoms.value * 0.95) {
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

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Container
    c.strokeStyle = '#475569';
    c.lineWidth = 3;
    c.strokeRect(95, 95, 310, 310);

    // Atoms
    atoms.value.forEach(atom => {
        c.beginPath();
        c.arc(atom.x, atom.y, 4, 0, Math.PI * 2);
        c.fillStyle = atom.decayed ? '#22c55e' : '#3b82f6';
        c.fill();
    });

    // Decay graph
    const graphX = 480;
    const graphY = 80;
    const graphW = 280;
    const graphH = 200;

    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 40, graphY + graphH - 30);
    c.lineTo(graphX + 40, graphY + 20);
    c.lineTo(graphX + graphW - 20, graphY + 20);
    c.stroke();

    // Theoretical decay curve
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    const maxTime = halfLife.value * 5;
    for (let t = 0; t <= maxTime; t += 0.1) {
        const remaining = initialAtoms.value * Math.exp(-decayConstant.value * t);
        const x = graphX + 40 + (t / maxTime) * (graphW - 60);
        const y = graphY + graphH - 30 - (remaining / initialAtoms.value) * (graphH - 50);
        if (t === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current point
    if (time.value > 0) {
        const currentX = graphX + 40 + (time.value / maxTime) * (graphW - 60);
        const currentY = graphY + graphH - 30 - ((initialAtoms.value - decayedCount.value) / initialAtoms.value) * (graphH - 50);
        c.beginPath();
        c.arc(currentX, currentY, 6, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
    }

    // Half-life marker
    const halfX = graphX + 40 + (halfLife.value / maxTime) * (graphW - 60);
    const halfY = graphY + graphH - 30 - 0.5 * (graphH - 50);
    c.setLineDash([5, 5]);
    c.strokeStyle = '#22c55e';
    c.beginPath();
    c.moveTo(halfX, graphY + graphH - 30);
    c.lineTo(halfX, halfY);
    c.lineTo(graphX + 40, halfY);
    c.stroke();
    c.setLineDash([]);

    c.fillStyle = '#22c55e';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('T₁/₂', halfX, graphY + graphH - 15);
    c.fillText('50%', graphX + 25, halfY + 4);

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(graphX, graphY + graphH + 30, graphW, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Yarim yemirilish davri: ${halfLife.value} s`, graphX + 15, graphY + graphH + 55);
    c.fillText(`Dastlabki atomlar: ${initialAtoms.value}`, graphX + 15, graphY + graphH + 75);
    c.fillStyle = '#3b82f6';
    c.fillText(`Qolgan: ${initialAtoms.value - decayedCount.value}`, graphX + 15, graphY + graphH + 100);
    c.fillStyle = '#22c55e';
    c.fillText(`Parchalangan: ${decayedCount.value}`, graphX + 15, graphY + graphH + 120);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s`, graphX + 15, graphY + graphH + 145);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('N(t) = N₀ × e^(-λt)', 100, 430);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Yarim yemirilish: <strong>{{ halfLife }} s</strong></label>
                <input type="range" v-model.number="halfLife" :min="5" :max="30" :step="5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>Atomlar: <strong>{{ initialAtoms }}</strong></label>
                <input type="range" v-model.number="initialAtoms" :min="500" :max="2000" :step="250"
                    :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">☢️ Boshlash</button>
                <button @click="reset" class="btn reset">🔄</button>
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
    min-width: 150px;
}

.control-group label {
    font-size: 0.8rem;
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

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #22c55e;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
