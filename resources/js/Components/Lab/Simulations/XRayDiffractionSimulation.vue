<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { braggAngle, braggOrders, CRYSTAL_SPACINGS } from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const wavelength = ref(0.154); // nm (Cu Kα = 0.154 nm)
const crystalType = ref('nacl');

const canvas = ref(null);
const ctx = ref(null);

// Interplanar spacings d (nm): NaCl(200) 0.282, diamond(111) 0.206, Si(111) 0.314
const crystals = {
    nacl: { name: 'NaCl', plane: CRYSTAL_SPACINGS.nacl.plane, d: +(CRYSTAL_SPACINGS.nacl.d * 1e9).toFixed(3), color: '#3b82f6' },
    diamond: { name: 'Olmos', plane: CRYSTAL_SPACINGS.diamond.plane, d: +(CRYSTAL_SPACINGS.diamond.d * 1e9).toFixed(3), color: '#94a3b8' },
    silicon: { name: 'Kremniy', plane: CRYSTAL_SPACINGS.silicon.plane, d: +(CRYSTAL_SPACINGS.silicon.d * 1e9).toFixed(3), color: '#8b5cf6' },
};

const d = computed(() => crystals[crystalType.value].d);
const toDeg = (rad) => (rad === null ? null : (rad * 180) / Math.PI);
// nλ = 2d·sin θ — θ is the glancing angle to the lattice planes; the beam is deviated by 2θ.
const firstOrderAngle = computed(() => toDeg(braggAngle(1, wavelength.value * 1e-9, d.value * 1e-9)));
const secondOrderAngle = computed(() => toDeg(braggAngle(2, wavelength.value * 1e-9, d.value * 1e-9)));
const orders = computed(() => braggOrders(wavelength.value * 1e-9, d.value * 1e-9));

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // X-ray source
    c.fillStyle = '#22c55e';
    c.beginPath();
    c.arc(80, h / 2, 25, 0, Math.PI * 2);
    c.fill();
    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.textAlign = 'center';
    c.fillText('X-ray', 80, h / 2 + 4);

    // Incident beam
    c.strokeStyle = '#22c55e';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(105, h / 2);
    c.lineTo(350, h / 2);
    c.stroke();

    // Crystal lattice
    const crystalX = 350;
    const crystalY = h / 2 - 80;
    const crystalW = 200;
    const crystalH = 160;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(crystalX, crystalY, crystalW, crystalH);
    c.strokeStyle = crystals[crystalType.value].color;
    c.lineWidth = 2;
    c.strokeRect(crystalX, crystalY, crystalW, crystalH);

    // Lattice planes
    const planeSpacing = 25;
    c.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    c.lineWidth = 1;
    for (let i = 1; i < crystalH / planeSpacing; i++) {
        const y = crystalY + i * planeSpacing;
        c.beginPath();
        c.moveTo(crystalX, y);
        c.lineTo(crystalX + crystalW, y);
        c.stroke();
    }

    // Atoms
    for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 8; col++) {
            const x = crystalX + 15 + col * 24;
            const y = crystalY + 15 + row * planeSpacing;
            c.beginPath();
            c.arc(x, y, 6, 0, Math.PI * 2);
            c.fillStyle = (row + col) % 2 === 0 ? crystals[crystalType.value].color : '#f59e0b';
            c.fill();
        }
    }

    // d spacing marker
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(crystalX - 30, crystalY + planeSpacing);
    c.lineTo(crystalX - 30, crystalY + 2 * planeSpacing);
    c.stroke();
    c.fillStyle = '#f59e0b';
    c.font = '11px Inter';
    c.textAlign = 'right';
    c.fillText(`d = ${d.value} nm`, crystalX - 35, crystalY + 1.5 * planeSpacing);

    // Diffracted beams: order n leaves at deviation 2θₙ from the incident direction
    // (Debye–Scherrer geometry); θₙ itself is the glancing angle to the planes.
    const beamColors = ['#ef4444', '#3b82f6', '#22c55e', '#eab308'];
    orders.value.slice(0, 4).forEach((o, i) => {
        const dev = o.twoTheta;
        const cx0 = crystalX + crystalW / 2;
        c.strokeStyle = beamColors[i];
        c.lineWidth = i === 0 ? 3 : 2;
        c.beginPath();
        c.moveTo(cx0, h / 2);
        c.lineTo(cx0 + 200 * Math.cos(dev), h / 2 - 200 * Math.sin(dev));
        c.stroke();

        c.fillStyle = beamColors[i];
        c.font = '12px Inter';
        c.textAlign = 'center';
        c.fillText(`n=${o.n}, θ=${toDeg(o.theta).toFixed(1)}° (2θ=${toDeg(dev).toFixed(1)}°)`,
            cx0 + 160 * Math.cos(dev),
            h / 2 - 160 * Math.sin(dev) - 10);
    });

    // Transmitted beam
    c.strokeStyle = 'rgba(34, 197, 94, 0.5)';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(crystalX + crystalW, h / 2);
    c.lineTo(w - 50, h / 2);
    c.stroke();

    // Detector
    c.fillStyle = '#1e293b';
    c.fillRect(w - 50, 100, 30, h - 200);
    c.strokeStyle = '#475569';
    c.strokeRect(w - 50, 100, 30, h - 200);
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.save();
    c.translate(w - 35, h / 2);
    c.rotate(-Math.PI / 2);
    c.fillText('Detektor', 0, 0);
    c.restore();

    // Bragg's Law info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 270, 175);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Bregg qonuni:', 35, 45);

    c.fillStyle = '#94a3b8';
    c.font = '16px serif';
    c.fillText('nλ = 2d sin θ', 35, 75);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText(`λ = ${wavelength.value} nm`, 35, 102);
    c.fillText(`d = ${d.value} nm (${crystals[crystalType.value].name} ${crystals[crystalType.value].plane})`, 35, 120);
    c.fillStyle = '#ef4444';
    c.fillText(`θ₁ = ${firstOrderAngle.value !== null ? firstOrderAngle.value.toFixed(2) + '°' : '— (λ > 2d)'}`, 35, 140);
    c.fillStyle = '#3b82f6';
    c.fillText(`θ₂ = ${secondOrderAngle.value !== null ? secondOrderAngle.value.toFixed(2) + '°' : '— (2λ > 2d)'}`, 35, 158);
    c.fillStyle = '#94a3b8';
    c.fillText(`Mavjud tartiblar: n ≤ 2d/λ → n_max = ${orders.value.length}`, 35, 176);
    c.font = '10px Inter';
    c.fillText('θ — sirpanish burchagi; nur 2θ ga og\'adi', 35, 190);
};

watch([wavelength, crystalType], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Kristall:</label>
                <select v-model="crystalType">
                    <option v-for="(cr, key) in crystals" :key="key" :value="key">{{ cr.name }}</option>
                </select>
            </div>
            <div class="control-group">
                <label>X-ray λ: <strong>{{ wavelength }} nm</strong></label>
                <input type="range" v-model.number="wavelength" :min="0.05" :max="0.3" :step="0.01" />
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
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: flex-end;
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

.control-group select {
    padding: 0.5rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
}
</style>
