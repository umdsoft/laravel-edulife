<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { bohrEnergyEV, bohrRadius, bohrSpeed, transitionPhoton, wavelengthToRGB } from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const currentLevel = ref(3);
const showTransitions = ref(true);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0); // elapsed time [s]
let lastTs = null;
const photon = ref(null);
const lastPhoton = ref(null);

// Orbits are drawn to scale: r ∝ n² (rₙ = a0·n²), 5.5 px per a0.
const PX_PER_A0 = 5.5;
const PHOTON_SPEED = 180; // px/s (visual)

const energyLevels = computed(() => {
    const levels = [];
    for (let n = 1; n <= 6; n++) {
        levels.push({
            n,
            energy: bohrEnergyEV(n), // Eₙ = −13.6/n² eV
            radiusM: bohrRadius(n), // rₙ = a0·n²
            speed: bohrSpeed(n), // vₙ = αc/n
            radius: PX_PER_A0 * n * n,
        });
    }
    return levels;
});

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animationId.value = requestAnimationFrame(animate); } };

const transition = (toLevel) => {
    const ph = transitionPhoton(currentLevel.value, toLevel);
    if (ph) {
        // Emission: photon leaves the atom; absorption: photon arrives and is absorbed.
        photon.value = { ...ph, t0: time.value };
        lastPhoton.value = ph;
    }
    currentLevel.value = toLevel;
};

const photonX = () => {
    if (!photon.value) return null;
    const age = time.value - photon.value.t0;
    if (photon.value.type === 'emission') return 200 + PHOTON_SPEED * age;
    return PHOTON_SPEED * age; // from the left edge (x = 0) towards the nucleus (x = 200)
};

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (!props.isPaused) time.value += dt;

    if (photon.value) {
        const x = photonX();
        if ((photon.value.type === 'emission' && x > 470) || (photon.value.type === 'absorption' && x >= 200)) {
            photon.value = null;
        }
    }

    draw();
    animationId.value = requestAnimationFrame(animate);
};

// "O'lchash": records the level energy and the last emitted/absorbed photon. These are
// model values (the Bohr model is a theory view, not an instrument): no scatter.
const round = (x, digits) => Math.round(x * 10 ** digits) / 10 ** digits;
const measure = () => {
    const lvl = energyLevels.value[currentLevel.value - 1];
    emit('measurement', { name: `Sath energiyasi E${currentLevel.value}`, value: round(lvl.energy, 4), unit: 'eV' });
    if (lastPhoton.value) {
        emit('measurement', { name: 'Foton energiyasi ΔE', value: round(lastPhoton.value.energyEV, 4), unit: 'eV' });
        emit('measurement', { name: "Foton to'lqin uzunligi λ", value: round(lastPhoton.value.wavelengthNm, 2), unit: 'nm' });
    }
};

const photonColor = (ph) => wavelengthToRGB(ph.wavelengthNm) ?? (ph.region === 'UV' ? '#c4b5fd' : '#fca5a5');
const regionLabel = (r) => ({ UV: 'UB', visible: "ko'rinadigan", IR: 'IQ' })[r] ?? '';

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerX = 200;
    const centerY = 250;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Title
    c.fillStyle = 'white';
    c.font = 'bold 16px Inter';
    c.textAlign = 'center';
    c.fillText('Vodorod atomi — Bor modeli', centerX, 30);

    // Draw orbits
    energyLevels.value.forEach((level, idx) => {
        const isOccupied = level.n === currentLevel.value;

        c.strokeStyle = isOccupied ? '#3b82f6' : '#334155';
        c.lineWidth = isOccupied ? 3 : 1;
        c.setLineDash(isOccupied ? [] : [5, 5]);
        c.beginPath();
        c.arc(centerX, centerY, level.radius, 0, Math.PI * 2);
        c.stroke();
        c.setLineDash([]);

        // Level label (above the top of each orbit; radii grow as n²)
        if (level.n >= 2) {
            c.fillStyle = isOccupied ? '#3b82f6' : '#64748b';
            c.font = '11px Inter';
            c.textAlign = 'center';
            c.fillText(`n=${level.n}`, centerX, centerY - level.radius - 3);
        }
    });

    // Nucleus (proton)
    c.beginPath();
    c.arc(centerX, centerY, 3, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();

    // Electron — angular speed ω ∝ vₙ/rₙ ∝ 1/n³ (Bohr), shown slowed down
    const lvl = energyLevels.value[currentLevel.value - 1];
    const electronAngle = time.value * 6 / lvl.n ** 3;
    const electronRadius = lvl.radius;
    const electronX = centerX + electronRadius * Math.cos(electronAngle);
    const electronY = centerY + electronRadius * Math.sin(electronAngle);

    c.beginPath();
    c.arc(electronX, electronY, 5, 0, Math.PI * 2);
    c.fillStyle = '#3b82f6';
    c.fill();

    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.textAlign = 'center';
    c.fillText('masshtab: rₙ = a₀n² (a₀ = 0.529 Å)', centerX, h - 20);

    // Photon
    if (photon.value) {
        const color = photonColor(photon.value);
        const px = photonX();
        const py = centerY;
        c.beginPath();
        c.arc(px, py, 8, 0, Math.PI * 2);
        c.fillStyle = color;
        c.fill();

        // Wave effect
        c.strokeStyle = color;
        c.lineWidth = 2;
        for (let i = 0; i < 3; i++) {
            const offset = (photon.value.type === 'emission' ? -1 : 1) * i * 15;
            c.beginPath();
            c.moveTo(px - offset, py - 5);
            c.quadraticCurveTo(px - offset + 7, py, px - offset, py + 5);
            c.stroke();
        }
        c.font = '10px Inter';
        c.fillStyle = color;
        c.fillText(`${photon.value.wavelengthNm.toFixed(1)} nm`, px, py - 14);
    }

    // Energy level diagram
    const { diagramX, diagramY, diagramH, y0, levelY, rowY } = diagramLayout();

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(diagramX - 20, diagramY - 30, 380, diagramH + 60);

    c.fillStyle = 'white';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Energiya sathlari', diagramX, diagramY - 10);

    // Draw energy levels: line height ∝ |Eₙ| (true scale); labels/buttons on
    // non-overlapping rows joined by leader lines.
    energyLevels.value.forEach((level) => {
        const y = levelY[level.n];
        const ry = rowY[level.n];
        const isOccupied = level.n === currentLevel.value;

        c.strokeStyle = isOccupied ? '#3b82f6' : '#475569';
        c.lineWidth = isOccupied ? 3 : 2;
        c.beginPath();
        c.moveTo(diagramX, y);
        c.lineTo(diagramX + 150, y);
        c.stroke();
        c.lineWidth = 1;
        c.beginPath();
        c.moveTo(diagramX + 150, y);
        c.lineTo(diagramX + 156, ry);
        c.stroke();

        c.fillStyle = isOccupied ? '#3b82f6' : '#94a3b8';
        c.font = '11px Inter';
        c.textAlign = 'left';
        c.fillText(`n = ${level.n}`, diagramX + 160, ry + 4);
        c.fillText(`E = ${level.energy.toFixed(2)} eV`, diagramX + 210, ry + 4);

        // Transition buttons
        if (level.n !== currentLevel.value) {
            c.fillStyle = '#1e293b';
            c.fillRect(diagramX + 300, ry - 10, 50, 20);
            c.fillStyle = level.n < currentLevel.value ? '#22c55e' : '#64748b';
            c.font = '10px Inter';
            // emission (down) / absorption (up), in Uzbek like the rest of the lab
            c.fillText(level.n < currentLevel.value ? '↓ Tushish' : '↑ Sakrash', diagramX + 304, ry + 4);
        }
    });

    // Ionisation limit E∞ = 0
    c.fillStyle = '#475569';
    c.font = '11px Inter';
    c.fillText('0 eV (ionizatsiya)', diagramX + 160, y0 - 4);
    c.strokeStyle = '#475569';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(diagramX, y0);
    c.lineTo(diagramX + 150, y0);
    c.stroke();
    c.setLineDash([]);
};

/** Diagram geometry: yₙ = y0 + |Eₙ|/13.6·H; label rows ≥ 22 px apart. */
function diagramLayout() {
    const diagramX = 500;
    const diagramY = 80;
    const diagramH = 350;
    const y0 = diagramY + 30;
    const scale = diagramH - 50;
    const E1 = Math.abs(bohrEnergyEV(1));
    const levelY = {};
    const rowY = {};
    energyLevels.value.forEach((l) => { levelY[l.n] = y0 + (Math.abs(l.energy) / E1) * scale; });
    let prev = y0 + 4;
    for (let n = 6; n >= 1; n--) {
        rowY[n] = Math.max(levelY[n], prev + 22);
        prev = rowY[n];
    }
    return { diagramX, diagramY, diagramH, y0, levelY, rowY };
}

const handleClick = (e) => {
    const rect = canvas.value.getBoundingClientRect();
    // Map CSS pixels to canvas pixels (the canvas may be scaled by CSS)
    const x = (e.clientX - rect.left) * (canvas.value.width / rect.width);
    const y = (e.clientY - rect.top) * (canvas.value.height / rect.height);

    const { diagramX, rowY } = diagramLayout();
    const hit = energyLevels.value.find((level) =>
        x >= diagramX + 300 && x <= diagramX + 350 && y >= rowY[level.n] - 10 && y <= rowY[level.n] + 10);
    if (hit && hit.n !== currentLevel.value) transition(hit.n);
};

onMounted(() => {
    initCanvas();
    if (canvas.value) canvas.value.addEventListener('click', handleClick);
});
onUnmounted(() => {
    if (animationId.value) cancelAnimationFrame(animationId.value);
    if (canvas.value) canvas.value.removeEventListener('click', handleClick);
});
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="info">
                <span>Joriy sath: <strong>n = {{ currentLevel }}</strong></span>
                <span>Energiya: <strong>{{ energyLevels[currentLevel - 1]?.energy.toFixed(2) }} eV</strong></span>
                <span>r = <strong>{{ (energyLevels[currentLevel - 1]?.radiusM * 1e10).toFixed(3) }} Å</strong></span>
                <span v-if="lastPhoton">
                    {{ lastPhoton.type === 'emission' ? 'Nurlangan' : 'Yutilgan' }} foton:
                    <strong>{{ lastPhoton.upper }}↔{{ lastPhoton.lower }}, ΔE = {{ lastPhoton.energyEV.toFixed(2) }} eV,
                        λ = {{ lastPhoton.wavelengthNm.toFixed(2) }} nm</strong>
                    ({{ lastPhoton.series }}, {{ regionLabel(lastPhoton.region) }})
                </span>
            </div>
            <p class="hint">Diagrammadagi tugmalarni bosib o'tishlarni ko'ring</p>
            <button @click="measure" class="btn">📏 O'lchash</button>
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
    cursor: pointer;
}

.controls {
    display: flex;
    gap: 2rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
    align-items: center;
}

.info {
    display: flex;
    gap: 2rem;
    color: #94a3b8;
}

.info strong {
    color: #3b82f6;
}

.hint {
    color: #64748b;
    font-size: 0.85rem;
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
