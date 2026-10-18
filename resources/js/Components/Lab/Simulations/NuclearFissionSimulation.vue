<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    u235FissionQ, fissionFragmentKinetics, ATOMIC_MASS_U, TYPICAL_FISSION_ENERGY_MEV, fissionEnergyForMass,
} from '../physics/atomic.js';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const neutronEnergy = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

// ── Physics ─────────────────────────────────────────────────────────────────
// ²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n : Q = Δm·c² ≈ 173 MeV prompt (≈ 200 MeV in total).
const Q = u235FissionQ();
const KIN = fissionFragmentKinetics({
    qMeV: Q.energyMeV, massA: ATOMIC_MASS_U.Ba141, massB: ATOMIC_MASS_U.Kr92, neutrons: 3, neutronKineticMeV: 2,
});
const ENERGY_PER_GRAM_J = fissionEnergyForMass(1e-3);

// On-screen speeds keep the physical ratios of the fast neutrons and fragments
// (equal and opposite momenta ⇒ v_Ba/v_Kr = m_Kr/m_Ba). Thermal incoming neutron and
// γ-rays are not to scale (γ moves at c, the incoming neutron is ~10⁴× slower).
const PX_PER_BETA = 220 / KIN.betaNeutron;
const V_FAST_N = KIN.betaNeutron * PX_PER_BETA; // px/s
const V_BA = KIN.betaA * PX_PER_BETA;
const V_KR = KIN.betaB * PX_PER_BETA;
const V_IN = 120;
const V_GAMMA = 600;
const GAMMA_LIFE = 0.5; // s
const U_POS = { x: 400, y: 250 };
const N_START = { x: 100, y: 250 };
const CONTACT = 40; // px, neutron–nucleus contact distance

// Analytic timeline: every particle is x(t) = x0 + v·(t − t0); nothing is integrated per frame.
const time = ref(0);
let lastTs = null;
const event = ref(null); // random directions of this run, sampled once at start
const fissionTime = (N_START.x < U_POS.x) ? (U_POS.x - CONTACT - N_START.x) / V_IN : Infinity;

const fissionCount = computed(() => (event.value && time.value >= fissionTime ? 1 : 0));
const energyReleased = computed(() => fissionCount.value * TYPICAL_FISSION_ENERGY_MEV);

const particles = computed(() => {
    if (!event.value) return [];
    const t = time.value;
    const list = [];
    if (t < fissionTime) {
        list.push({ type: 'neutron', x: N_START.x + V_IN * t, y: N_START.y, active: true });
        list.push({ type: 'uranium', x: U_POS.x, y: U_POS.y, active: true });
        return list;
    }
    const dt = t - fissionTime;
    const e = event.value;
    const cos = Math.cos(e.fragAngle);
    const sin = Math.sin(e.fragAngle);
    list.push({ type: 'barium', x: U_POS.x - 10 * cos - V_BA * cos * dt, y: U_POS.y - 10 * sin - V_BA * sin * dt, active: true });
    list.push({ type: 'krypton', x: U_POS.x + 10 * cos + V_KR * cos * dt, y: U_POS.y + 10 * sin + V_KR * sin * dt, active: true });
    e.neutronAngles.forEach((a) => list.push({
        type: 'neutron', x: U_POS.x + V_FAST_N * Math.cos(a) * dt, y: U_POS.y + V_FAST_N * Math.sin(a) * dt, active: true,
    }));
    if (dt < GAMMA_LIFE) {
        e.gammaAngles.forEach((a) => list.push({
            type: 'gamma', x: U_POS.x + V_GAMMA * Math.cos(a) * dt, y: U_POS.y + V_GAMMA * Math.sin(a) * dt,
            life: 30 * (1 - dt / GAMMA_LIFE), active: true,
        }));
    }
    return list.filter(p => p.x > -50 && p.x < 950 && p.y > -50 && p.y < 550);
});

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const startFission = () => {
    if (isRunning.value) return;
    event.value = {
        // Fragments fly apart back-to-back along a random axis
        fragAngle: Math.atan2(1, 2) + (Math.random() - 0.5) * 0.6,
        neutronAngles: [0, 1, 2].map(i => (i / 3) * Math.PI * 2 + Math.random() * 0.5),
        gammaAngles: [0, 1, 2, 3, 4].map(() => Math.random() * Math.PI * 2),
    };
    time.value = 0;
    lastTs = null;
    isRunning.value = true;
    animationId.value = requestAnimationFrame(animate);
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    event.value = null;
    time.value = 0;
    lastTs = null;
    draw();
};

const animate = (ts) => {
    if (!isRunning.value || props.isPaused) { lastTs = null; return; }
    const dt = lastTs === null ? 0 : Math.min(0.1, (ts - lastTs) / 1000);
    lastTs = ts;
    time.value += dt;

    draw();
    if (isRunning.value && (time.value < fissionTime || particles.value.length > 0)) {
        animationId.value = requestAnimationFrame(animate);
    } else {
        isRunning.value = false;
    }
};

watch(() => props.isPaused, (p) => {
    if (!p && isRunning.value) { lastTs = null; animationId.value = requestAnimationFrame(animate); }
});

const draw = () => {
    if (!ctx.value) return;
    const c = ctx.value;
    const w = canvasConfig.value.width;
    const h = canvasConfig.value.height;

    c.fillStyle = '#0a0a0a';
    c.fillRect(0, 0, w, h);

    // Draw particles
    particles.value.forEach(p => {
        if (!p.active) return;

        switch (p.type) {
            case 'neutron':
                c.beginPath();
                c.arc(p.x, p.y, 8, 0, Math.PI * 2);
                c.fillStyle = '#3b82f6';
                c.fill();
                c.fillStyle = 'white';
                c.font = '10px Inter';
                c.textAlign = 'center';
                c.fillText('n', p.x, p.y + 4);
                break;
            case 'uranium':
                c.beginPath();
                c.arc(p.x, p.y, 35, 0, Math.PI * 2);
                c.fillStyle = '#22c55e';
                c.fill();
                c.fillStyle = 'white';
                c.font = 'bold 14px Inter';
                c.fillText('²³⁵U', p.x, p.y + 5);
                break;
            case 'barium':
                c.beginPath();
                c.arc(p.x, p.y, 25, 0, Math.PI * 2);
                c.fillStyle = '#f59e0b';
                c.fill();
                c.fillStyle = 'white';
                c.font = '12px Inter';
                c.fillText('Ba', p.x, p.y + 4);
                break;
            case 'krypton':
                c.beginPath();
                c.arc(p.x, p.y, 20, 0, Math.PI * 2);
                c.fillStyle = '#8b5cf6';
                c.fill();
                c.fillStyle = 'white';
                c.font = '12px Inter';
                c.fillText('Kr', p.x, p.y + 4);
                break;
            case 'gamma':
                c.strokeStyle = `rgba(253, 224, 71, ${p.life / 30})`;
                c.lineWidth = 2;
                c.beginPath();
                c.moveTo(p.x - 5, p.y);
                c.lineTo(p.x + 5, p.y);
                c.moveTo(p.x, p.y - 5);
                c.lineTo(p.x, p.y + 5);
                c.stroke();
                break;
        }
    });

    // Info panel
    c.fillStyle = 'rgba(30, 41, 59, 0.95)';
    c.fillRect(20, 20, 300, 205);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 16px Inter';
    c.textAlign = 'left';
    c.fillText('Yadro bo\'linishi', 35, 50);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText('²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n + γ + E', 35, 75);

    c.fillStyle = '#22c55e';
    c.fillText(`Bo'linishlar: ${fissionCount.value}`, 35, 100);
    c.fillStyle = '#ef4444';
    c.fillText(`Energiya: ≈ ${energyReleased.value} MeV`, 35, 120);
    c.fillStyle = '#cbd5e1';
    c.fillText(`Δm = ${Q.deltaMassU.toFixed(4)} u → ΔE = Δm·c² = ${Q.energyMeV.toFixed(1)} MeV`, 35, 142);
    c.fillText(`KE(Kr) ≈ ${KIN.kineticB.toFixed(0)} MeV, KE(Ba) ≈ ${KIN.kineticA.toFixed(0)} MeV`, 35, 160);
    c.fillStyle = '#94a3b8';
    c.fillText('≈ 200 MeV har bir bo\'linishda (β/γ bilan)', 35, 182);
    c.fillText(`1 g ²³⁵U ≈ ${(ENERGY_PER_GRAM_J / 1e10).toFixed(1)}·10¹⁰ J`, 35, 202);
    c.fillStyle = '#64748b';
    c.font = '10px Inter';
    c.fillText('1 u = 931.494 MeV', 35, 218);
};

onMounted(() => initCanvas());
onUnmounted(() => { if (animationId.value) cancelAnimationFrame(animationId.value); });
</script>

<template>
    <div class="simulation">
        <canvas ref="canvas" :width="canvasConfig.width" :height="canvasConfig.height" class="canvas" />
        <div class="controls">
            <div class="buttons">
                <button @click="startFission" :disabled="isRunning" class="btn start">☢️ Bo'linishni boshlash</button>
                <button @click="reset" class="btn reset">🔄</button>
            </div>
            <div class="legend">
                <span class="item neutron">● Neytron</span>
                <span class="item uranium">● Uran-235</span>
                <span class="item barium">● Bariy</span>
                <span class="item krypton">● Kripton</span>
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

.buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.5;
}

.btn.start {
    background: #22c55e;
    color: white;
}

.btn.reset {
    background: #475569;
    color: white;
}

.legend {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
}

.item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.item.neutron {
    color: #3b82f6;
}

.item.uranium {
    color: #22c55e;
}

.item.barium {
    color: #f59e0b;
}

.item.krypton {
    color: #8b5cf6;
}
</style>
