<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    config: Object,
    currentTask: Object,
    isPaused: Boolean,
    state: Object,
});

const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

// Circuit parameters
const voltage = ref(props.config?.battery?.voltage?.default || 6);
const resistance = ref(props.config?.resistor?.resistance?.default || 100);
const isSwitchOn = ref(false);

// Computed values
const current = computed(() => {
    if (!isSwitchOn.value) return 0;
    return voltage.value / resistance.value;
});

const power = computed(() => {
    return voltage.value * current.value;
});

// Canvas
const canvas = ref(null);
const ctx = ref(null);
const canvasConfig = computed(() => props.config?.canvas || { width: 900, height: 600 });

// Electron animation
const electrons = ref([]);
const animationId = ref(null);

// Methods
const initCanvas = () => {
    if (!canvas.value) return;
    ctx.value = canvas.value.getContext('2d');
    initElectrons();
    draw();
};

const initElectrons = () => {
    electrons.value = [];
    const numElectrons = 20;

    // Circuit path points
    for (let i = 0; i < numElectrons; i++) {
        electrons.value.push({
            position: i / numElectrons,
            speed: 0,
        });
    }
};

const toggleSwitch = () => {
    isSwitchOn.value = !isSwitchOn.value;

    if (isSwitchOn.value) {
        animateElectrons();
    } else {
        if (animationId.value) cancelAnimationFrame(animationId.value);
    }
};

const animateElectrons = () => {
    if (!isSwitchOn.value || props.isPaused) return;

    // Speed based on current
    const speed = current.value * 0.002;

    electrons.value.forEach(e => {
        e.position += speed;
        if (e.position >= 1) e.position = 0;
    });

    draw();
    animationId.value = requestAnimationFrame(animateElectrons);
};

const measure = () => {
    emit('measurement', {
        name: `Tok kuchi (U=${voltage.value}V, R=${resistance.value}Ω)`,
        value: (current.value * 1000).toFixed(1),
        unit: 'mA',
    });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    // Clear
    c.fillStyle = '#1e293b';
    c.fillRect(0, 0, w, h);

    // Draw circuit
    drawCircuit(c, w, h);

    // Draw electrons if switch is on
    if (isSwitchOn.value) {
        drawElectrons(c, w, h);
    }

    // Draw measurements
    drawMeasurements(c);
};

const drawCircuit = (c, w, h) => {
    const cx = w / 2;
    const cy = h / 2;
    const size = 200;

    // Circuit wire style
    c.strokeStyle = isSwitchOn.value ? '#22c55e' : '#64748b';
    c.lineWidth = 4;

    // Top wire
    c.beginPath();
    c.moveTo(cx - size, cy - size);
    c.lineTo(cx + size, cy - size);
    c.stroke();

    // Right wire
    c.beginPath();
    c.moveTo(cx + size, cy - size);
    c.lineTo(cx + size, cy + size);
    c.stroke();

    // Bottom wire
    c.beginPath();
    c.moveTo(cx + size, cy + size);
    c.lineTo(cx - size, cy + size);
    c.stroke();

    // Left wire (with switch gap)
    c.beginPath();
    c.moveTo(cx - size, cy + size);
    c.lineTo(cx - size, cy + 50);
    c.stroke();

    c.beginPath();
    c.moveTo(cx - size, cy - 50);
    c.lineTo(cx - size, cy - size);
    c.stroke();

    // Draw components
    drawBattery(c, cx, cy - size);
    drawResistor(c, cx + size, cy);
    drawSwitch(c, cx - size, cy);
    drawAmmeter(c, cx - 80, cy + size);
    drawVoltmeter(c, cx + 80, cy);
};

const drawBattery = (c, x, y) => {
    // Battery body
    c.fillStyle = '#334155';
    c.fillRect(x - 40, y - 20, 80, 40);
    c.strokeStyle = '#64748b';
    c.strokeRect(x - 40, y - 20, 80, 40);

    // + terminal
    c.fillStyle = '#ef4444';
    c.fillRect(x + 40, y - 10, 10, 20);

    // - terminal
    c.fillStyle = '#3b82f6';
    c.fillRect(x - 50, y - 10, 10, 20);

    // Voltage label
    c.fillStyle = '#ffffff';
    c.font = 'bold 14px Inter';
    c.textAlign = 'center';
    c.fillText(`${voltage.value}V`, x, y + 5);
};

const drawResistor = (c, x, y) => {
    // Resistor zigzag
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(x, y - 40);

    const zigzagWidth = 15;
    const numZigs = 6;
    const zigHeight = 60 / numZigs;

    for (let i = 0; i < numZigs; i++) {
        const dir = i % 2 === 0 ? 1 : -1;
        c.lineTo(x + dir * zigzagWidth, y - 40 + (i + 0.5) * zigHeight);
    }
    c.lineTo(x, y + 40);
    c.stroke();

    // Resistance label
    c.fillStyle = '#f59e0b';
    c.font = 'bold 12px Inter';
    c.textAlign = 'center';
    c.fillText(`${resistance.value}Ω`, x + 35, y + 5);
};

const drawSwitch = (c, x, y) => {
    // Switch base
    c.fillStyle = '#334155';
    c.beginPath();
    c.arc(x, y - 50, 6, 0, Math.PI * 2);
    c.arc(x, y + 50, 6, 0, Math.PI * 2);
    c.fill();

    // Switch lever
    c.strokeStyle = isSwitchOn.value ? '#22c55e' : '#64748b';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(x, y - 50);

    if (isSwitchOn.value) {
        c.lineTo(x, y + 50);
    } else {
        c.lineTo(x + 30, y + 30);
    }
    c.stroke();

    // Label
    c.fillStyle = '#94a3b8';
    c.font = '12px Inter';
    c.textAlign = 'left';
    c.fillText(isSwitchOn.value ? 'YOQILGAN' : 'O\'CHIRILGAN', x + 15, y);
};

const drawAmmeter = (c, x, y) => {
    // Ammeter circle
    c.fillStyle = '#1e3a5f';
    c.beginPath();
    c.arc(x, y, 30, 0, Math.PI * 2);
    c.fill();
    c.strokeStyle = '#3b82f6';
    c.lineWidth = 2;
    c.stroke();

    // A symbol
    c.fillStyle = '#ffffff';
    c.font = 'bold 16px serif';
    c.textAlign = 'center';
    c.fillText('A', x, y - 5);

    // Current value
    c.font = 'bold 11px Inter';
    c.fillStyle = '#10b981';
    c.fillText(`${(current.value * 1000).toFixed(1)} mA`, x, y + 12);
};

const drawVoltmeter = (c, x, y) => {
    // Voltmeter circle
    c.fillStyle = '#3f1e1e';
    c.beginPath();
    c.arc(x, y, 30, 0, Math.PI * 2);
    c.fill();
    c.strokeStyle = '#ef4444';
    c.lineWidth = 2;
    c.stroke();

    // V symbol
    c.fillStyle = '#ffffff';
    c.font = 'bold 16px serif';
    c.textAlign = 'center';
    c.fillText('V', x, y - 5);

    // Voltage value
    c.font = 'bold 11px Inter';
    c.fillStyle = '#ef4444';
    c.fillText(`${voltage.value.toFixed(1)} V`, x, y + 12);
};

const drawElectrons = (c, w, h) => {
    const cx = w / 2;
    const cy = h / 2;
    const size = 200;

    // Circuit path as array of points
    const path = [
        { x: cx - size, y: cy - size }, // top-left
        { x: cx + size, y: cy - size }, // top-right
        { x: cx + size, y: cy + size }, // bottom-right
        { x: cx - size, y: cy + size }, // bottom-left
    ];

    const perimeter = size * 8;

    electrons.value.forEach(e => {
        const dist = e.position * perimeter;
        const point = getPointOnPath(path, size, dist);

        // Draw electron
        c.beginPath();
        c.arc(point.x, point.y, 4, 0, Math.PI * 2);
        c.fillStyle = '#fbbf24';
        c.fill();
    });
};

const getPointOnPath = (path, size, dist) => {
    const segment = Math.floor(dist / (size * 2)) % 4;
    const localDist = dist % (size * 2);
    const t = localDist / (size * 2);

    const p1 = path[segment];
    const p2 = path[(segment + 1) % 4];

    return {
        x: p1.x + (p2.x - p1.x) * t,
        y: p1.y + (p2.y - p1.y) * t,
    };
};

const drawMeasurements = (c) => {
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 180, 140);
    c.strokeStyle = '#334155';
    c.strokeRect(20, 20, 180, 140);

    c.textAlign = 'left';
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';

    c.fillText(`Kuchlanish (U): ${voltage.value} V`, 35, 50);
    c.fillText(`Qarshilik (R): ${resistance.value} Ω`, 35, 75);

    c.fillStyle = '#10b981';
    c.fillText(`Tok kuchi (I): ${(current.value * 1000).toFixed(2)} mA`, 35, 100);

    c.fillStyle = '#f59e0b';
    c.fillText(`Quvvat (P): ${(power.value * 1000).toFixed(2)} mW`, 35, 125);

    c.fillStyle = '#94a3b8';
    c.font = '10px Inter';
    c.fillText('I = U/R (Om qonuni)', 35, 150);
};

// Watchers
watch(() => props.isPaused, (paused) => {
    if (!paused && isSwitchOn.value) animateElectrons();
});

watch([voltage, resistance], () => {
    draw();
    emit('update:state', {
        voltage: voltage.value,
        resistance: resistance.value,
        current: current.value,
    });
});

onMounted(() => initCanvas());
</script>

<template>
    <div class="ohm-simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="simulation-canvas" />

        <div class="controls-panel">
            <div class="control-group">
                <label class="control-label">
                    Kuchlanish (U): <strong>{{ voltage }} V</strong>
                </label>
                <input type="range" v-model.number="voltage" :min="config?.battery?.voltage?.min || 1"
                    :max="config?.battery?.voltage?.max || 12" :step="config?.battery?.voltage?.step || 0.5"
                    class="range-slider voltage" />
            </div>

            <div class="control-group">
                <label class="control-label">
                    Qarshilik (R): <strong>{{ resistance }} Ω</strong>
                </label>
                <input type="range" v-model.number="resistance" :min="config?.resistor?.resistance?.min || 10"
                    :max="config?.resistor?.resistance?.max || 500" :step="config?.resistor?.resistance?.step || 10"
                    class="range-slider resistance" />
            </div>

            <div class="button-group">
                <button @click="toggleSwitch" class="btn" :class="isSwitchOn ? 'btn-off' : 'btn-on'">
                    {{ isSwitchOn ? '⏹️ O\'chirish' : '▶️ Yoqish' }}
                </button>
                <button @click="measure" :disabled="!isSwitchOn" class="btn btn-measure">
                    📏 O'lchash
                </button>
            </div>
        </div>

        <!-- Ohm's Law Display -->
        <div class="ohm-display">
            <div class="ohm-formula">
                I = U / R = {{ voltage }} / {{ resistance }} = <span class="result">{{ (current * 1000).toFixed(2) }}
                    mA</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ohm-simulation {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.simulation-canvas {
    border-radius: 12px;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
}

.controls-panel {
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
}

.control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 180px;
}

.control-label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-label strong {
    color: white;
}

.range-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    appearance: none;
    background: #334155;
}

.range-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.range-slider.voltage::-webkit-slider-thumb {
    background: #ef4444;
}

.range-slider.resistance::-webkit-slider-thumb {
    background: #f59e0b;
}

.button-group {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn-on {
    background: #10b981;
    color: white;
}

.btn-off {
    background: #ef4444;
    color: white;
}

.btn-measure {
    background: #3b82f6;
    color: white;
}

.ohm-display {
    padding: 1rem 1.5rem;
    background: #1e293b;
    border-radius: 12px;
}

.ohm-formula {
    font-family: 'Times New Roman', serif;
    font-size: 1.125rem;
    color: #94a3b8;
}

.ohm-formula .result {
    color: #10b981;
    font-weight: bold;
}
</style>
