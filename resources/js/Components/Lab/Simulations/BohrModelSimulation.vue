<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const currentLevel = ref(3);
const showTransitions = ref(true);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);
const photon = ref(null);

const energyLevels = computed(() => {
    const levels = [];
    for (let n = 1; n <= 6; n++) {
        levels.push({
            n,
            energy: -13.6 / (n * n),
            radius: 30 + n * 25,
        });
    }
    return levels;
});

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const transition = (toLevel) => {
    if (toLevel < currentLevel.value) {
        // Emission
        const fromE = energyLevels.value[currentLevel.value - 1].energy;
        const toE = energyLevels.value[toLevel - 1].energy;
        photon.value = {
            energy: toE - fromE,
            x: 200,
            y: 250,
            vx: 3,
        };
    }
    currentLevel.value = toLevel;
};

const animate = () => {
    time.value += 0.02;

    if (photon.value) {
        photon.value.x += photon.value.vx;
        if (photon.value.x > canvasConfig.value.width) {
            photon.value = null;
        }
    }

    draw();
    animationId.value = requestAnimationFrame(animate);
};

const energyToColor = (energy) => {
    // Convert energy (eV) to approximate wavelength color
    const wl = 1240 / Math.abs(energy);
    if (wl < 400) return '#8b5cf6';
    if (wl < 450) return '#6366f1';
    if (wl < 490) return '#3b82f6';
    if (wl < 530) return '#22c55e';
    if (wl < 580) return '#eab308';
    if (wl < 640) return '#f97316';
    return '#ef4444';
};

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
    c.fillText('Vodorod Atomi - Bor Modeli', centerX, 30);

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

        // Level label
        c.fillStyle = isOccupied ? '#3b82f6' : '#64748b';
        c.font = '11px Inter';
        c.fillText(`n=${level.n}`, centerX + level.radius + 10, centerY - 5);
    });

    // Nucleus
    c.beginPath();
    c.arc(centerX, centerY, 15, 0, Math.PI * 2);
    c.fillStyle = '#ef4444';
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 10px Inter';
    c.fillText('+', centerX, centerY + 4);

    // Electron
    const electronAngle = time.value * 2;
    const electronRadius = energyLevels.value[currentLevel.value - 1].radius;
    const electronX = centerX + electronRadius * Math.cos(electronAngle);
    const electronY = centerY + electronRadius * Math.sin(electronAngle);

    c.beginPath();
    c.arc(electronX, electronY, 8, 0, Math.PI * 2);
    c.fillStyle = '#3b82f6';
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 10px Inter';
    c.fillText('-', electronX, electronY + 3);

    // Photon
    if (photon.value) {
        const color = energyToColor(photon.value.energy);
        c.beginPath();
        c.arc(photon.value.x, photon.value.y, 8, 0, Math.PI * 2);
        c.fillStyle = color;
        c.fill();

        // Wave effect
        c.strokeStyle = color;
        c.lineWidth = 2;
        for (let i = 0; i < 3; i++) {
            const offset = i * 15;
            c.beginPath();
            c.moveTo(photon.value.x + offset, photon.value.y - 5);
            c.quadraticCurveTo(photon.value.x + offset + 7, photon.value.y, photon.value.x + offset, photon.value.y + 5);
            c.stroke();
        }
    }

    // Energy level diagram
    const diagramX = 500;
    const diagramY = 80;
    const diagramH = 350;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(diagramX - 20, diagramY - 30, 380, diagramH + 60);

    c.fillStyle = 'white';
    c.font = 'bold 14px Inter';
    c.textAlign = 'left';
    c.fillText('Energiya sathlari', diagramX, diagramY - 10);

    // Draw energy levels
    energyLevels.value.forEach((level, idx) => {
        const y = diagramY + 30 + ((1 / level.n ** 2) * (diagramH - 50));
        const isOccupied = level.n === currentLevel.value;

        c.strokeStyle = isOccupied ? '#3b82f6' : '#475569';
        c.lineWidth = isOccupied ? 3 : 2;
        c.beginPath();
        c.moveTo(diagramX, y);
        c.lineTo(diagramX + 150, y);
        c.stroke();

        c.fillStyle = isOccupied ? '#3b82f6' : '#94a3b8';
        c.font = '11px Inter';
        c.fillText(`n = ${level.n}`, diagramX + 160, y + 4);
        c.fillText(`E = ${level.energy.toFixed(2)} eV`, diagramX + 220, y + 4);

        // Transition buttons
        if (level.n !== currentLevel.value) {
            c.fillStyle = '#1e293b';
            c.fillRect(diagramX + 300, y - 10, 50, 20);
            c.fillStyle = level.n < currentLevel.value ? '#22c55e' : '#64748b';
            c.font = '10px Inter';
            c.fillText(level.n < currentLevel.value ? '→ Go' : 'Jump', diagramX + 310, y + 4);
        }
    });

    // Ground state reference
    c.fillStyle = '#475569';
    c.font = '11px Inter';
    c.fillText('0 eV (ionizatsiya)', diagramX + 160, diagramY + 30);
    c.strokeStyle = '#475569';
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(diagramX, diagramY + 25);
    c.lineTo(diagramX + 150, diagramY + 25);
    c.stroke();
    c.setLineDash([]);
};

const handleClick = (e) => {
    const rect = canvas.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const diagramX = 500;
    const diagramY = 80;
    const diagramH = 350;

    energyLevels.value.forEach((level) => {
        const levelY = diagramY + 30 + ((1 / level.n ** 2) * (diagramH - 50));
        if (x >= diagramX + 300 && x <= diagramX + 350 && y >= levelY - 10 && y <= levelY + 10) {
            transition(level.n);
        }
    });
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
            </div>
            <p class="hint">Diagrammadagi tugmalarni bosib o'tishlarni ko'ring</p>
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
</style>
