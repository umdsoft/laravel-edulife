<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    BookOpenIcon,
    CheckCircleIcon,
    CubeTransparentIcon,
    PauseCircleIcon,
    PauseIcon,
    PlayIcon,
    VariableIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon, ClockIcon } from '@heroicons/vue/20/solid';
import Alert from '@/Components/UI/Alert.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Modal from '@/Components/UI/Modal.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';

// Components
import { getSimulationComponent, isSimulationAvailable } from '@/Components/Lab/simulationRegistry';
import TaskPanel from '@/Components/Lab/TaskPanel.vue';
import MeasurementPanel from '@/Components/Lab/MeasurementPanel.vue';
import FormulaSheet from '@/Components/Lab/FormulaSheet.vue';
import ResultModal from '@/Components/Lab/ResultModal.vue';
import {
    labUrls,
    taskAnswerKind,
    normalizeTasksProgress,
    progressPercent as computeProgress,
    buildCheckPayload,
} from '@/Components/Lab/labTasks';

const props = defineProps({
    experiment: Object,
    attempt: Object,
});

const tasks = computed(() => props.experiment.tasks || []);

// State
const tasksProgress = ref(normalizeTasksProgress(props.attempt.tasks_progress));
const currentTask = ref(
    tasks.value.some((t) => t.step === props.attempt.current_task) ? props.attempt.current_task : (tasks.value[0]?.step ?? 1)
);
const measurements = ref(props.attempt.measurements || []);
const calculations = ref(props.attempt.calculations || []);
// Written answers per step (conclusion / graph analysis), prefilled from the server
const answerTexts = ref(
    Object.fromEntries(Object.values(tasksProgress.value).filter((p) => p.answer_text).map((p) => [p.step, p.answer_text]))
);
const isChecking = ref(false);
const taskFeedback = ref(null); // { step, correct, message }
const completeError = ref('');
const simulationState = ref(props.attempt.simulation_state || {});
const timeSpent = ref(props.attempt.time_spent_seconds || 0);
const isPaused = ref(false);
const showFormulas = ref(false);
const showTheory = ref(false);
const showResult = ref(false);
const resultData = ref(null);
const isCompleting = ref(false);
const showExitConfirm = ref(false);
const isExiting = ref(false);

// Auto-save interval
let autoSaveInterval = null;
let timerInterval = null;

// Computed
const currentTaskData = computed(() => {
    return tasks.value.find(t => t.step === currentTask.value);
});

const progressPercent = computed(() => computeProgress(tasksProgress.value, tasks.value));

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
        await axios.post(labUrls.saveState(props.attempt.id), {
            state: simulationState.value,
        });
    } catch (e) {
        console.error('Save state error:', e);
    }
};

const isTaskDone = (step) => !!tasksProgress.value[step]?.completed;

// Ask the server whether the active task is solved. The server decides and scores;
// we only send what the student did (measurements, calculator results or text).
const checkTask = async (step = currentTask.value, { auto = false } = {}) => {
    const task = tasks.value.find((t) => t.step === step);
    if (!task || isTaskDone(step) || isChecking.value) return;

    const payload = buildCheckPayload(task, {
        measurements: measurements.value,
        calculations: calculations.value,
        text: answerTexts.value[step] || '',
    });

    isChecking.value = true;
    try {
        const { data } = await axios.post(labUrls.checkTask(props.attempt.id), payload);

        if (data.task) {
            tasksProgress.value = { ...tasksProgress.value, [data.task_number]: data.task };
        }
        // An automatic check with an unrelated measurement stays silent.
        if (auto && !data.counted && !data.correct) return;

        // The task stays on screen with its ✓ feedback; TaskPanel offers "Keyingi vazifa".
        taskFeedback.value = { step: data.task_number, correct: !!data.correct, message: data.feedback };
    } catch (e) {
        if (!auto) {
            const message = e.response?.data?.message
                || (e.response?.status === 429 ? "Juda ko'p urinish. Bir oz kutib, qayta urinib ko'ring." : "Tekshirishda xatolik yuz berdi. Qayta urinib ko'ring.");
            taskFeedback.value = { step, correct: false, message };
        }
        console.error('Check task error:', e);
    } finally {
        isChecking.value = false;
    }
};

// Several measurements emitted in one click (e.g. dispersion) are checked together.
let autoCheckTimer = null;
const scheduleAutoCheck = (kind) => {
    const task = currentTaskData.value;
    if (!task || taskAnswerKind(task) !== kind || isTaskDone(task.step)) return;
    clearTimeout(autoCheckTimer);
    const run = () => {
        if (isChecking.value) {
            autoCheckTimer = setTimeout(run, 300);
        } else {
            checkTask(task.step, { auto: true });
        }
    };
    autoCheckTimer = setTimeout(run, 300);
};

const addMeasurement = async (measurement) => {
    measurements.value.push({
        ...measurement,
        step: currentTask.value,
        timestamp: new Date().toISOString(),
    });
    scheduleAutoCheck('measurement');

    try {
        await axios.post(labUrls.measurement(props.attempt.id), {
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
    calculations.value.push({ ...calculation, step: currentTask.value });
    scheduleAutoCheck('calculation');

    try {
        await axios.post(labUrls.calculation(props.attempt.id), {
            formula_id: calculation.formula_id,
            inputs: calculation.inputs,
            result: calculation.result,
            unit: calculation.unit,
            step: currentTask.value,
        });
    } catch (e) {
        console.error('Submit calculation error:', e);
    }
};

const selectTask = (step) => {
    currentTask.value = step;
};

const conclusionText = () => {
    const conclusionTask = tasks.value.find((t) => t.type === 'conclusion');
    return (conclusionTask && answerTexts.value[conclusionTask.step]) || simulationState.value.conclusion || '';
};

const completeExperiment = async () => {
    if (isCompleting.value) return;
    isCompleting.value = true;
    completeError.value = '';

    try {
        const response = await axios.post(labUrls.complete(props.attempt.id), {
            conclusion: conclusionText(),
        });

        resultData.value = response.data.result;
        showResult.value = true;
    } catch (e) {
        completeError.value = e.response?.data?.message || "Tajribani yakunlashda xatolik yuz berdi.";
        console.error('Complete experiment error:', e);
    } finally {
        isCompleting.value = false;
    }
};

const pauseSimulation = async () => {
    isPaused.value = true;
    clearInterval(timerInterval);

    try {
        await axios.post(labUrls.pause(props.attempt.id));
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

// Leaving asks for confirmation in a dialog (was window.confirm); the flow itself is unchanged.
const exitSimulation = () => {
    showExitConfirm.value = true;
};

const confirmExit = () => {
    showExitConfirm.value = false;
    isExiting.value = true;
    pauseSimulation();
    router.visit(labUrls.experiment(props.experiment.slug));
};

const closeResult = () => {
    showResult.value = false;
    router.visit(labUrls.experiment(props.experiment.slug));
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
    clearTimeout(autoCheckTimer);
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

    <!-- Focused full-screen workspace: quiet top bar, dominant instrument stage, compact side panels. -->
    <div class="flex min-h-dvh flex-col bg-bg text-fg lg:h-dvh lg:overflow-hidden">
        <!-- Top bar -->
        <header class="sticky top-0 z-topbar shrink-0 border-b border-border bg-surface">
            <div class="flex h-14 items-center gap-2 px-3 sm:gap-3 sm:px-4">
                <IconButton :icon="ArrowLeftIcon" label="Tajribadan chiqish" @click="exitSimulation" />

                <div class="min-w-0 flex-1">
                    <p v-if="experiment.category" class="truncate text-xs leading-4 text-fg-subtle">{{ experiment.category }}</p>
                    <h1 class="truncate text-sm font-semibold leading-5 text-fg sm:text-base">{{ experiment.title }}</h1>
                </div>

                <div class="hidden w-48 shrink-0 items-center gap-2.5 md:flex lg:w-56">
                    <span class="sr-only">Tajriba progressi</span>
                    <ProgressBar :value="progressPercent" size="sm" :tone="progressPercent >= 100 ? 'success' : 'brand'" />
                    <span class="w-10 text-right text-13 font-medium text-fg tabular-nums">{{ progressPercent }}%</span>
                </div>

                <div role="timer" :aria-label="`Sarflangan vaqt: ${formattedTime}`" :class="[
                    'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-md px-2.5 text-13 font-semibold tabular-nums ring-1 ring-inset',
                    isPaused ? 'bg-warning-50 text-warning ring-warning-100' : 'bg-surface-2 text-fg ring-border',
                ]">
                    <ClockIcon :class="['h-4 w-4', isPaused ? '' : 'text-fg-subtle']" aria-hidden="true" />
                    {{ formattedTime }}
                </div>

                <div class="hidden items-center gap-2 sm:flex">
                    <Button variant="secondary" :icon-left="VariableIcon" aria-haspopup="dialog" @click="showFormulas = true">
                        <span class="hidden lg:inline">Formulalar</span>
                        <span class="sr-only lg:hidden">Formulalar</span>
                    </Button>
                    <Button variant="secondary" :icon-left="BookOpenIcon" aria-haspopup="dialog" @click="showTheory = true">
                        <span class="hidden lg:inline">Nazariya</span>
                        <span class="sr-only lg:hidden">Nazariya</span>
                    </Button>
                </div>

                <IconButton v-if="!isPaused" :icon="PauseIcon" variant="secondary" label="Pauza" @click="pauseSimulation" />
                <IconButton v-else :icon="PlayIcon" variant="primary" label="Davom etish" @click="resumeSimulation" />

                <Button v-if="progressPercent >= 100" class="hidden sm:inline-flex" :loading="isCompleting"
                    :icon-left="CheckIcon" @click="completeExperiment">
                    Yakunlash
                </Button>
            </div>

            <!-- Mobile secondary row: progress + tools -->
            <div class="flex items-center gap-3 border-t border-border px-3 py-2 sm:hidden">
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <span class="sr-only">Tajriba progressi</span>
                    <ProgressBar :value="progressPercent" size="sm" :tone="progressPercent >= 100 ? 'success' : 'brand'" />
                    <span class="text-xs font-medium text-fg-muted tabular-nums">{{ progressPercent }}%</span>
                </div>
                <IconButton :icon="VariableIcon" variant="secondary" label="Formulalar" @click="showFormulas = true" />
                <IconButton :icon="BookOpenIcon" variant="secondary" label="Nazariya" @click="showTheory = true" />
            </div>
        </header>

        <!--
            < lg : stacked (stage → tasks → measurements), page scrolls.
            lg   : stage | right column (tasks + measurements, one scroll).
            xl   : tasks | stage | measurements (the wrapper becomes `display: contents`).
        -->
        <div
            class="flex flex-1 flex-col lg:grid lg:min-h-0 lg:grid-cols-[minmax(0,1fr)_340px] lg:grid-rows-[minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)_320px]">
            <!-- Stage -->
            <main id="main-content"
                class="flex min-w-0 flex-col p-3 sm:p-4 lg:col-start-1 lg:row-start-1 lg:min-h-0 xl:col-start-2">
                <div v-if="simulationComponent"
                    class="lab-stage flex min-h-[280px] flex-1 overflow-auto rounded-lg border p-3 shadow-xs sm:p-4">
                    <div class="m-auto max-w-full">
                        <component :is="simulationComponent" :config="experiment.simulation_config"
                            :currentTask="currentTaskData" :isPaused="isPaused" v-model:state="simulationState"
                            @measurement="addMeasurement" />
                    </div>
                </div>

                <div v-else class="flex flex-1 items-center justify-center rounded-lg border border-border bg-surface p-6 shadow-xs">
                    <EmptyState :icon="CubeTransparentIcon" title="Simulyatsiya mavjud emas"
                        description="Bu simulyatsiya turi hali mavjud emas. Keyinroq qayta urinib ko'ring." />
                </div>
            </main>

            <div class="flex flex-col lg:col-start-2 lg:row-start-1 lg:min-h-0 lg:overflow-y-auto lg:border-l lg:border-border lg:bg-surface xl:contents">
                <!-- Tasks -->
                <aside aria-label="Vazifalar"
                    class="border-t border-border bg-surface p-4 lg:border-t-0 xl:col-start-1 xl:row-start-1 xl:min-h-0 xl:overflow-y-auto xl:border-r">
                    <TaskPanel :tasks="tasks" :currentTask="currentTask" :tasksProgress="tasksProgress"
                        :feedback="taskFeedback" :checking="isChecking"
                        :answerText="answerTexts[currentTask] || ''"
                        @update:answerText="(v) => answerTexts[currentTask] = v"
                        @selectTask="selectTask" @check="checkTask(currentTask)" />

                    <!-- Complete -->
                    <div v-if="progressPercent >= 100" class="mt-4 rounded-lg border border-success-100 bg-success-50 p-4">
                        <div class="flex items-start gap-3">
                            <CheckCircleIcon class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-fg">Barcha vazifalar bajarildi</p>
                                <p class="mt-0.5 text-13 text-fg-muted">Natija va mukofotlarni olish uchun tajribani yakunlang.</p>
                            </div>
                        </div>
                        <Button block class="mt-3" :loading="isCompleting" :icon-left="CheckIcon" @click="completeExperiment">
                            Tajribani yakunlash
                        </Button>
                        <Alert v-if="completeError" variant="danger" class="mt-3">{{ completeError }}</Alert>
                    </div>
                </aside>

                <!-- Measurements + calculator -->
                <aside aria-label="O'lchovlar va kalkulyator"
                    class="border-t border-border bg-surface p-4 xl:col-start-3 xl:row-start-1 xl:min-h-0 xl:overflow-y-auto xl:border-l xl:border-t-0">
                    <MeasurementPanel :measurements="measurements" :calculations="calculations"
                        :currentTask="currentTaskData" :formulas="experiment.formulas" @calculate="submitCalculation" />
                </aside>
            </div>
        </div>

        <!-- Formulas -->
        <FormulaSheet v-if="showFormulas" :formulas="experiment.formulas" @close="showFormulas = false" />

        <!-- Theory -->
        <Modal :show="showTheory" title="Nazariy qism" :description="experiment.title" size="2xl" @close="showTheory = false">
            <div v-if="experiment.theory" class="text-sm leading-7 text-fg-muted"
                v-html="experiment.theory?.replace(/\n/g, '<br>')"></div>
            <p v-else class="text-sm text-fg-subtle">Nazariy ma'lumot qo'shilmagan.</p>

            <template v-if="experiment.objectives?.length">
                <h3 class="mt-6 text-sm font-semibold text-fg">Maqsadlar</h3>
                <ul class="mt-2 space-y-2 pb-2">
                    <li v-for="(obj, i) in experiment.objectives" :key="i" class="flex gap-2 text-sm leading-6 text-fg-muted">
                        <CheckCircleIcon class="mt-1 h-4 w-4 shrink-0 text-brand" aria-hidden="true" />
                        <span>{{ obj }}</span>
                    </li>
                </ul>
            </template>

            <template #footer>
                <Button variant="secondary" @click="showTheory = false">Yopish</Button>
            </template>
        </Modal>

        <!-- Pause -->
        <Modal :show="isPaused && !isExiting && !showResult" size="sm" @close="resumeSimulation">
            <div class="px-6 pb-6 pt-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-warning-50 text-warning">
                    <PauseCircleIcon class="h-6 w-6" aria-hidden="true" />
                </div>
                <h2 id="modal-title" class="mt-4 text-lg font-semibold text-fg">Tajriba pauza qilindi</h2>
                <p class="mt-1 text-sm text-fg-muted">Vaqt to'xtatildi. Davom etish uchun tugmani bosing.</p>
                <p class="mt-3">
                    <Badge variant="warning" :icon="ClockIcon"><span class="tabular-nums">{{ formattedTime }}</span></Badge>
                </p>
                <Button class="mt-6" block size="lg" :icon-left="PlayIcon" autofocus @click="resumeSimulation">
                    Davom etish
                </Button>
            </div>
        </Modal>

        <!-- Exit confirm -->
        <Modal :show="showExitConfirm" title="Tajribadan chiqish" size="sm" @close="showExitConfirm = false">
            <p class="pb-2 text-sm text-fg-muted">Tajribadan chiqishni xohlaysizmi? Natijangiz saqlanadi.</p>
            <template #footer>
                <Button variant="secondary" @click="showExitConfirm = false">Qolish</Button>
                <Button :icon-left="ArrowLeftIcon" @click="confirmExit">Chiqish</Button>
            </template>
        </Modal>

        <!-- Result -->
        <ResultModal v-if="showResult" :result="resultData" :experiment="experiment" @close="closeResult" />
    </div>
</template>

<style scoped>
/*
 * The instrument viewport stays dark in both themes on purpose: the physics-audited
 * simulations draw their own dark canvases and dark control panels (they are not tokenised).
 */
.lab-stage {
    background: #0b1220;
    border-color: #1e293b;
    color: #fff;
}

/* Wide canvases (800–900px) scale down to the stage; the simulations map pointer
   coordinates through getBoundingClientRect, so interaction stays exact. */
.lab-stage :deep(canvas) {
    max-width: 100%;
    height: auto;
}
</style>
