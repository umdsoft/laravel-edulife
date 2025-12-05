<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({ config: Object, currentTask: Object, isPaused: Boolean, state: Object });
const emit = defineEmits(['update:state', 'measurement', 'taskComplete']);

const neutronEnergy = ref(50);

const canvas = ref(null);
const ctx = ref(null);
const isRunning = ref(false);
const animationId = ref(null);

const particles = ref([]);
const fissionCount = ref(0);
const energyReleased = ref(0);

const canvasConfig = computed(() => ({ width: 900, height: 500 }));

const initCanvas = () => { if (canvas.value) { ctx.value = canvas.value.getContext('2d'); draw(); } };

const startFission = () => {
    if (isRunning.value) return;
    particles.value = [];
    fissionCount.value = 0;
    energyReleased.value = 0;

    // Initial neutron
    particles.value.push({
        type: 'neutron',
        x: 100,
        y: 250,
        vx: 3,
        vy: 0,
        active: true
    });

    // Uranium atom
    particles.value.push({
        type: 'uranium',
        x: 400,
        y: 250,
        vx: 0,
        vy: 0,
        active: true
    });

    isRunning.value = true;
    animate();
};

const reset = () => {
    isRunning.value = false;
    if (animationId.value) cancelAnimationFrame(animationId.value);
    particles.value = [];
    fissionCount.value = 0;
    energyReleased.value = 0;
    draw();
};

const animate = () => {
    if (!isRunning.value) return;

    // Update particles
    particles.value.forEach(p => {
        if (!p.active) return;
        p.x += p.vx;
        p.y += p.vy;

        // Check for collision with uranium
        if (p.type === 'neutron') {
            particles.value.forEach(u => {
                if (u.type === 'uranium' && u.active) {
                    const dist = Math.sqrt((p.x - u.x) ** 2 + (p.y - u.y) ** 2);
                    if (dist < 40) {
                        // Fission event!
                        p.active = false;
                        u.active = false;
                        fissionCount.value++;
                        energyReleased.value += 200; // MeV

                        // Create fission products
                        particles.value.push({
                            type: 'barium',
                            x: u.x - 10,
                            y: u.y,
                            vx: -2,
                            vy: -1,
                            active: true
                        });
                        particles.value.push({
                            type: 'krypton',
                            x: u.x + 10,
                            y: u.y,
                            vx: 2,
                            vy: 1,
                            active: true
                        });

                        // Release 2-3 neutrons
                        for (let i = 0; i < 3; i++) {
                            const angle = (i / 3) * Math.PI * 2 + Math.random() * 0.5;
                            particles.value.push({
                                type: 'neutron',
                                x: u.x,
                                y: u.y,
                                vx: Math.cos(angle) * 4,
                                vy: Math.sin(angle) * 4,
                                active: true
                            });
                        }

                        // Energy release (gamma)
                        for (let i = 0; i < 5; i++) {
                            particles.value.push({
                                type: 'gamma',
                                x: u.x,
                                y: u.y,
                                vx: (Math.random() - 0.5) * 8,
                                vy: (Math.random() - 0.5) * 8,
                                life: 30,
                                active: true
                            });
                        }
                    }
                }
            });
        }
    });

    // Update gamma particles
    particles.value.forEach(p => {
        if (p.type === 'gamma' && p.active) {
            p.life--;
            if (p.life <= 0) p.active = false;
        }
    });

    // Remove off-screen particles
    particles.value = particles.value.filter(p =>
        p.x > -50 && p.x < 950 && p.y > -50 && p.y < 550
    );

    draw();
    if (isRunning.value && particles.value.some(p => p.active)) {
        animationId.value = requestAnimationFrame(animate);
    } else {
        isRunning.value = false;
    }
};

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
    c.fillRect(20, 20, 250, 150);
    c.fillStyle = '#f59e0b';
    c.font = 'bold 16px Inter';
    c.textAlign = 'left';
    c.fillText('Yadro bo\'linishi', 35, 50);

    c.fillStyle = 'white';
    c.font = '12px Inter';
    c.fillText('²³⁵U + n → Ba + Kr + 3n + γ + E', 35, 80);

    c.fillStyle = '#22c55e';
    c.fillText(`Bo'linishlar: ${fissionCount.value}`, 35, 110);
    c.fillStyle = '#ef4444';
    c.fillText(`Energiya: ${energyReleased.value} MeV`, 35, 130);
    c.fillStyle = '#94a3b8';
    c.fillText('≈ 200 MeV har bir bo\'linishda', 35, 155);
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
