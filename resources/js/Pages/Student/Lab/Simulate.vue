<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

// Components
import { getSimulationComponent, isSimulationAvailable } from '@/Components/Lab/simulationRegistry';
import TaskPanel from '@/Components/Lab/TaskPanel.vue';
import MeasurementPanel from '@/Components/Lab/MeasurementPanel.vue';
import FormulaSheet from '@/Components/Lab/FormulaSheet.vue';
import ResultModal from '@/Components/Lab/ResultModal.vue';

const props = defineProps({
    experiment: Object,
    attempt: Object,
});

// State
const currentTask = ref(props.attempt.current_task || 1);
const tasksProgress = ref(props.attempt.tasks_progress || {});
const measurements = ref(props.attempt.measurements || []);
const calculations = ref([]);
const simulationState = ref(props.attempt.simulation_state || {});
const timeSpent = ref(props.attempt.time_spent_seconds || 0);
const isPaused = ref(false);
const showFormulas = ref(false);
const showTheory = ref(false);
const showResult = ref(false);
const resultData = ref(null);
const isCompleting = ref(false);

// Auto-save interval
let autoSaveInterval = null;
let timerInterval = null;

// Computed
const currentTaskData = computed(() => {
    return props.experiment.tasks?.find(t => t.step === currentTask.value);
});

const progressPercent = computed(() => {
    const completed = Object.keys(tasksProgress.value).filter(
        k => tasksProgress.value[k]?.completed
    ).length;
    return Math.round((completed / props.experiment.tasks.length) * 100);
});

const formattedTime = computed(() => {
    const hrs = Math.floor(timeSpent.value / 3600);
    const mins = Math.floor((timeSpent.value % 3600) / 60);
    const secs = timeSpent.value % 60;
    return hrs > 0
        ? `${hrs}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
        : `${mins}:${String(secs).padStart(2, '0')}`;
});

// Dynamic simulation component loading
const simulationComponent = computed(() => {
    return getSimulationComponent(props.experiment.simulation_type);
});

const hasSimulation = computed(() => {
    return isSimulationAvailable(props.experiment.simulation_type);
});

// Methods
const saveState = async () => {
    try {
        await axios.post(route('student.lab.api.saveState', props.attempt.id), {
            state: simulationState.value,
        });
    } catch (e) {
        console.error('Save state error:', e);
    }
};

const addMeasurement = async (measurement) => {
    measurements.value.push({
        ...measurement,
        timestamp: new Date().toISOString(),
    });

    try {
        await axios.post(route('student.lab.api.measurement', props.attempt.id), {
            name: measurement.name,
            value: measurement.value,
            unit: measurement.unit,
            step: currentTask.value,
        });
    } catch (e) {
        console.error('Add measurement error:', e);
    }
};

const submitCalculation = async (calculation) => {
    calculations.value.push(calculation);

    try {
        await axios.post(route('student.lab.api.calculation', props.attempt.id), {
            formula_id: calculation.formula_id,
            inputs: calculation.inputs,
            result: calculation.result,
            step: currentTask.value,
        });
    } catch (e) {
        console.error('Submit calculation error:', e);
    }
};

const completeTask = async (taskNumber, score, maxScore, userInput = null) => {
    tasksProgress.value[taskNumber] = {
        completed: true,
        score,
        max_score: maxScore,
        user_input: userInput,
        completed_at: new Date().toISOString(),
    };

    try {
        await axios.post(route('student.lab.api.completeTask', props.attempt.id), {
            task_number: taskNumber,
            score,
            max_score: maxScore,
            user_input: userInput,
        });

        // Move to next task
        if (taskNumber < props.experiment.tasks.length) {
            currentTask.value = taskNumber + 1;
        }
    } catch (e) {
        console.error('Complete task error:', e);
    }
};

const completeExperiment = async () => {
    if (isCompleting.value) return;
    isCompleting.value = true;

    try {
        const response = await axios.post(route('student.lab.api.complete', props.attempt.id), {
            conclusion: simulationState.value.conclusion || '',
        });

        resultData.value = response.data.result;
        showResult.value = true;
    } catch (e) {
        console.error('Complete experiment error:', e);
    } finally {
        isCompleting.value = false;
    }
};

const pauseSimulation = async () => {
    isPaused.value = true;
    clearInterval(timerInterval);

    try {
        await axios.post(route('student.lab.api.pause', props.attempt.id));
    } catch (e) {
        console.error('Pause error:', e);
    }
};

const resumeSimulation = () => {
    isPaused.value = false;
    startTimer();
};

const startTimer = () => {
    timerInterval = setInterval(() => {
        if (!isPaused.value) {
            timeSpent.value++;
        }
    }, 1000);
};

const exitSimulation = () => {
    if (confirm('Tajribadan chiqishni xohlaysizmi? Natijangiz saqlanadi.')) {
        pauseSimulation();
        router.visit(route('student.lab.show', props.experiment.slug));
    }
};

const closeResult = () => {
    showResult.value = false;
    router.visit(route('student.lab.show', props.experiment.slug));
};

// Lifecycle
onMounted(() => {
    startTimer();

    // Auto-save every 30 seconds
    autoSaveInterval = setInterval(saveState, 30000);

    // Prevent accidental navigation
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
    clearInterval(timerInterval);
    clearInterval(autoSaveInterval);
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

const handleBeforeUnload = (e) => {
    if (!showResult.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};
</script>

<template>

    <Head :title="`${experiment.title} - Simulyatsiya`" />

    <div class="simulation-layout">
        <!-- Header Bar -->
        <header class="sim-header">
            <div class="header-left">
                <button @click="exitSimulation" class="exit-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="experiment-info">
                    <span class="category-tag">{{ experiment.category }}</span>
                    <h1 class="experiment-title">{{ experiment.title }}</h1>
                </div>
            </div>

            <div class="header-center">
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="bar" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <span class="progress-text">{{ progressPercent }}%</span>
                </div>
            </div>

            <div class="header-right">
                <div class="timer" :class="{ paused: isPaused }">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-sm" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ formattedTime }}
                </div>

                <button @click="showFormulas = !showFormulas" class="tool-btn" :class="{ active: showFormulas }">
                    📐 Formulalar
                </button>

                <button @click="showTheory = !showTheory" class="tool-btn" :class="{ active: showTheory }">
                    📖 Nazariya
                </button>

                <button v-if="!isPaused" @click="pauseSimulation" class="pause-btn">
                    ⏸️ Pauza
                </button>
                <button v-else @click="resumeSimulation" class="resume-btn">
                    ▶️ Davom
                </button>
            </div>
        </header>

        <!-- Main Simulation Area -->
        <div class="sim-main">
            <!-- Left Panel - Tasks -->
            <aside class="task-sidebar">
                <TaskPanel :tasks="experiment.tasks" :currentTask="currentTask" :tasksProgress="tasksProgress"
                    @selectTask="(t) => currentTask = t" />
            </aside>

            <!-- Center - Simulation Canvas -->
            <main class="simulation-area">
                <component v-if="simulationComponent" :is="simulationComponent" :config="experiment.simulation_config"
                    :currentTask="currentTaskData" :isPaused="isPaused" v-model:state="simulationState"
                    @measurement="addMeasurement" @taskComplete="completeTask" />

                <div v-else class="no-simulation">
                    <p>Bu simulyatsiya turi hali mavjud emas</p>
                </div>
            </main>

            <!-- Right Panel - Measurements -->
            <aside class="measurement-sidebar">
                <MeasurementPanel :measurements="measurements" :calculations="calculations"
                    :currentTask="currentTaskData" :formulas="experiment.formulas" @calculate="submitCalculation" />

                <!-- Complete Button -->
                <div v-if="progressPercent >= 100" class="complete-section">
                    <button @click="completeExperiment" class="complete-btn" :disabled="isCompleting">
                        {{ isCompleting ? 'Yuklanmoqda...' : '✓ Tajribani yakunlash' }}
                    </button>
                </div>
            </aside>
        </div>

        <!-- Formulas Sheet Overlay -->
        <FormulaSheet v-if="showFormulas" :formulas="experiment.formulas" @close="showFormulas = false" />

        <!-- Theory Overlay -->
        <div v-if="showTheory" class="theory-overlay" @click.self="showTheory = false">
            <div class="theory-content">
                <button @click="showTheory = false" class="close-btn">×</button>
                <h2>📖 Nazariy qism</h2>
                <div v-html="experiment.theory?.replace(/\n/g, '<br>')"></div>

                <h3>Maqsadlar:</h3>
                <ul>
                    <li v-for="(obj, i) in experiment.objectives" :key="i">{{ obj }}</li>
                </ul>
            </div>
        </div>

        <!-- Pause Overlay -->
        <div v-if="isPaused" class="pause-overlay">
            <div class="pause-content">
                <span class="pause-icon">⏸️</span>
                <h2>Tajriba pauza qilindi</h2>
                <p>Davom etish uchun tugmani bosing</p>
                <button @click="resumeSimulation" class="resume-btn-lg">
                    ▶️ Davom etish
                </button>
            </div>
        </div>

        <!-- Result Modal -->
        <ResultModal v-if="showResult" :result="resultData" :experiment="experiment" @close="closeResult" />
    </div>
</template>

<style scoped>
.simulation-layout {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #0f172a;
    color: white;
}

/* Header */
.sim-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #1e293b;
    border-bottom: 1px solid #334155;
}

.header-left,
.header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.exit-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #334155;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.exit-btn:hover {
    background: #475569;
    color: white;
}

.experiment-info {
    display: flex;
    flex-direction: column;
}

.category-tag {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
}

.experiment-title {
    font-size: 1rem;
    font-weight: 600;
}

.header-center {
    flex: 1;
    max-width: 300px;
    margin: 0 2rem;
}

.progress-indicator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #334155;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar .bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    border-radius: 4px;
    transition: width 0.3s;
}

.progress-text {
    font-size: 0.85rem;
    font-weight: 600;
    color: #10b981;
}

.timer {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    background: #334155;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.timer.paused {
    color: #f59e0b;
}

.tool-btn {
    padding: 0.5rem 0.875rem;
    background: #334155;
    border: none;
    border-radius: 8px;
    color: #94a3b8;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.tool-btn:hover,
.tool-btn.active {
    background: #475569;
    color: white;
}

.pause-btn,
.resume-btn {
    padding: 0.5rem 0.875rem;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.pause-btn {
    background: #f59e0b;
    color: #1e293b;
}

.resume-btn {
    background: #10b981;
    color: white;
}

/* Main Area */
.sim-main {
    flex: 1;
    display: grid;
    grid-template-columns: 280px 1fr 320px;
    gap: 1px;
    background: #334155;
    overflow: hidden;
}

.task-sidebar,
.measurement-sidebar {
    background: #1e293b;
    overflow-y: auto;
    padding: 1rem;
}

.simulation-area {
    background: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.no-simulation {
    color: #64748b;
    font-size: 1.125rem;
}

/* Complete Section */
.complete-section {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #334155;
}

.complete-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.complete-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.complete-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Theory Overlay */
.theory-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.theory-content {
    max-width: 700px;
    max-height: 80vh;
    background: #1e293b;
    border-radius: 16px;
    padding: 2rem;
    overflow-y: auto;
    position: relative;
}

.theory-content h2 {
    margin-bottom: 1rem;
}

.theory-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.theory-content ul {
    padding-left: 1.5rem;
}

.theory-content li {
    margin-bottom: 0.5rem;
    color: #94a3b8;
}

.close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #334155;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Pause Overlay */
.pause-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.pause-content {
    text-align: center;
}

.pause-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
}

.pause-content h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.pause-content p {
    color: #94a3b8;
    margin-bottom: 2rem;
}

.resume-btn-lg {
    padding: 1rem 2rem;
    background: #10b981;
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1.125rem;
    font-weight: 700;
    cursor: pointer;
}

/* Icons */
.icon {
    width: 20px;
    height: 20px;
}

.icon-sm {
    width: 16px;
    height: 16px;
}

/* Responsive */
@media (max-width: 1200px) {
    .sim-main {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr auto;
    }

    .task-sidebar,
    .measurement-sidebar {
        padding: 0.75rem;
    }
}
</style>
