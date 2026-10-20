<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    dopplerFrequency,
    dopplerWavelengths,
    dopplerWavefronts,
    machNumber,
    advanceClock,
    SOUND_SPEED_AIR,
    WAVE_INSTRUMENTS,
} from '../physics/waves.js';
import { quantize } from '../physics/instruments.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Sign convention (matches f' = f (v + vₒ)/(v − vₛ)):
//   vₛ > 0 — source moves TOWARD the observer, vₒ > 0 — observer moves TOWARD the source.
const sourceVelocity = ref(0);
const observerVelocity = ref(0);
const sourceFrequency = ref(440);
const soundSpeed = SOUND_SPEED_AIR;

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
let clock = { t: 0, lastMs: null };

// Visual scale (schematic): speeds are scaled to px/s, and crests are drawn
// every VIS_PERIOD seconds (a real 440 Hz period is far too short to see).
// The compression ratio λ_front/λ = 1 − vₛ/v is preserved exactly.
const PX_PER_MPS = 0.45;
const VIS_PERIOD = 0.35;
const SOURCE_X = 260;
const OBSERVER_X = 700;

const observedFrequency = computed(() =>
    dopplerFrequency(sourceFrequency.value, soundSpeed, observerVelocity.value, sourceVelocity.value));
const frequencyShift = computed(() => observedFrequency.value - sourceFrequency.value);
const wavelengths = computed(() => dopplerWavelengths(sourceFrequency.value, soundSpeed, sourceVelocity.value));
const mach = computed(() => machNumber(sourceVelocity.value, soundSpeed));
const restWavelength = computed(() => soundSpeed / sourceFrequency.value);

const canvasConfig = computed(() => ({ width: 900, height: 400 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const measure = () => {
    if (!Number.isFinite(observedFrequency.value)) return;
    // microphone at the observer → frequency counter (0.1 Hz, 10 s gate)
    emit('measurement', {
        name: 'Kuzatilgan chastota',
        value: quantize(observedFrequency.value, WAVE_INSTRUMENTS.frequencyCounter.resolution),
        unit: 'Hz',
    });
};

const animate = (nowMs) => {
    clock = advanceClock(clock, nowMs, !props.isPaused);
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const arrow = (c, x, y, len, color) => {
    if (Math.abs(len) < 1) return;
    const dir = Math.sign(len);
    c.strokeStyle = color;
    c.fillStyle = color;
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(x, y);
    c.lineTo(x + len, y);
    c.stroke();
    c.beginPath();
    c.moveTo(x + len + dir * 8, y);
    c.lineTo(x + len, y - 6);
    c.lineTo(x + len, y + 6);
    c.closePath();
    c.fill();
};

const fmt = (v, d) => (Number.isFinite(v) ? v.toFixed(d) : '—');

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const t = clock.t;
    const roadY = h / 2;
    const vsPx = sourceVelocity.value * PX_PER_MPS;
    const vPx = soundSpeed * PX_PER_MPS;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Road — drawn in the source's rest frame, so the ground slides by at −vₛ
    c.fillStyle = '#334155';
    c.fillRect(0, roadY - 30, w, 60);
    c.strokeStyle = '#f59e0b';
    c.setLineDash([30, 20]);
    c.lineDashOffset = ((vsPx * t) % 50 + 50) % 50;
    c.beginPath();
    c.moveTo(0, roadY);
    c.lineTo(w, roadY);
    c.stroke();
    c.setLineDash([]);
    c.lineDashOffset = 0;

    // Wavefronts: crest emitted at tₙ has radius v(t − tₙ) and is centred where
    // the source was at tₙ, i.e. −vₛ(t − tₙ) behind its present position.
    const maxAge = 4.5;
    const fronts = dopplerWavefronts(t, { period: VIS_PERIOD, waveSpeed: vPx, sourceSpeed: vsPx, maxAge });
    c.lineWidth = 2;
    for (const f of fronts) {
        const alpha = Math.max(0, 1 - f.age / maxAge);
        c.strokeStyle = `rgba(59, 130, 246, ${alpha})`;
        c.beginPath();
        c.arc(SOURCE_X + f.centerOffset, roadY, f.radius, 0, Math.PI * 2);
        c.stroke();
    }

    // Source (car)
    c.fillStyle = '#ef4444';
    c.fillRect(SOURCE_X - 30, roadY - 15, 60, 30);
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('🔊', SOURCE_X, roadY + 4);
    arrow(c, SOURCE_X, roadY - 45, sourceVelocity.value * 0.8, '#ef4444');

    // Observer (positive vₒ = toward the source = to the left)
    c.fillStyle = '#22c55e';
    c.beginPath();
    c.arc(OBSERVER_X, roadY, 20, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = 'white';
    c.textAlign = 'center';
    c.fillText('👂', OBSERVER_X, roadY + 5);
    arrow(c, OBSERVER_X, roadY - 45, -observerVelocity.value * 0.8, '#22c55e');

    // Frequency visualization
    c.fillStyle = 'rgba(30, 41, 59, 0.9)';
    c.fillRect(w / 2 - 150, 20, 300, 80);

    c.fillStyle = '#ef4444';
    c.fillRect(w / 2 - 140, 40, 120, 20);
    c.fillStyle = 'white';
    c.font = '11px Inter';
    c.textAlign = 'left';
    c.fillText(`Manba: ${sourceFrequency.value} Hz`, w / 2 - 135, 55);

    const ratio = observedFrequency.value / sourceFrequency.value;
    const obsBarWidth = Number.isFinite(ratio) ? Math.min(ratio * 120, 280) : 0;
    const up = frequencyShift.value > 0.05;
    const down = frequencyShift.value < -0.05;
    c.fillStyle = up ? '#3b82f6' : down ? '#f59e0b' : '#64748b';
    c.fillRect(w / 2 - 140, 70, obsBarWidth, 20);
    c.fillStyle = 'white';
    c.fillText(`Kuzatilgan: ${fmt(observedFrequency.value, 1)} Hz`, w / 2 - 135, 85);

    // Info panel (left)
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 225, 130);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(`Manba tezligi: ${sourceVelocity.value} m/s`, 35, 45);
    c.fillText(`Kuzatuvchi tezligi: ${observerVelocity.value} m/s`, 35, 65);
    c.fillText(`Tovush tezligi: ${soundSpeed} m/s (20 °C)`, 35, 85);
    c.fillStyle = up ? '#3b82f6' : down ? '#f59e0b' : '#94a3b8';
    const shift = frequencyShift.value;
    c.fillText(`Δf = ${Number.isFinite(shift) && shift > 0 ? '+' : ''}${fmt(shift, 1)} Hz`, 35, 110);
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText(
        !Number.isFinite(shift) ? 'Tovushdan tez — formula yaroqsiz'
            : up ? '↑ Yuqoriroq ton' : down ? '↓ Pastroq ton' : '= Ton o\'zgarmaydi',
        35, 130);

    // Info panel (right): wavelengths in the medium
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 220, 20, 200, 110);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.fillText(`λ₀ = v/f = ${fmt(restWavelength.value * 100, 1)} cm`, w - 205, 45);
    c.fillText(`λ oldinda = ${fmt(wavelengths.value.front * 100, 1)} cm`, w - 205, 65);
    c.fillText(`λ orqada = ${fmt(wavelengths.value.behind * 100, 1)} cm`, w - 205, 85);
    c.fillStyle = '#94a3b8';
    c.fillText(`Mach: M = ${fmt(mach.value, 2)}`, w - 205, 110);

    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.textAlign = 'left';
    c.fillText('Sxematik: to\'lqin frontlari sekinlashtirilgan, λ nisbati aniq', 20, h - 12);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Manba tezligi: <strong>{{ sourceVelocity }} m/s</strong></label>
                <input type="range" v-model.number="sourceVelocity" :min="-50" :max="100" :step="10" />
            </div>
            <div class="control-group">
                <label>Kuzatuvchi tezligi: <strong>{{ observerVelocity }} m/s</strong></label>
                <input type="range" v-model.number="observerVelocity" :min="-50" :max="50" :step="10" />
            </div>
            <div class="control-group">
                <label>Manba chastotasi: <strong>{{ sourceFrequency }} Hz</strong></label>
                <input type="range" v-model.number="sourceFrequency" :min="200" :max="800" :step="20" />
            </div>
            <button @click="measure" class="btn">📏</button>
        </div>
        <div class="formula">f' = f × (v + vₒ) / (v − vₛ)</div>
        <div class="note">vₛ &gt; 0 — manba kuzatuvchiga yaqinlashadi; vₒ &gt; 0 — kuzatuvchi manbaga yaqinlashadi (manfiy — uzoqlashadi)</div>
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
    font-size: 0.75rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}

.formula {
    font-family: serif;
    font-size: 1.1rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-radius: 8px;
}

.note {
    font-size: 0.75rem;
    color: #64748b;
    text-align: center;
    max-width: 900px;
}
</style>
