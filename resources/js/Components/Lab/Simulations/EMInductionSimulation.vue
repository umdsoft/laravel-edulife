<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { movingMagnetInduction, shuttleMotion, inductionPeakEMF, labDmmReading, LAB_DMM } from '../physics/magnetism.js';
import { createRng, seedFrom, quantize, dmmResolution } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetSpeed = ref(2); // m/s
const coilTurns = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// ── Physical model (SI) ─────────────────────────────────────────────────────
// 1 px = 1 mm. Coil: radius a = 6 cm at x = 400 px. Bar magnet: 12 cm long,
// effective poles 10 cm apart, moment m = 3 A·m², N pole on the left.
const COIL_PX = 400;
const M_PER_PX = 1e-3;
const COIL_RADIUS = 0.06;
const MAGNET_MOMENT = 3.0;
const POLE_SEPARATION = 0.10;
const X_MIN = -0.3; // magnet centre travel limits relative to the coil [m]
const X_MAX = 0.3;
// The real motion (≈0.1 s per pass) is shown 20× slower; all values are physical.
const SLOW_MOTION = 20;

const time = ref(0); // physical time since start [s]
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
const inducedEMF = computed(() => induction.value.emf);
const fluxChange = computed(() => induction.value.fluxRate);

// Galvanometer full-scale = peak EMF at the largest slider settings (N = 100, v = 5 m/s).
const EMF_FULL_SCALE = (() => {
    let peak = 0;
    for (let i = 0; i <= 600; i++) {
        const x = X_MIN + ((X_MAX - X_MIN) * i) / 600;
        const { emf } = movingMagnetInduction({ turns: 100, moment: MAGNET_MOMENT, poleSeparation: POLE_SEPARATION, radius: COIL_RADIUS, position: x, velocity: 5 });
        peak = Math.max(peak, Math.abs(emf));
    }
    return peak;
})();

// Peak-hold millivoltmeter (3½-digit DMM, 200 mV range → 0.1 mV): |ε|max over the part of
// the stroke travelled since "Boshlash" (|ε| is symmetric, so the return strokes repeat it).
const travelled = computed(() => Math.min(X_MAX - X_MIN, magnetSpeed.value * time.value));
const peakEMF = computed(() => (time.value > 0
    ? inductionPeakEMF({
        turns: coilTurns.value, moment: MAGNET_MOMENT, poleSeparation: POLE_SEPARATION, radius: COIL_RADIUS,
        speed: magnetSpeed.value, xFrom: X_MIN, xTo: X_MIN + travelled.value,
    }).emf
    : 0));
const lastReading = ref(null); // mV
const readCounts = new Map();
const measure = () => {
    const key = seedFrom('EMInductionSimulation', coilTurns.value, magnetSpeed.value, travelled.value);
    const k = readCounts.get(key) ?? 0;
    readCounts.set(key, k + 1);
    const volts = labDmmReading(peakEMF.value, createRng(seedFrom(key, k)), LAB_DMM.peakSigmaRel);
    const value = quantize(volts * 1000, dmmResolution(volts) * 1000);
    lastReading.value = value;
    emit('measurement', { name: `EYuK amplitudasi (N=${coilTurns.value}, v=${magnetSpeed.value} m/s)`, value, unit: 'mV' });
};

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
    lastReading.value = null;
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

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const coilX = 400;
    const coilY = 225;

    // Coil
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 4;
    for (let i = 0; i < 8; i++) {
        const x = coilX - 40 + i * 10;
        c.beginPath();
        c.ellipse(x, coilY, 8, 60, 0, 0, Math.PI * 2);
        c.stroke();
    }

    // Galvanometer connection
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(coilX - 50, coilY - 65);
    c.lineTo(coilX - 50, coilY - 120);
    c.lineTo(coilX + 50, coilY - 120);
    c.lineTo(coilX + 50, coilY - 65);
    c.stroke();

    // Galvanometer
    c.beginPath();
    c.arc(coilX, coilY - 150, 40, 0, Math.PI * 2);
    c.fillStyle = '#1e293b';
    c.fill();
    c.strokeStyle = '#475569';
    c.stroke();

    // Needle
    const needleAngle = Math.max(-0.8, Math.min(0.8, 0.8 * inducedEMF.value / EMF_FULL_SCALE));
    c.strokeStyle = '#ef4444';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(coilX, coilY - 150);
    c.lineTo(coilX + 30 * Math.sin(needleAngle), coilY - 150 - 30 * Math.cos(needleAngle));
    c.stroke();

    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.textAlign = 'center';
    c.fillText('G', coilX, coilY - 145);

    // Magnet
    const magX = magnetPos.value;
    const magY = coilY;

    // N pole
    c.fillStyle = '#ef4444';
    c.fillRect(magX - 60, magY - 20, 60, 40);
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.fillText('N', magX - 30, magY + 6);

    // S pole
    c.fillStyle = '#3b82f6';
    c.fillRect(magX, magY - 20, 60, 40);
    c.fillStyle = 'white';
    c.fillText('S', magX + 30, magY + 6);

    // Direction arrow
    if (isRunning.value) {
        c.fillStyle = '#22c55e';
        c.beginPath();
        if (magnetDir.value > 0) {
            c.moveTo(magX + 80, magY);
            c.lineTo(magX + 65, magY - 10);
            c.lineTo(magX + 65, magY + 10);
        } else {
            c.moveTo(magX - 80, magY);
            c.lineTo(magX - 65, magY - 10);
            c.lineTo(magX - 65, magY + 10);
        }
        c.closePath();
        c.fill();
    }

    // EMF visualization
    const emfColor = inducedEMF.value >= 0 ? '#22c55e' : '#ef4444';
    if (Math.abs(inducedEMF.value) > 0.02 * EMF_FULL_SCALE) {
        c.strokeStyle = emfColor;
        c.lineWidth = 2;
        c.setLineDash([5, 5]);
        c.beginPath();
        c.arc(coilX, coilY, 80, 0, Math.PI * 2);
        c.stroke();
        c.setLineDash([]);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 230, 215);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`O'ramlar soni: ${coilTurns.value}`, 35, 45);
    c.fillText(`Magnit tezligi: ${magnetSpeed.value} m/s`, 35, 65);
    c.fillStyle = '#cbd5e1';
    c.fillText(`Φ (1 o'ram) = ${(induction.value.flux * 1e6).toFixed(2)} µWb`, 35, 88);
    c.fillStyle = '#f59e0b';
    c.fillText(`dΦ/dt = ${(fluxChange.value * 1e3).toFixed(3)} mWb/s`, 35, 108);
    c.fillStyle = emfColor;
    c.fillText(`ε = ${(inducedEMF.value * 1e3).toFixed(1)} mV`, 35, 131);

    c.fillStyle = '#94a3b8';
    c.font = '11px serif';
    c.fillText('ε = -N × dΦ/dt', 35, 154);
    c.font = '10px Inter';
    c.fillStyle = '#64748b';
    c.fillText(`t = ${time.value.toFixed(3)} s  (×${SLOW_MOTION} sekinlashtirilgan)`, 35, 176);
    c.font = '12px Inter';
    c.fillStyle = '#22c55e';
    c.fillText(`mV-metr (PEAK): ${lastReading.value === null ? "— (O'lchash)" : `${lastReading.value.toFixed(1)} mV`}`, 35, 205);
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
                <input type="range" v-model.number="magnetSpeed" :min="1" :max="5" :step="0.5" :disabled="isRunning" />
            </div>
            <div class="control-group">
                <label>O'ramlar: <strong>{{ coilTurns }}</strong></label>
                <input type="range" v-model.number="coilTurns" :min="20" :max="100" :step="10" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">▶️ Boshlash</button>
                <button @click="reset" class="btn reset">🔄</button>
                <button @click="measure" class="btn measure" title="EYuK amplitudasini o'lchash">📏 O'lchash</button>
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

.btn.measure {
    background: #3b82f6;
    color: white;
}
</style>
