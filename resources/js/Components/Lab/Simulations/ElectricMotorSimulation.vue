<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { dcMotorState, motorKinematics, omegaToRpm, DC_MOTOR_DEFAULTS, toFixedSafe } from '../physics/electricity.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const current = ref(2);
const magnetStrength = ref(50); // % of B_max (DC_MOTOR_DEFAULTS.bMax = 0.5 T)

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(true);
const animationId = ref(null);

// Physical model (see physics/electricity.js): N-turn coil in uniform B with a
// split-ring commutator and a viscous load b.  F = NBIL, τ̄ = (2/π)NBIA,
// ωss = τ̄/b, back-EMF ε̄ = (2/π)NBAω, spin-up J·dω/dt = τ̄ − bω (τm = J/b).
const motor = computed(() => dcMotorState(current.value, magnetStrength.value / 100));

// Rotor angle/speed are the exact solution of the spin-up ODE evaluated at the
// simulated time since the last parameter change (frame-rate independent).
const time = ref(0); // simulated time [s]
const segment = ref({ t0: 0, theta0: 0, omega0: 0 });
let lastTs = null;
const kin = computed(() => motorKinematics(
    segment.value.theta0, segment.value.omega0, motor.value.omega, DC_MOTOR_DEFAULTS.spinUpTime,
    time.value - segment.value.t0,
));
const rotorAngle = computed(() => kin.value.theta);
const omega = computed(() => kin.value.omega);
const rotationSpeed = computed(() => omega.value); // rad/s (actual, including spin-up)

const canvasConfig = computed(() => ({ width: 800, height: 450 }));

const initCanvas = () => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        animationId.value = requestAnimationFrame(animate);
    }
};

const animate = (ts) => {
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    if (isRunning.value && !props.isPaused) time.value += dt;
    draw();
    animationId.value = requestAnimationFrame(animate);
};

// New I or B: continue from the present angle and speed toward the new ωss.
watch([current, magnetStrength], (_, [oldI, oldStrength]) => {
    // Evaluate the state reached so far with the OLD parameters, then rebase on it.
    const seg = segment.value;
    const oldOmegaSS = dcMotorState(oldI, oldStrength / 100).omega;
    const k = motorKinematics(seg.theta0, seg.omega0, oldOmegaSS, DC_MOTOR_DEFAULTS.spinUpTime, time.value - seg.t0);
    segment.value = { t0: time.value, theta0: k.theta % (2 * Math.PI), omega0: k.omega };
    emit('update:state', {
        ...(props.state || {}),
        current: current.value,
        magnetStrength: magnetStrength.value,
        B: motor.value.B,
        steadyRpm: motor.value.rpm,
        avgTorque: motor.value.avgTorque,
    });
});

const measure = () => {
    emit('measurement', {
        name: `Aylanish tezligi (I=${current.value} A, B=${toFixedSafe(motor.value.B, 2)} T)`,
        value: toFixedSafe(omegaToRpm(omega.value), 1),
        unit: 'RPM',
    });
};

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;
    const centerX = 350;
    const centerY = 225;

    c.fillStyle = '#0f172a';
    c.fillRect(0, 0, w, h);

    // Magnets (stator)
    // N pole (top)
    c.fillStyle = '#ef4444';
    c.beginPath();
    c.arc(centerX, centerY - 110, 40, Math.PI, 0);
    c.fill();
    c.fillStyle = 'white';
    c.font = 'bold 20px Inter';
    c.textAlign = 'center';
    c.fillText('N', centerX, centerY - 95);

    // S pole (bottom)
    c.fillStyle = '#3b82f6';
    c.beginPath();
    c.arc(centerX, centerY + 110, 40, 0, Math.PI);
    c.fill();
    c.fillStyle = 'white';
    c.fillText('S', centerX, centerY + 125);

    // Magnetic field lines
    c.strokeStyle = 'rgba(239, 68, 68, 0.3)';
    c.lineWidth = 1;
    for (let i = 0; i < 5; i++) {
        const x = centerX - 30 + i * 15;
        c.beginPath();
        c.moveTo(x, centerY - 70);
        c.lineTo(x, centerY + 70);
        c.stroke();
    }

    // Rotor (coil)
    c.save();
    c.translate(centerX, centerY);
    c.rotate(rotorAngle.value);

    // Coil
    c.strokeStyle = '#f59e0b';
    c.lineWidth = 6;
    c.strokeRect(-60, -20, 120, 40);

    // Current direction indicators
    const currentPhase = Math.sin(rotorAngle.value);
    if (currentPhase > 0) {
        c.fillStyle = '#22c55e';
        c.font = '16px Inter';
        c.fillText('⊙', -50, 5);
        c.fillText('⊗', 50, 5);
    } else {
        c.fillStyle = '#22c55e';
        c.font = '16px Inter';
        c.fillText('⊗', -50, 5);
        c.fillText('⊙', 50, 5);
    }

    c.restore();

    // Axis
    c.fillStyle = '#475569';
    c.beginPath();
    c.arc(centerX, centerY, 10, 0, Math.PI * 2);
    c.fill();

    // Commutator
    c.fillStyle = '#f59e0b';
    c.save();
    c.translate(centerX, centerY);
    c.rotate(rotorAngle.value);
    c.fillRect(-15, 60, 10, 20);
    c.fillRect(5, 60, 10, 20);
    c.restore();

    // Brushes
    c.fillStyle = '#64748b';
    c.fillRect(centerX - 25, centerY + 85, 15, 25);
    c.fillRect(centerX + 10, centerY + 85, 15, 25);

    // Power source
    c.strokeStyle = '#64748b';
    c.lineWidth = 3;
    c.beginPath();
    c.moveTo(centerX - 17, centerY + 110);
    c.lineTo(centerX - 17, centerY + 140);
    c.lineTo(centerX - 60, centerY + 140);
    c.moveTo(centerX + 17, centerY + 110);
    c.lineTo(centerX + 17, centerY + 140);
    c.lineTo(centerX + 60, centerY + 140);
    c.stroke();

    c.fillStyle = '#22c55e';
    c.fillRect(centerX - 80, centerY + 130, 40, 25);
    c.fillStyle = 'white';
    c.font = '10px Inter';
    c.fillText('DC', centerX - 60, centerY + 147);

    // Ampère forces F = N·B·I·L on the two coil sides. B is vertical (N → S), the
    // side currents are ⟂ to the screen, so F is always HORIZONTAL in the lab frame
    // (it does not rotate with the coil): ⊙ side → +x, ⊗ side → −x (F = I L × B).
    // With the commutator this gives a torque of constant sign, τ = NBIA|sin θ|.
    const sinA = Math.sin(rotorAngle.value);
    const cosA = Math.cos(rotorAngle.value);
    const leftIsOut = sinA > 0; // same rule as the ⊙/⊗ markers above
    const arrowLen = Math.max(12, Math.min(80, 12 * motor.value.sideForce));
    const drawForce = (lx, outOfScreen) => {
        const px = centerX + lx * cosA;
        const py = centerY + lx * sinA;
        const dir = outOfScreen ? 1 : -1;
        const tip = px + dir * arrowLen;
        c.strokeStyle = '#22c55e';
        c.fillStyle = '#22c55e';
        c.lineWidth = 3;
        c.beginPath();
        c.moveTo(px, py);
        c.lineTo(tip, py);
        c.stroke();
        c.beginPath();
        c.moveTo(tip + dir * 6, py);
        c.lineTo(tip - dir * 6, py - 7);
        c.lineTo(tip - dir * 6, py + 7);
        c.closePath();
        c.fill();
    };
    if (Math.abs(sinA) > 1e-3) {
        drawForce(-50, leftIsOut);
        drawForce(50, !leftIsOut);
    }

    // Info panel
    const m = motor.value;
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(w - 240, 20, 225, 210);
    c.fillStyle = '#ffffff';
    c.font = '12px Inter';
    c.textAlign = 'left';

    c.fillText(`Tok kuchi: ${current.value} A`, w - 225, 45);
    c.fillText(`Magnit kuchi: ${magnetStrength.value}% (B = ${toFixedSafe(m.B, 2)} T)`, w - 225, 65);
    c.fillStyle = '#f59e0b';
    c.fillText(`Aylanish tezligi: ${toFixedSafe(omegaToRpm(rotationSpeed.value), 0)} RPM`, w - 225, 90);
    c.fillStyle = '#94a3b8';
    c.fillText(`(barqaror: ${toFixedSafe(m.rpm, 0)} RPM)`, w - 225, 108);
    c.fillStyle = '#22c55e';
    c.fillText(`F = NBIL = ${toFixedSafe(m.sideForce, 2)} N`, w - 225, 130);
    c.fillText(`τ̄ = (2/π)NBIA = ${toFixedSafe(m.avgTorque * 1000, 1)} mN·m`, w - 225, 150);
    c.fillText(`Teskari EYK ε̄ = ${toFixedSafe(m.backEmf * 1000, 0)} mV`, w - 225, 170);

    c.fillStyle = '#94a3b8';
    c.font = '11px Inter';
    c.fillText(`N=${DC_MOTOR_DEFAULTS.turns}, L=${DC_MOTOR_DEFAULTS.sideLength * 100} sm, A=${toFixedSafe(m.area * 1e4, 0)} sm²`, w - 225, 195);
    c.fillText('F = BIL (Amper kuchi), τ = F×r', w - 225, 215);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="control-group">
                <label>Tok: <strong>{{ current }} A</strong></label>
                <input type="range" v-model.number="current" :min="0.5" :max="5" :step="0.5" />
            </div>
            <div class="control-group">
                <label>Magnit kuchi: <strong>{{ magnetStrength }}%</strong></label>
                <input type="range" v-model.number="magnetStrength" :min="20" :max="100" :step="10" />
            </div>
            <button @click="isRunning = !isRunning" class="btn">
                {{ isRunning ? '⏸️ Pauza' : '▶️ Davom' }}
            </button>
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
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-weight: 600;
}
</style>
