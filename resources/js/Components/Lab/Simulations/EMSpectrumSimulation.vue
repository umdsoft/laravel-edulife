<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    EM_BANDS,
    bandRanges,
    emFrequency,
    photonEnergyEV,
    formatSI,
} from '../physics/waves.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const selectedWave = ref('visible');

const canvas = ref(null);
const ctx = ref(null);

// Display data for each band; all numbers (λ, f, E) come from EM_BANDS via
// f = c/λ and E = hc/λ. Bar widths are schematic (not to logarithmic scale).
const UI = {
    radio: { color: '#475569', x: 0, w: 100, apps: 'Radio aloqa, TV, Wi-Fi' },
    microwave: { color: '#64748b', x: 100, w: 80, apps: 'Mikroto\'lqinli pech, radar, uyali aloqa' },
    infrared: { color: '#ef4444', x: 180, w: 80, apps: 'Issiqlik kameralari, masofadan boshqarish pulti' },
    visible: { color: 'rainbow', x: 260, w: 120, apps: 'Ko\'rish, fotosintez, optik tolalar' },
    ultraviolet: { color: '#8b5cf6', x: 380, w: 80, apps: 'Sterilizatsiya, D vitamini sintezi' },
    xray: { color: '#06b6d4', x: 460, w: 80, apps: 'Tibbiy tasvirlash, kristallografiya' },
    gamma: { color: '#22c55e', x: 540, w: 80, apps: 'Saraton davolash, yadro fizikasi' },
};

const spectrum = EM_BANDS.map((b) => {
    const r = bandRanges(b);
    return {
        ...b,
        ...UI[b.id],
        ...r,
        wavelength: b.openMax
            ? `> ${formatSI(b.lambdaMin, 'm')}`
            : b.openMin
                ? `< ${formatSI(b.lambdaMax, 'm')}`
                : `${formatSI(b.lambdaMin, 'm')} – ${formatSI(b.lambdaMax, 'm')}`,
        freq: b.openMax
            ? `< ${formatSI(r.fMax, 'Hz')}`
            : b.openMin
                ? `> ${formatSI(r.fMin, 'Hz')}`
                : `${formatSI(r.fMin, 'Hz')} – ${formatSI(r.fMax, 'Hz')}`,
        energy: b.openMax
            ? `< ${formatSI(r.eMaxEV, 'eV')}`
            : b.openMin
                ? `> ${formatSI(r.eMinEV, 'eV')}`
                : `${formatSI(r.eMinEV, 'eV')} – ${formatSI(r.eMaxEV, 'eV')}`,
    };
});

const selectedInfo = computed(() => spectrum.find((s) => s.id === selectedWave.value) ?? null);
// Representative wavelength: geometric mean of the band (for open bands, the edge value)
const representative = computed(() => {
    const s = selectedInfo.value;
    if (!s) return null;
    const lambda = s.openMax ? s.lambdaMin : s.openMin ? s.lambdaMax : s.lambdaCenter;
    return { lambda, f: emFrequency(lambda), e: photonEnergyEV(lambda) };
});

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    const r = representative.value;
    if (!r || !Number.isFinite(r.e)) return;
    emit('measurement', {
        name: `Foton energiyasi (λ = ${formatSI(r.lambda, 'm')})`,
        value: Number(r.e.toPrecision(4)),
        unit: 'eV',
    });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    c.fillStyle = '#ffffff';
    c.font = 'bold 18px Inter';
    c.textAlign = 'center';
    c.fillText('Elektromagnit Spektr', w / 2, 40);

    // Spectrum bar
    const barY = 100;
    const barH = 80;
    const startX = 100;
    const scale = 1.1;

    spectrum.forEach((wave) => {
        const x = startX + wave.x * scale;
        const ww = wave.w * scale;

        if (wave.color === 'rainbow') {
            // long λ (red) on the left → short λ (violet) on the right
            const rainbowGrad = c.createLinearGradient(x, 0, x + ww, 0);
            rainbowGrad.addColorStop(0, '#ef4444');
            rainbowGrad.addColorStop(0.17, '#f97316');
            rainbowGrad.addColorStop(0.33, '#eab308');
            rainbowGrad.addColorStop(0.5, '#22c55e');
            rainbowGrad.addColorStop(0.67, '#06b6d4');
            rainbowGrad.addColorStop(0.83, '#3b82f6');
            rainbowGrad.addColorStop(1, '#8b5cf6');
            c.fillStyle = rainbowGrad;
        } else {
            c.fillStyle = wave.color;
        }
        c.fillRect(x, barY, ww, barH);

        if (selectedWave.value === wave.id) {
            c.strokeStyle = '#f59e0b';
            c.lineWidth = 3;
            c.strokeRect(x, barY, ww, barH);
        }
    });

    // Boundary ticks: λ below, f = c/λ above (computed)
    c.font = '9px Inter';
    c.textAlign = 'center';
    for (let i = 1; i < spectrum.length; i++) {
        const x = startX + spectrum[i].x * scale;
        const lam = spectrum[i].lambdaMax;
        c.strokeStyle = 'rgba(255,255,255,0.8)';
        c.lineWidth = 1;
        c.beginPath();
        c.moveTo(x, barY);
        c.lineTo(x, barY + barH);
        c.stroke();
        c.fillStyle = 'rgba(0,0,0,0.55)';
        c.fillRect(x - 24, barY + 2, 48, 12);
        c.fillRect(x - 24, barY + barH - 14, 48, 12);
        c.fillStyle = 'white';
        c.fillText(formatSI(emFrequency(lam), 'Hz'), x, barY + 11);
        c.fillText(formatSI(lam, 'm'), x, barY + barH - 5);
    }

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('← Uzun to\'lqin uzunligi', startX + 50, barY - 10);
    c.fillText('Qisqa to\'lqin uzunligi →', startX + 620, barY - 10);
    c.fillText('← Kichik chastota', startX + 50, barY + barH + 25);
    c.fillText('Katta chastota →', startX + 620, barY + barH + 25);
    c.fillText('← Kam energiya', startX + 50, barY + barH + 45);
    c.fillText('Ko\'p energiya →', startX + 620, barY + barH + 45);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText('Masshtab shartli (logarifmik emas)', w / 2, barY - 10);

    spectrum.forEach((wave) => {
        const x = startX + wave.x * scale + wave.w * scale / 2;
        c.fillStyle = 'white';
        c.font = '10px Inter';
        c.save();
        c.translate(x, barY + barH + 70);
        c.rotate(-Math.PI / 4);
        c.textAlign = 'right';
        c.fillText(wave.name, 0, 0);
        c.restore();
    });

    // Detailed info for selected wave
    const infoY = 300;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(100, infoY, w - 200, 180);

    const s = selectedInfo.value;
    const rep = representative.value;
    if (s && rep) {
        c.fillStyle = '#f59e0b';
        c.font = 'bold 16px Inter';
        c.textAlign = 'left';
        c.fillText(s.name, 120, infoY + 30);

        c.fillStyle = 'white';
        c.font = '13px Inter';
        c.fillText(`To'lqin uzunligi: ${s.wavelength}`, 120, infoY + 55);
        c.fillText(`Chastota: ${s.freq}`, 120, infoY + 75);
        c.fillText(`Foton energiyasi: ${s.energy}`, 120, infoY + 95);
        c.fillStyle = '#cbd5e1';
        c.font = '12px Inter';
        c.fillText(
            `Masalan λ = ${formatSI(rep.lambda, 'm')} → f = ${formatSI(rep.f, 'Hz')}, E = ${formatSI(rep.e, 'eV')}`,
            120, infoY + 117);

        c.fillStyle = '#94a3b8';
        c.fillText(`Qo'llanilishi: ${s.apps}`, 120, infoY + 138);

        // Schematic wave: more cycles for shorter-wavelength bands
        const idx = spectrum.indexOf(s);
        const cycles = 1.5 + idx;
        const waveX = 560;
        const waveY = infoY + 60;
        c.strokeStyle = s.color === 'rainbow' ? '#f59e0b' : s.color;
        c.lineWidth = 2;
        c.beginPath();
        for (let x = 0; x <= 200; x++) {
            const y = waveY + Math.sin((2 * Math.PI * cycles * x) / 200) * 30;
            if (x === 0) c.moveTo(waveX + x, y);
            else c.lineTo(waveX + x, y);
        }
        c.stroke();
    }

    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.textAlign = 'center';
    c.fillText('c = λ·f  (c ≈ 3×10⁸ m/s)      E = h·f = h·c/λ  (h = 6.63×10⁻³⁴ J·s)', w / 2, infoY + 168);
};

watch(selectedWave, () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>To'lqin turi:</label>
                <select v-model="selectedWave">
                    <option v-for="wave in spectrum" :key="wave.id" :value="wave.id">{{ wave.name }}</option>
                </select>
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
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group select {
    padding: 0.5rem 1rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
    min-width: 200px;
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
