<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    movingMagnetInduction, shuttleMotion, lenzInducedFieldDirection, coilFacePoles, inducedCurrent as ohmCurrent,
} from '../physics/magnetism.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetSpeed = ref(3); // m/s
const coilTurns = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// ── Physical model (SI) ─────────────────────────────────────────────────────
// 1 px = 1 mm. Coil radius 5 cm at x = 400 px; magnet 10 cm long (poles 8.5 cm apart),
// m = 3 A·m², N on the left. Circuit: galvanometer 10 Ω + 0.02 Ω per turn.
const COIL_PX = 400;
const M_PER_PX = 1e-3;
const COIL_RADIUS = 0.05;
const MAGNET_MOMENT = 3.0;
const POLE_SEPARATION = 0.085;
const X_MIN = -0.2;
const X_MAX = 0.2;
const SLOW_MOTION = 20;
const circuitResistance = computed(() => 10 + 0.02 * coilTurns.value); // Ω

const time = ref(0); // physical time [s]
let lastTs = null;

const motion = computed(() => (isRunning.value || time.value > 0)
    ? shuttleMotion(time.value, X_MIN, X_MAX, magnetSpeed.value)
    : { x: X_MIN, v: 0 });

const induction = computed(() => movingMagnetInduction({
    turns: coilTurns.value,
    moment: MAGNET_MOMENT,
    poleSeparation: POLE_SEPARATION,
    radius: COIL_RADIUS,
    position: motion.value.x,
    velocity: motion.value.v,
    northOnLeft: true,
}));

const magnetPos = computed(() => COIL_PX + motion.value.x / M_PER_PX);
const magnetDir = computed(() => Math.sign(motion.value.v) || 1);
// Induced current I = ε / R  [A]
const inducedCurrent = computed(() => ohmCurrent(induction.value.emf, circuitResistance.value));
// Lenz: B' opposes dΦ/dt (+1 → B' along +x)
const inducedDir = computed(() => lenzInducedFieldDirection(induction.value.fluxRate));
const facePoles = computed(() => coilFacePoles(inducedDir.value));
// Approaching ⇔ the distance |x| is decreasing ⇔ x·v < 0
const approaching = computed(() => motion.value.x * motion.value.v < 0);

const I_FULL_SCALE = (() => {
    let peak = 0;
    for (let i = 0; i <= 400; i++) {
        const x = X_MIN + ((X_MAX - X_MIN) * i) / 400;
        const { emf } = movingMagnetInduction({ turns: 100, moment: MAGNET_MOMENT, poleSeparation: POLE_SEPARATION, radius: COIL_RADIUS, position: x, velocity: 6 });
        peak = Math.max(peak, Math.abs(emf) / (10 + 0.02 * 100));
    }
    return peak;
})();

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    time.value = 0;
    lastTs = null;
    isRunning.value = true;
    animationId.value = requestAnimationFrame(animate);
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    time.value = 0;
    lastTs = null;
    draw();
};

const animate = (ts) => {
    if (!isRunning.value || props.isPaused) { lastTs = null; return; }
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    time.value += dt / SLOW_MOTION;
    draw();
    if (isRunning.value) animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerY = 225;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Lenz's Law explanation
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(20, 20, 300, 80);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Lents qonuni:', 35, 45);
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.fillText('Induksion tok magnit oqimi', 35, 65);
    c.fillText('o\'zgarishiga qarshi yo\'naladi', 35, 85);

    // Coil
    const coilX = 400;
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 4;
    for (let i = 0; i < 8; i++) {
        const x = coilX - 30 + i * 8;
        c.beginPath();
        c.ellipse(x, centerY, 6, 50, 0, 0, Math.PI * 2);
        c.stroke();
    }

    const significant = Math.abs(inducedCurrent.value) > 0.03 * I_FULL_SCALE;

    // Induced current direction (right-hand rule for B'): for B' → (+x) the current comes
    // out of the screen (⊙) at the top of the turns and goes in (⊗) at the bottom.
    if (significant && inducedDir.value !== 0) {
        const topOut = inducedDir.value > 0;
        const drawDot = (x, y) => {
            c.beginPath(); c.arc(x, y, 7, 0, Math.PI * 2); c.stroke();
            c.beginPath(); c.arc(x, y, 2, 0, Math.PI * 2); c.fill();
        };
        const drawCross = (x, y) => {
            c.beginPath(); c.arc(x, y, 7, 0, Math.PI * 2); c.stroke();
            c.beginPath();
            c.moveTo(x - 5, y - 5); c.lineTo(x + 5, y + 5);
            c.moveTo(x + 5, y - 5); c.lineTo(x - 5, y + 5);
            c.stroke();
        };
        c.strokeStyle = '#22c55e';
        c.fillStyle = '#22c55e';
        c.lineWidth = 2;
        (topOut ? drawDot : drawCross)(coilX, centerY - 62);
        (topOut ? drawCross : drawDot)(coilX, centerY + 62);

        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText('I (induksion)', coilX, centerY + 88);
    }

    // Magnet
    const magY = centerY;
    c.fillStyle = '#ef4444';
    c.fillRect(magnetPos.value - 50, magY - 20, 50, 40);
    c.fillStyle = '#3b82f6';
    c.fillRect(magnetPos.value, magY - 20, 50, 40);

    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText('N', magnetPos.value - 25, magY + 6);
    c.fillText('S', magnetPos.value + 25, magY + 6);

    // Movement arrow
    if (isRunning.value) {
        c.fillStyle = '#f59e0b';
        c.beginPath();
        if (magnetDir.value > 0) {
            c.moveTo(magnetPos.value + 70, magY);
            c.lineTo(magnetPos.value + 55, magY - 10);
            c.lineTo(magnetPos.value + 55, magY + 10);
        } else {
            c.moveTo(magnetPos.value - 70, magY);
            c.lineTo(magnetPos.value - 55, magY - 10);
            c.lineTo(magnetPos.value - 55, magY + 10);
        }
        c.closePath();
        c.fill();
    }

    // Induced magnetic field B' inside the coil (opposes the change of flux)
    if (significant && inducedDir.value !== 0) {
        const fieldDir = inducedDir.value;
        c.strokeStyle = 'rgba(139, 92, 246, 0.7)';
        c.fillStyle = 'rgba(139, 92, 246, 0.9)';
        c.lineWidth = 2;
        c.setLineDash([5, 5]);
        for (let i = -1; i <= 1; i++) {
            const y = centerY + i * 30;
            c.beginPath();
            c.moveTo(coilX - 50 * fieldDir, y);
            c.lineTo(coilX + 50 * fieldDir, y);
            c.stroke();
        }
        c.setLineDash([]);
        for (let i = -1; i <= 1; i++) {
            const y = centerY + i * 30;
            const tip = coilX + 58 * fieldDir;
            c.beginPath();
            c.moveTo(tip, y);
            c.lineTo(tip - 10 * fieldDir, y - 5);
            c.lineTo(tip - 10 * fieldDir, y + 5);
            c.closePath();
            c.fill();
        }

        c.fillStyle = '#8b5cf6';
        c.font = '11px Inter';
        c.textAlign = 'center';
        c.fillText('B\' (qarshi)', coilX, centerY - 80);
        // Poles formed on the coil ends by the induced current
        c.font = 'bold 14px Inter';
        c.fillText(facePoles.value.left, coilX - 45, centerY - 58);
        c.fillText(facePoles.value.right, coilX + 45, centerY - 58);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 230, 20, 210, 150);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Magnit tezligi: ${magnetSpeed.value} m/s`, w - 215, 45);
    c.fillText(`O'ramlar: ${coilTurns.value}   R = ${circuitResistance.value.toFixed(1)} Ω`, w - 215, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`ε = ${(induction.value.emf * 1e3).toFixed(1)} mV`, w - 215, 87);
    c.fillStyle = '#22c55e';
    c.fillText(`I = ε/R = ${(inducedCurrent.value * 1e3).toFixed(2)} mA`, w - 215, 107);
    c.fillStyle = '#8b5cf6';
    if (motion.value.v === 0) c.fillText('Magnit tinch', w - 215, 129);
    else c.fillText(`${magnetDir.value > 0 ? '→' : '←'} ${approaching.value ? 'Yaqinlashmoqda' : 'Uzoqlashmoqda'}`, w - 215, 129);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText(`t = ${time.value.toFixed(3)} s (×${SLOW_MOTION} sekin)`, w - 215, 152);
};

watch(() => props.isPaused, (p) => {
    if (!p && isRunning.value) { lastTs = null; animationId.value = requestAnimationFrame(animate); }
});
watch([magnetSpeed, coilTurns], () => { if (!isRunning.value) draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tezlik: <strong>{{ magnetSpeed }} m/s</strong></label>
                <input type="range" v-model.number="magnetSpeed" :min="1" :max="6" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="20" :max="100" :step="10" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Boshlash</button>
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
    min-width: 130px;
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
    background: #10b981;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
