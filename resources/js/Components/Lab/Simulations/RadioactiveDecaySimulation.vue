<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { decayConstant as lambdaOf, remainingNuclei, activity, sampleDecayTime } from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const halfLife = ref(10); // seconds
const initialAtoms = ref(1000);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// Each nucleus stores a uniform number u ∈ (0,1]; its decay time is τ = −ln(u)/λ.
// "Decayed at t" ⇔ τ ≤ t — exact for any frame rate; the count converges to N0·e^(−λt).
const atoms = ref([]);
const time = ref(0); // elapsed time [s]
let lastTs = null;

const decayConstant = computed(() => lambdaOf(halfLife.value)); // λ = ln2 / T½
const expectedRemaining = computed(() => remainingNuclei(initialAtoms.value, time.value, halfLife.value));
const expectedActivity = computed(() => activity(initialAtoms.value, time.value, halfLife.value)); // A = λN [Bq]
const decayedCount = computed(() => {
    const t = time.value;
    let n = 0;
    for (const a of atoms.value) if (a.tau <= t) n++;
    return n;
});

const canvasConfig = computed(() => ({ width: 800, height: 500 }));
const maxTime = computed(() => halfLife.value * 5);

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        initAtoms();
        draw();
    }
};

const initAtoms = () => {
    const lam = decayConstant.value;
    const list = [];
    for (let i = 0; i < initialAtoms.value; i++) {
        list.push({
            x: 100 + Math.random() * 300,
            y: 100 + Math.random() * 300,
            tau: sampleDecayTime(1 - Math.random(), lam), // 1 − U ∈ (0, 1]
        });
    }
    atoms.value = list;
};

const start = () => {
    if (isRunning.value) return;
    initAtoms();
    time.value = 0;
    lastTs = null;
    isRunning.value = true;
    animationId.value = requestAnimationFrame(animate);
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    initAtoms();
    time.value = 0;
    lastTs = null;
    draw();
};

const animate = (ts) => {
    if (!isRunning.value || props.isPaused) { lastTs = null; return; }
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    time.value = Math.min(maxTime.value, time.value + dt);

    // Stop when most atoms decayed (or the graph's 5·T½ window is reached)
    if (decayedCount.value >= initialAtoms.value * 0.95 || time.value >= maxTime.value) {
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
    const t = time.value;
    atoms.value.forEach(atom => {
        c.beginPath();
        c.arc(atom.x, atom.y, 4, 0, Math.PI * 2);
        c.fillStyle = atom.tau <= t ? '#22c55e' : '#3b82f6';
        c.fill();
    });

    // Decay graph
    const graphX = 480;
    const graphY = 80;
    const graphW = 280;
    const graphH = 200;

    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(graphX, graphY, graphW, graphH);

    // Axes: N axis on the left, time axis along the bottom (N = 0)
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 40, graphY + 20);
    c.lineTo(graphX + 40, graphY + graphH - 30);
    c.lineTo(graphX + graphW - 20, graphY + graphH - 30);
    c.stroke();

    // Theoretical decay curve N(t) = N0·(1/2)^(t/T½)
    const tMax = maxTime.value;
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    const steps = 200;
    for (let i = 0; i <= steps; i++) {
        const tt = (tMax * i) / steps;
        const remaining = remainingNuclei(initialAtoms.value, tt, halfLife.value);
        const x = graphX + 40 + (tt / tMax) * (graphW - 60);
        const y = graphY + graphH - 30 - (remaining / initialAtoms.value) * (graphH - 50);
        if (i === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
    }
    c.stroke();

    // Current point (simulated count)
    if (time.value > 0) {
        const currentX = graphX + 40 + (time.value / tMax) * (graphW - 60);
        const currentY = graphY + graphH - 30 - ((initialAtoms.value - decayedCount.value) / initialAtoms.value) * (graphH - 50);
        c.beginPath();
        c.arc(currentX, currentY, 6, 0, Math.PI * 2);
        c.fillStyle = '#ef4444';
        c.fill();
    }

    // Half-life marker
    const halfX = graphX + 40 + (halfLife.value / tMax) * (graphW - 60);
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
    c.fillRect(graphX, graphY + graphH + 20, graphW, 175);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Yarim yemirilish davri: ${halfLife.value} s`, graphX + 15, graphY + graphH + 42);
    c.fillText(`λ = ln2/T½ = ${decayConstant.value.toFixed(4)} s⁻¹`, graphX + 15, graphY + graphH + 60);
    c.fillText(`Dastlabki atomlar: ${initialAtoms.value}`, graphX + 15, graphY + graphH + 78);
    c.fillStyle = '#3b82f6';
    c.fillText(`Qolgan: ${initialAtoms.value - decayedCount.value}`, graphX + 15, graphY + graphH + 100);
    c.fillStyle = '#f59e0b';
    c.fillText(`Nazariy N(t): ${expectedRemaining.value.toFixed(1)}`, graphX + 140, graphY + graphH + 100);
    c.fillStyle = '#22c55e';
    c.fillText(`Parchalangan: ${decayedCount.value}`, graphX + 15, graphY + graphH + 120);
    c.fillStyle = '#cbd5e1';
    c.fillText(`Aktivlik A = λN = ${expectedActivity.value.toFixed(1)} Bq`, graphX + 15, graphY + graphH + 140);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s  (t/T½ = ${(time.value / halfLife.value).toFixed(2)})`, graphX + 15, graphY + graphH + 162);

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '12px serif';
    c.fillText('N(t) = N₀ × e^(-λt)', 100, 430);
};

watch(() => props.isPaused, (p) => {
    if (!p && isRunning.value) { lastTs = null; animationId.value = requestAnimationFrame(animate); }
});
watch([halfLife, initialAtoms], () => { if (!isRunning.value) { initAtoms(); time.value = 0; draw(); } });
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
