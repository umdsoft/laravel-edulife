<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { balmerLines, EMISSION_LINES_NM, wavelengthToRGB } from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const element = ref('hydrogen');

const canvas = ref(null);
const ctx = ref(null);

// Line colours are computed from the wavelength.
const withColor = (lines) => lines.map(l => ({ ...l, color: wavelengthToRGB(l.wavelength) ?? '#ffffff' }));

const elements = {
    hydrogen: {
        name: 'Vodorod',
        // Balmer series from the Rydberg formula 1/λ = R(1/2² − 1/n²), n = 3…6
        lines: withColor(balmerLines(6).map(l => ({ wavelength: Math.round(l.wavelengthNm * 10) / 10, name: l.name }))),
    },
    helium: { name: 'Geliy', lines: withColor(EMISSION_LINES_NM.helium) },
    sodium: { name: 'Natriy', lines: withColor(EMISSION_LINES_NM.sodium) },
    mercury: { name: 'Simob', lines: withColor(EMISSION_LINES_NM.mercury) },
};

const canvasConfig = computed(() => ({ width: 900, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    const spectrumY = 100;
    const spectrumH = 150;

    // Continuous spectrum background (400–700 nm, colours from the wavelength)
    const grad = c.createLinearGradient(100, 0, w - 100, 0);
    for (let wl = 400; wl <= 700; wl += 10) {
        grad.addColorStop((wl - 400) / 300, wavelengthToRGB(wl));
    }
    c.fillStyle = grad;
    c.fillRect(100, spectrumY, w - 200, spectrumH);

    // Wavelength scale
    c.fillStyle = '#64748b';
    c.font = '11px Inter';
    c.textAlign = 'center';
    for (let wl = 400; wl <= 700; wl += 50) {
        const x = 100 + ((wl - 400) / 300) * (w - 200);
        c.fillText(`${wl}`, x, spectrumY + spectrumH + 20);
    }
    c.fillText('nm', w - 60, spectrumY + spectrumH + 20);

    // Emission spectrum
    const emissionY = spectrumY + spectrumH + 60;
    c.fillStyle = '#0a0a0a';
    c.fillRect(100, emissionY, w - 200, 100);
    c.strokeStyle = '#334155';
    c.strokeRect(100, emissionY, w - 200, 100);

    // Draw emission lines
    const currentElement = elements[element.value];
    let prevLabelX = -Infinity;
    let stagger = 0;
    [...currentElement.lines].sort((a, b) => a.wavelength - b.wavelength).forEach(line => {
        const x = 100 + ((line.wavelength - 400) / 300) * (w - 200);
        // Stagger labels of lines closer than 45 px (e.g. Na D1/D2 doublet, 0.6 nm apart)
        stagger = x - prevLabelX < 45 ? stagger + 1 : 0;
        prevLabelX = x;

        // Glow effect
        const glowGrad = c.createRadialGradient(x, emissionY + 50, 0, x, emissionY + 50, 20);
        glowGrad.addColorStop(0, line.color);
        glowGrad.addColorStop(1, 'transparent');
        c.fillStyle = glowGrad;
        c.fillRect(x - 20, emissionY, 40, 100);

        // Line
        c.strokeStyle = line.color;
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(x, emissionY);
        c.lineTo(x, emissionY + 100);
        c.stroke();

        // Label
        c.fillStyle = line.color;
        c.font = '10px Inter';
        c.textAlign = 'center';
        c.fillText(`${line.wavelength.toFixed(1)}nm`, x, emissionY + 115 + stagger * 12);
        if (line.name) {
            c.fillText(line.name, x + (stagger ? 14 * stagger : 0), emissionY - 10);
        }
    });

    // Gas tube visualization (above the spectra so it does not hide any line)
    const tubeY = 25;
    c.fillStyle = '#1e293b';
    c.fillRect(w / 2 - 150, tubeY, 300, 60);
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.strokeRect(w / 2 - 150, tubeY, 300, 60);

    // Glowing gas
    const tubeGrad = c.createLinearGradient(w / 2 - 140, 0, w / 2 + 140, 0);
    const nLines = currentElement.lines.length;
    currentElement.lines.forEach((line, i) => {
        tubeGrad.addColorStop(nLines > 1 ? i / (nLines - 1) : 0, line.color + '80');
    });
    c.fillStyle = tubeGrad;
    c.fillRect(w / 2 - 140, tubeY + 5, 280, 50);

    // Electrodes
    c.fillStyle = '#64748b';
    c.fillRect(w / 2 - 170, tubeY + 15, 25, 30);
    c.fillRect(w / 2 + 145, tubeY + 15, 25, 30);

    // Info
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 180, 60);
    c.fillStyle = '#ffffff';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText(`Element: ${currentElement.name}`, 35, 45);
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.fillText(`Chiziqlar: ${currentElement.lines.length}`, 35, 65);
};

watch(element, () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Element:</label>
                <select v-model="element">
                    <option v-for="(el, key) in elements" :key="key" :value="key">{{ el.name }}</option>
                </select>
            </div>
            <div class="info">
                <span>Har bir element o'ziga xos spektral chiziqlar beradi</span>
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
    min-width: 120px;
}

.info {
    color: #64748b;
    font-size: 0.85rem;
}
</style>
