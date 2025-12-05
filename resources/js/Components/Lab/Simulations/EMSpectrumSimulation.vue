<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const selectedWave = ref('visible');

const canvas = ref(null);
const ctx = ref(null);

const spectrum = [
    { name: 'Radio to\'lqinlari', freq: '3 kHz - 300 GHz', wavelength: '1mm - 100km', color: '#475569', x: 0, w: 100 },
    { name: 'Mikro to\'lqinlar', freq: '300 MHz - 300 GHz', wavelength: '1mm - 1m', color: '#64748b', x: 100, w: 80 },
    { name: 'Infraqizil', freq: '300 GHz - 400 THz', wavelength: '700nm - 1mm', color: '#ef4444', x: 180, w: 80 },
    { name: 'Ko\'rinadigan yorug\'lik', freq: '400 - 800 THz', wavelength: '400 - 700nm', color: 'rainbow', x: 260, w: 120 },
    { name: 'Ultrabinafsha', freq: '800 THz - 30 PHz', wavelength: '10 - 400nm', color: '#8b5cf6', x: 380, w: 80 },
    { name: 'Rentgen nurlari', freq: '30 PHz - 30 EHz', wavelength: '0.01 - 10nm', color: '#06b6d4', x: 460, w: 80 },
    { name: 'Gamma nurlari', freq: '> 30 EHz', wavelength: '< 0.01nm', color: '#22c55e', x: 540, w: 80 },
];

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Title
    c.fillStyle = '#ffffff';
    c.font = 'bold 18px Inter';
    c.textAlign = 'center';
    c.fillText('Elektromagnit Spektr', w / 2, 40);

    // Spectrum bar
    const barY = 100;
    const barH = 80;
    const startX = 100;
    const scale = 1.1;

    spectrum.forEach((wave, i) => {
        const x = startX + wave.x * scale;
        const ww = wave.w * scale;

        if (wave.color === 'rainbow') {
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

        // Highlight selected
        if ((selectedWave.value === 'visible' && wave.color === 'rainbow') ||
            selectedWave.value === wave.name) {
            c.strokeStyle = '#f59e0b';
            c.lineWidth = 3;
            c.strokeRect(x, barY, ww, barH);
        }
    });

    // Wavelength scale
    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.textAlign = 'center';
    c.fillText('← Uzun to\'lqin uzunligi', startX + 50, barY - 10);
    c.fillText('Qisqa to\'lqin uzunligi →', startX + 620, barY - 10);

    // Frequency scale
    c.fillText('← Kichik chastota', startX + 50, barY + barH + 25);
    c.fillText('Katta chastota →', startX + 620, barY + barH + 25);

    // Energy scale
    c.fillText('← Kam energiya', startX + 50, barY + barH + 45);
    c.fillText('Ko\'p energiya →', startX + 620, barY + barH + 45);

    // Labels
    spectrum.forEach((wave, i) => {
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
    c.fillRect(100, infoY, w - 200, 170);

    let selectedInfo;
    if (selectedWave.value === 'visible') {
        selectedInfo = spectrum.find(s => s.color === 'rainbow');
    } else {
        selectedInfo = spectrum.find(s => s.name === selectedWave.value);
    }

    if (selectedInfo) {
        c.fillStyle = '#f59e0b';
        c.font = 'bold 16px Inter';
        c.textAlign = 'left';
        c.fillText(selectedInfo.name, 120, infoY + 35);

        c.fillStyle = 'white';
        c.font = '13px Inter';
        c.fillText(`Chastota: ${selectedInfo.freq}`, 120, infoY + 65);
        c.fillText(`To'lqin uzunligi: ${selectedInfo.wavelength}`, 120, infoY + 90);

        // Applications
        c.fillStyle = '#94a3b8';
        c.font = '12px Inter';
        let apps = '';
        switch (selectedInfo.name) {
            case 'Radio to\'lqinlari': apps = 'Qo\'llanilishi: Radio aloqa, TV, Wi-Fi'; break;
            case 'Mikro to\'lqinlar': apps = 'Qo\'llanilishi: Mikroto\'lqinli pech, radar, uyali aloqa'; break;
            case 'Infraqizil': apps = 'Qo\'llanilishi: Issiqlik kameralari, masofadan boshqarish pulti'; break;
            case 'Ko\'rinadigan yorug\'lik': apps = 'Qo\'llanilishi: Ko\'rish, fotosintez, optik tolalar'; break;
            case 'Ultrabinafsha': apps = 'Qo\'llanilishi: Sterilizatsiya, D vitamini sintezi'; break;
            case 'Rentgen nurlari': apps = 'Qo\'llanilishi: Tibbiy tasvirlash, kristallografiya'; break;
            case 'Gamma nurlari': apps = 'Qo\'llanilishi: Saraton davolash, yadro fizikasi'; break;
        }
        c.fillText(apps, 120, infoY + 120);

        // Wave visualization
        const waveX = 500;
        const waveY = infoY + 80;
        const waveLen = selectedInfo.x === 0 ? 100 : 200 - selectedInfo.x / 4;

        c.strokeStyle = selectedInfo.color === 'rainbow' ? '#f59e0b' : selectedInfo.color;
        c.lineWidth = 2;
        c.beginPath();
        for (let x = 0; x < 200; x++) {
            const y = waveY + Math.sin(x / waveLen * Math.PI * 4) * 30;
            if (x === 0) c.moveTo(waveX + x, y);
            else c.lineTo(waveX + x, y);
        }
        c.stroke();
    }

    // Formula
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.textAlign = 'center';
    c.fillText('c = λ × f    (c = 3×10⁸ m/s)', w / 2, infoY + 155);
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
                    <option v-for="wave in spectrum" :key="wave.name" :value="wave.name">{{ wave.name }}</option>
                </select>
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
    min-width: 200px;
}
</style>
