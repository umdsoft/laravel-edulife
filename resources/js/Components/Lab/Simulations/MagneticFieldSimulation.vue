<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const magnetStrength = ref(50);
const showFieldLines = ref(true);

const canvas = ref(null);
const ctx = ref(null);
const animationId = ref(null);
const time = ref(0);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); animate(); } };

const animate = () => {
    time.value += 0.02;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    const centerX = w / 2;
    const centerY = h / 2;
    const magnetW = 200;
    const magnetH = 60;

    // Field lines
    if (showFieldLines.value) {
        const numLines = 12;
        for (let i = 0; i < numLines; i++) {
            const startY = centerY - magnetH / 2 + (magnetH / (numLines - 1)) * i;
            const offset = (time.value * 20) % 40;

            c.strokeStyle = `hsla(${200 + i * 10}, 70%, 60%, 0.6)`;
            c.lineWidth = 2;
            c.beginPath();

            // Right side field lines (N to S external)
            for (let x = centerX + magnetW / 2; x < w - 50; x += 5) {
                const progress = (x - centerX - magnetW / 2) / (w - 50 - centerX - magnetW / 2);
                const curve = Math.sin(progress * Math.PI) * (i - numLines / 2) * 8;
                const y = startY + curve;
                if (x === centerX + magnetW / 2) c.moveTo(x, y);
                else c.lineTo(x, y);
            }
            c.stroke();

            // Left side field lines
            c.beginPath();
            for (let x = centerX - magnetW / 2; x > 50; x -= 5) {
                const progress = (centerX - magnetW / 2 - x) / (centerX - magnetW / 2 - 50);
                const curve = Math.sin(progress * Math.PI) * (i - numLines / 2) * 8;
                const y = startY + curve;
                if (x === centerX - magnetW / 2) c.moveTo(x, y);
                else c.lineTo(x, y);
            }
            c.stroke();
        }

        // External field lines (looping around)
        for (let i = 0; i < 6; i++) {
            const angle = (i / 6) * Math.PI - Math.PI / 2;
            const radiusX = magnetW / 2 + 80 + i * 25;
            const radiusY = 60 + i * 30;

            c.strokeStyle = `hsla(${220 + i * 20}, 60%, 50%, 0.4)`;
            c.lineWidth = 1.5;
            c.beginPath();
            c.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI, false);
            c.stroke();
            c.beginPath();
            c.ellipse(centerX, centerY, radiusX, radiusY, 0, Math.PI, Math.PI * 2, false);
            c.stroke();
        }
    }

    // Magnet
    // North pole (red)
    c.fillStyle = '#ef4444';
    c.fillRect(centerX - magnetW / 2, centerY - magnetH / 2, magnetW / 2, magnetH);
    c.fillStyle = 'white';
    c.font = 'bold 24px Inter';
    c.textAlign = 'center';
    c.fillText('N', centerX - magnetW / 4, centerY + 8);

    // South pole (blue)
    c.fillStyle = '#3b82f6';
    c.fillRect(centerX, centerY - magnetH / 2, magnetW / 2, magnetH);
    c.fillStyle = 'white';
    c.fillText('S', centerX + magnetW / 4, centerY + 8);

    // Magnet border
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.strokeRect(centerX - magnetW / 2, centerY - magnetH / 2, magnetW, magnetH);

    // Compass needles animation
    const compasses = [
        { x: centerX - magnetW / 2 - 80, y: centerY },
        { x: centerX + magnetW / 2 + 80, y: centerY },
        { x: centerX, y: centerY - 120 },
        { x: centerX, y: centerY + 120 },
    ];

    compasses.forEach((comp, idx) => {
        const dx = comp.x - centerX;
        const dy = comp.y - centerY;
        const angle = Math.atan2(dy, dx) + (dx < 0 ? Math.PI : 0);

        c.save();
        c.translate(comp.x, comp.y);
        c.rotate(angle);

        // Compass circle
        c.beginPath();
        c.arc(0, 0, 20, 0, Math.PI * 2);
        c.fillStyle = '#1e293b';
        c.fill();
        c.strokeStyle = '#475569';
        c.lineWidth = 2;
        c.stroke();

        // Needle
        c.beginPath();
        c.moveTo(-12, 0);
        c.lineTo(12, 0);
        c.strokeStyle = '#ef4444';
        c.lineWidth = 3;
        c.stroke();

        // Arrow head
        c.beginPath();
        c.moveTo(12, 0);
        c.lineTo(6, -4);
        c.lineTo(6, 4);
        c.closePath();
        c.fillStyle = '#ef4444';
        c.fill();

        c.restore();
    });

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 200, 100);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Magnit kuchi: ${magnetStrength.value}%`, 35, 45);
    c.fillText('🔴 N - Shimoliy qutb', 35, 70);
    c.fillText('🔵 S - Janubiy qutb', 35, 90);
    c.fillStyle = '#94a3b8';
    c.fillText('Maydon chiziqlari: N → S', 35, 110);
};

watch([magnetStrength, showFieldLines], () => { draw(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Magnit kuchi: <strong>{{ magnetStrength }}%</strong></label>
                <input type="range" v-model.number="magnetStrength" :min="10" :max="100" :step="10" />
            </div>
            <label class="checkbox">
                <input type="checkbox" v-model="showFieldLines" />
                <span>Maydon chiziqlarini ko'rsatish</span>
            </label>
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
    min-width: 150px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
    cursor: pointer;
}

.checkbox input {
    width: 18px;
    height: 18px;
}
</style>
