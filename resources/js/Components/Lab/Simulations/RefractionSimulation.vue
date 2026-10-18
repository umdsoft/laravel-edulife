<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { toRad, toDeg, snellRefractionAngle, criticalAngle, fresnelReflectance } from '../physics/optics.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const angle1 = ref(45); // degrees, measured from the normal
const n1 = ref(1.0); // air
const n2 = ref(1.5); // glass

const canvas = ref(null);
const ctx = ref(null);

// Range sliders with step 0.1 can yield 1.2000000000000002 — show 2 decimals.
const fmtN = (n) => Number(n).toFixed(2);

const angle1Rad = computed(() => toRad(angle1.value));
// Snell: n1 sinθ1 = n2 sinθ2 (null on total internal reflection)
const angle2Rad = computed(() => snellRefractionAngle(n1.value, n2.value, angle1Rad.value));
const angle2 = computed(() => (angle2Rad.value === null ? null : toDeg(angle2Rad.value)));
const isTotalReflection = computed(() => angle2Rad.value === null);
const criticalAngleDeg = computed(() => {
    const c = criticalAngle(n1.value, n2.value);
    return c === null ? null : toDeg(c);
});
// Fresnel (unpolarised) share of the light reflected at the boundary
const reflectance = computed(() => fresnelReflectance(n1.value, n2.value, angle1Rad.value).R);

const canvasConfig = computed(() => ({ width: 800, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const measure = () => {
    if (isTotalReflection.value) {
        // Backend requires a numeric value: under TIR report the reflection angle (= θ1).
        emit('measurement', {
            name: `To'liq ichki qaytish: qaytish burchagi (n₁=${fmtN(n1.value)}, n₂=${fmtN(n2.value)})`,
            value: angle1.value.toFixed(2),
            unit: '°',
        });
        return;
    }
    emit('measurement', { name: `Sinish burchagi (n₁=${fmtN(n1.value)}, n₂=${fmtN(n2.value)})`, value: angle2.value.toFixed(2), unit: '°' });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    const centerX = w / 2;
    const centerY = h / 2;

    // Background - two media
    c.fillStyle = '#1e3a5f'; // Top (air)
    c.fillRect(0, 0, w, centerY);
    c.fillStyle = '#1e293b'; // Bottom (glass)
    c.fillRect(0, centerY, w, centerY);

    // Interface line
    c.strokeStyle = '#64748b';
    c.setLineDash([10, 5]);
    c.lineWidth = 2;
    c.beginPath();
    c.moveTo(0, centerY);
    c.lineTo(w, centerY);
    c.stroke();
    c.setLineDash([]);

    // Normal line
    c.strokeStyle = '#475569';
    c.lineWidth = 1;
    c.setLineDash([5, 5]);
    c.beginPath();
    c.moveTo(centerX, centerY - 200);
    c.lineTo(centerX, centerY + 200);
    c.stroke();
    c.setLineDash([]);

    // Incident ray (from the upper left, angle θ1 from the normal)
    const rayLen = 200;
    const incidentAngleRad = angle1Rad.value;
    const startX = centerX - rayLen * Math.sin(incidentAngleRad);
    const startY = centerY - rayLen * Math.cos(incidentAngleRad);

    c.strokeStyle = '#ef4444';
    c.lineWidth = 4;
    c.beginPath();
    c.moveTo(startX, startY);
    c.lineTo(centerX, centerY);
    c.stroke();

    // Arrow
    c.fillStyle = '#ef4444';
    c.beginPath();
    const arrowX = centerX - 30 * Math.sin(incidentAngleRad);
    const arrowY = centerY - 30 * Math.cos(incidentAngleRad);
    c.arc(arrowX, arrowY, 6, 0, Math.PI * 2);
    c.fill();

    // Reflected ray (law of reflection), brightness from the Fresnel reflectance
    const reflectedX = centerX + rayLen * Math.sin(incidentAngleRad);
    const reflectedY = centerY - rayLen * Math.cos(incidentAngleRad);

    if (isTotalReflection.value) {
        // Total internal reflection: all the light is reflected
        c.strokeStyle = '#f59e0b';
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(centerX, centerY);
        c.lineTo(reflectedX, reflectedY);
        c.stroke();

        c.fillStyle = '#f59e0b';
        c.font = '14px Inter';
        c.textAlign = 'center';
        c.fillText('To\'liq ichki qaytish!', centerX, centerY + 120);
    } else {
        // Partial reflection
        c.strokeStyle = `rgba(245, 158, 11, ${Math.min(1, 0.15 + reflectance.value)})`;
        c.lineWidth = 1 + 3 * reflectance.value;
        c.beginPath();
        c.moveTo(centerX, centerY);
        c.lineTo(reflectedX, reflectedY);
        c.stroke();

        const refractedAngleRad = angle2Rad.value;
        const endX = centerX + rayLen * Math.sin(refractedAngleRad);
        const endY = centerY + rayLen * Math.cos(refractedAngleRad);

        c.strokeStyle = '#3b82f6';
        c.lineWidth = 4;
        c.beginPath();
        c.moveTo(centerX, centerY);
        c.lineTo(endX, endY);
        c.stroke();
    }

    // Angle arcs (both measured from the normal). Canvas angles grow clockwise;
    // −π/2 is "up", so the incident ray (upper LEFT) spans [−π/2 − θ1, −π/2].
    c.strokeStyle = '#ef4444';
    c.lineWidth = 2;
    c.beginPath();
    c.arc(centerX, centerY, 40, -Math.PI / 2 - incidentAngleRad, -Math.PI / 2, false);
    c.stroke();

    if (!isTotalReflection.value) {
        // refracted ray (lower RIGHT) spans [π/2 − θ2, π/2]
        c.strokeStyle = '#3b82f6';
        c.beginPath();
        c.arc(centerX, centerY, 50, Math.PI / 2 - angle2Rad.value, Math.PI / 2, false);
        c.stroke();
    }

    // Labels
    c.fillStyle = 'white';
    c.font = '14px Inter';
    c.textAlign = 'center';
    c.fillText(`n₁ = ${fmtN(n1.value)}`, 80, 50);
    c.fillText(`n₂ = ${fmtN(n2.value)}`, 80, centerY + 50);

    // Angle labels
    c.fillStyle = '#ef4444';
    c.fillText(`θ₁ = ${angle1.value}°`, centerX - 80, centerY - 60);
    if (!isTotalReflection.value) {
        c.fillStyle = '#3b82f6';
        c.fillText(`θ₂ = ${angle2.value.toFixed(1)}°`, centerX + 80, centerY + 80);
    }

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 250, 20, 230, 170);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tushish burchagi (θ₁): ${angle1.value}°`, w - 235, 45);
    c.fillText(`n₁ = ${fmtN(n1.value)} (yuqori muhit)`, w - 235, 65);
    c.fillText(`n₂ = ${fmtN(n2.value)} (pastki muhit)`, w - 235, 85);

    if (!isTotalReflection.value) {
        c.fillStyle = '#3b82f6';
        c.fillText(`Sinish burchagi: ${angle2.value.toFixed(2)}°`, w - 235, 110);
    } else {
        c.fillStyle = '#f59e0b';
        c.fillText('To\'liq ichki qaytish!', w - 235, 110);
    }

    if (criticalAngleDeg.value !== null) {
        c.fillStyle = '#f59e0b';
        c.fillText(`Kritik burchak: ${criticalAngleDeg.value.toFixed(1)}°`, w - 235, 135);
    }

    c.fillStyle = '#94a3b8';
    c.fillText(`Qaytgan ulush (Frenel): ${(reflectance.value * 100).toFixed(1)}%`, w - 235, 155);

    // Snell's Law
    c.fillStyle = '#94a3b8';
    c.font = '14px serif';
    c.fillText('n₁ sin θ₁ = n₂ sin θ₂', w - 235, 180);
};

watch([angle1, n1, n2], () => { draw(); });
onMounted(() => initCanvas());
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tushish burchagi: <strong>{{ angle1 }}°</strong></label>
                <input type="range" v-model.number="angle1" :min="0" :max="85" :step="1" />
            </div>
            <div class="control-group">
                <label>n₁ (yuqori): <strong>{{ fmtN(n1) }}</strong></label>
                <input type="range" v-model.number="n1" :min="1" :max="2" :step="0.1" />
            </div>
            <div class="control-group">
                <label>n₂ (pastki): <strong>{{ fmtN(n2) }}</strong></label>
                <input type="range" v-model.number="n2" :min="1" :max="2.5" :step="0.1" />
            </div>
            <button @click="measure" class="btn measure">📏 O'lchash</button>
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
    min-width: 140px;
}

.control-group label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.control-group strong {
    color: white;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    background: #3b82f6;
    color: white;
}
</style>
