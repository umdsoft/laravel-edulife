<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const heatPower = ref(200);
const isHeating = ref(true);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const temperature = ref(-20);
const phase = ref('solid'); // solid, melting, liquid, boiling, gas
const heatAdded = ref(0);
const time = ref(0);

const meltingPoint = 0;
const boilingPoint = 100;
const meltingHeat = 334000; // J/kg for water
const boilingHeat = 2260000;
const mass = 0.5;

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const start = () => {
    if (isRunning.value) return;
    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    temperature.value = -20;
    phase.value = 'solid';
    heatAdded.value = 0;
    time.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value || props.isPaused) return;
    const dt = 1 / 60;
    time.value += dt;

    const dQ = heatPower.value * dt * (isHeating.value ? 1 : -1);
    heatAdded.value += dQ;

    // Phase transitions
    if (phase.value === 'solid' && temperature.value < meltingPoint) {
        temperature.value += dQ / (mass * 2100); // Ice specific heat
        if (temperature.value >= meltingPoint) {
            temperature.value = meltingPoint;
            phase.value = 'melting';
        }
    } else if (phase.value === 'melting') {
        // Absorbing latent heat
        if (heatAdded.value > meltingHeat * mass) {
            phase.value = 'liquid';
        }
    } else if (phase.value === 'liquid' && temperature.value < boilingPoint) {
        temperature.value += dQ / (mass * 4186);
        if (temperature.value >= boilingPoint) {
            temperature.value = boilingPoint;
            phase.value = 'boiling';
        }
    } else if (phase.value === 'boiling') {
        if (heatAdded.value > (meltingHeat + boilingHeat) * mass) {
            phase.value = 'gas';
        }
    } else if (phase.value === 'gas') {
        temperature.value += dQ / (mass * 2000);
        if (temperature.value > 150) {
            isRunning.value = false;
        }
    }

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

    // Container
    c.strokeStyle = '#64748b';
    c.lineWidth = 4;
    c.strokeRect(100, 150, 200, 200);

    // Content based on phase
    if (phase.value === 'solid') {
        c.fillStyle = '#93c5fd';
        c.fillRect(102, 250, 196, 98);
        // Ice crystals
        for (let i = 0; i < 8; i++) {
            c.fillStyle = 'rgba(255,255,255,0.5)';
            c.beginPath();
            c.arc(120 + i * 22, 290, 8, 0, Math.PI * 2);
            c.fill();
        }
    } else if (phase.value === 'melting') {
        c.fillStyle = '#93c5fd';
        c.fillRect(102, 280, 196, 68);
        c.fillStyle = '#3b82f6';
        c.fillRect(102, 250, 196, 30);
    } else if (phase.value === 'liquid' || phase.value === 'boiling') {
        c.fillStyle = '#3b82f6';
        c.fillRect(102, 250, 196, 98);

        if (phase.value === 'boiling' || temperature.value > 80) {
            // Bubbles
            for (let i = 0; i < 10; i++) {
                c.beginPath();
                c.arc(120 + Math.random() * 160, 260 + Math.random() * 80, 5 + Math.random() * 8, 0, Math.PI * 2);
                c.fillStyle = 'rgba(255,255,255,0.4)';
                c.fill();
            }
        }
    } else if (phase.value === 'gas') {
        // Steam
        for (let i = 0; i < 15; i++) {
            const x = 120 + Math.random() * 160;
            const y = 200 + Math.sin(time.value + i) * 50;
            c.beginPath();
            c.arc(x, y, 10 + Math.random() * 15, 0, Math.PI * 2);
            c.fillStyle = 'rgba(200,200,200,0.3)';
            c.fill();
        }
    }

    // Heat source
    if (isRunning.value && isHeating.value) {
        c.fillStyle = '#ef4444';
        c.fillRect(90, 355, 220, 15);
        for (let i = 0; i < 6; i++) {
            c.beginPath();
            c.moveTo(110 + i * 35, 355);
            c.lineTo(110 + i * 35 - 8, 335 - Math.random() * 15);
            c.lineTo(110 + i * 35 + 8, 335 - Math.random() * 15);
            c.fillStyle = '#f97316';
            c.fill();
        }
    }

    // Temperature graph
    const graphX = 400;
    const graphW = 350;
    const graphH = 300;

    c.fillStyle = 'rgba(30, 41, 59, 0.8)';
    c.fillRect(graphX, 75, graphW, graphH);

    // Graph axes
    c.strokeStyle = '#475569';
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(graphX + 50, graphH + 50);
    c.lineTo(graphX + 50, 100);
    c.lineTo(graphX + graphW - 20, 100);
    c.stroke();

    // Phase markers
    const phases = [
        { temp: -20, y: 350, label: 'Muz' },
        { temp: 0, y: 270, label: 'Suyuqlanish' },
        { temp: 100, y: 170, label: 'Qaynash' },
        { temp: 120, y: 120, label: 'Bug\'' },
    ];

    c.fillStyle = '#94a3b8';
    c.font = '10px Inter';
    c.textAlign = 'right';
    phases.forEach(p => {
        c.fillText(`${p.temp}°C`, graphX + 45, p.y);
        c.setLineDash([3, 3]);
        c.beginPath();
        c.moveTo(graphX + 50, p.y);
        c.lineTo(graphX + graphW - 20, p.y);
        c.stroke();
        c.setLineDash([]);
    });

    // Current state
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 180, 120);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    const phaseNames = { solid: 'Qattiq (Muz)', melting: 'Suyuqlanish', liquid: 'Suyuq (Suv)', boiling: 'Qaynash', gas: 'Gaz (Bug\')' };
    c.fillText(`Holat: ${phaseNames[phase.value]}`, 35, 45);
    c.fillStyle = temperature.value < 0 ? '#3b82f6' : '#ef4444';
    c.fillText(`T = ${temperature.value.toFixed(1)} °C`, 35, 70);
    c.fillStyle = '#f59e0b';
    c.fillText(`Q = ${(heatAdded.value / 1000).toFixed(1)} kJ`, 35, 95);
    c.fillStyle = '#64748b';
    c.fillText(`Vaqt: ${time.value.toFixed(1)} s`, 35, 120);
};

watch(() => props.isPaused, (p) => { if (!p && isRunning.value) animate(); });
onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Quvvat: <strong>{{ heatPower }} W</strong></label>
                <input type="range" v-model.number="heatPower" :min="100" :max="500" :step="50" :disabled="isRunning" />
            </div>
            <div class="buttons">
                <button @click="start" :disabled="isRunning" class="btn start">🔥 Isitish</button>
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
    gap: 1.5rem;
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
    background: #ef4444;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}
</style>
