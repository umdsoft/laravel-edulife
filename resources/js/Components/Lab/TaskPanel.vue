<script setup>
import { computed } from 'vue';
import {
    ArrowRightIcon,
    BookOpenIcon,
    BoltIcon,
    CalculatorIcon,
    ChartBarIcon,
    CheckIcon,
    ClipboardDocumentListIcon,
    LightBulbIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    ScaleIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon, XCircleIcon, ChevronDownIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { taskAnswerKind, nextOpenStep, completedCount } from './labTasks';

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    currentTask: Number,
    tasksProgress: { type: Object, default: () => ({}) },
    feedback: { type: Object, default: null }, // { step, correct, message }
    checking: { type: Boolean, default: false },
    answerText: { type: String, default: '' },
});

defineEmits(['selectTask', 'check', 'update:answerText']);

const activeTask = computed(() => props.tasks.find((t) => t.step === props.currentTask) || null);
const activeKind = computed(() => taskAnswerKind(activeTask.value));
const activeProgress = computed(() => (activeTask.value ? props.tasksProgress[activeTask.value.step] : null));
const activeFeedback = computed(() =>
    props.feedback && activeTask.value && props.feedback.step === activeTask.value.step ? props.feedback : null
);
const activeIndex = computed(() => props.tasks.findIndex((t) => t.step === props.currentTask));
const doneCount = computed(() => completedCount(props.tasksProgress, props.tasks));

// Next unfinished task (null when this is the last open one)
const nextStep = computed(() => {
    if (!activeTask.value) return null;
    const next = nextOpenStep(props.tasks, props.tasksProgress, activeTask.value.step);
    return next !== activeTask.value.step ? next : null;
});

const answerHint = {
    measurement: "Vazifa shartidagi sozlamalarni o'rnatib, simulyatsiyada o'lchovni bajaring — natija avtomatik tekshiriladi.",
    calculation: "Kalkulyatorda vazifada ko'rsatilgan formula bo'yicha hisoblang — natija avtomatik tekshiriladi.",
    text: "Kuzatishlaringiz va xulosangizni o'z so'zlaringiz bilan yozing.",
};

const getTaskStatus = (task, progress) => {
    if (!progress[task.step]) return 'pending';
    if (progress[task.step].completed) return 'completed';
    return 'in_progress';
};

const taskTypes = {
    instruction: { icon: BookOpenIcon, label: "Yo'riqnoma" },
    measurement: { icon: ScaleIcon, label: "O'lchov" },
    calculation: { icon: CalculatorIcon, label: 'Hisoblash' },
    conclusion: { icon: PencilSquareIcon, label: 'Xulosa' },
    graph_analysis: { icon: ChartBarIcon, label: 'Grafik tahlili' },
    circuit_build: { icon: BoltIcon, label: 'Zanjir yig\'ish' },
};
const taskType = (type) => taskTypes[type] || { icon: ClipboardDocumentListIcon, label: 'Vazifa' };
</script>

<template>
    <section class="flex flex-col gap-4" aria-labelledby="lab-tasks-title">
        <div class="flex items-center justify-between gap-2">
            <h2 id="lab-tasks-title" class="text-xs font-semibold uppercase tracking-wide text-fg-subtle">Vazifalar</h2>
            <span class="text-xs font-medium text-fg-muted tabular-nums">{{ doneCount }} / {{ tasks.length }} bajarildi</span>
        </div>

        <!-- Active task -->
        <div v-if="activeTask" class="rounded-lg border border-border bg-surface p-4 shadow-xs">
            <div class="flex items-center gap-2 text-xs text-fg-subtle">
                <component :is="taskType(activeTask.type).icon" class="h-4 w-4" aria-hidden="true" />
                <span>{{ taskType(activeTask.type).label }}</span>
                <span aria-hidden="true">·</span>
                <span class="tabular-nums">{{ activeIndex + 1 }}-vazifa</span>
                <span class="ml-auto font-medium text-fg-muted tabular-nums">{{ activeTask.max_score }} ball</span>
            </div>

            <h3 class="mt-2 text-base font-semibold text-fg">{{ activeTask.title }}</h3>
            <p v-if="activeTask.description" class="mt-1.5 whitespace-pre-line text-sm leading-6 text-fg-muted">
                {{ activeTask.description }}
            </p>

            <details v-if="activeTask.hints?.length" class="group mt-3 rounded-md bg-warning-50 ring-1 ring-inset ring-warning-100">
                <summary
                    class="focus-ring flex cursor-pointer list-none items-center gap-2 rounded-md px-3 py-2 text-13 font-medium text-warning [&::-webkit-details-marker]:hidden">
                    <LightBulbIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    Ko'rsatmalar
                    <ChevronDownIcon class="ml-auto h-4 w-4 transition-transform duration-150 group-open:rotate-180"
                        aria-hidden="true" />
                </summary>
                <ul class="list-disc space-y-1 px-3 pb-3 pl-8 text-13 leading-5 text-fg-muted">
                    <li v-for="(hint, i) in activeTask.hints" :key="i">{{ hint }}</li>
                </ul>
            </details>

            <!-- Answer / check -->
            <div class="mt-4 space-y-3">
                <template v-if="activeProgress?.completed">
                    <div class="flex items-center gap-2 rounded-md bg-success-50 px-3 py-2.5 text-sm font-medium text-success ring-1 ring-inset ring-success-100">
                        <CheckCircleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                        <span>Bajarildi — <span class="tabular-nums">{{ activeProgress.score }}/{{ activeTask.max_score }}</span> ball</span>
                    </div>
                    <Button v-if="nextStep" block :icon-right="ArrowRightIcon" @click="$emit('selectTask', nextStep)">
                        Keyingi vazifa
                    </Button>
                </template>

                <template v-else>
                    <p v-if="answerHint[activeKind]" class="text-13 leading-5 text-fg-subtle">{{ answerHint[activeKind] }}</p>

                    <Textarea v-if="activeKind === 'text'" label="Javobingiz" :model-value="answerText" :rows="4"
                        :maxlength="5000" :disabled="checking" placeholder="Javobingizni shu yerga yozing..." autoresize
                        @update:model-value="$emit('update:answerText', $event)" />

                    <Button block :loading="checking" :icon-left="activeKind === 'ack' ? CheckIcon : MagnifyingGlassIcon"
                        @click="$emit('check')">
                        <template v-if="checking">Tekshirilmoqda...</template>
                        <template v-else-if="activeKind === 'ack'">Tanishdim</template>
                        <template v-else>Tekshirish</template>
                    </Button>
                </template>

                <div v-if="activeFeedback" role="status" aria-live="polite" :class="[
                    'flex gap-2 rounded-md px-3 py-2.5 text-13 leading-5 ring-1 ring-inset',
                    activeFeedback.correct ? 'bg-success-50 text-success ring-success-100' : 'bg-danger-50 text-danger ring-danger-100',
                ]">
                    <component :is="activeFeedback.correct ? CheckCircleIcon : XCircleIcon" class="mt-px h-4 w-4 shrink-0"
                        aria-hidden="true" />
                    <span>{{ activeFeedback.message }}</span>
                </div>
            </div>
        </div>

        <!-- All tasks -->
        <ol class="space-y-1.5" aria-label="Vazifalar ro'yxati">
            <li v-for="task in tasks" :key="task.step">
                <button type="button" :aria-current="currentTask === task.step ? 'step' : undefined" :class="[
                    'focus-ring flex min-h-[44px] w-full items-center gap-3 rounded-md border px-3 py-2 text-left transition-colors duration-150',
                    currentTask === task.step
                        ? 'border-brand-200 bg-brand-50'
                        : 'border-transparent hover:bg-surface-2',
                ]" @click="$emit('selectTask', task.step)">
                    <span :class="[
                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                        getTaskStatus(task, tasksProgress) === 'completed'
                            ? 'bg-success-600 text-white'
                            : currentTask === task.step
                                ? 'bg-brand-600 text-white'
                                : 'bg-surface-2 text-fg-muted ring-1 ring-inset ring-border',
                    ]">
                        <CheckIcon v-if="getTaskStatus(task, tasksProgress) === 'completed'" class="h-4 w-4" aria-hidden="true" />
                        <template v-else>{{ task.step }}</template>
                        <span v-if="getTaskStatus(task, tasksProgress) === 'completed'" class="sr-only">Bajarildi:</span>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span :class="[
                            'block truncate text-13 font-medium',
                            currentTask === task.step ? 'text-fg' : getTaskStatus(task, tasksProgress) === 'pending' ? 'text-fg-muted' : 'text-fg',
                        ]">{{ task.title }}</span>
                        <span class="mt-0.5 flex items-center gap-1 text-xs text-fg-subtle">
                            <component :is="taskType(task.type).icon" class="h-3.5 w-3.5" aria-hidden="true" />
                            <span class="tabular-nums">{{ task.max_score }} ball</span>
                        </span>
                    </span>

                    <span v-if="tasksProgress[task.step]?.score" class="shrink-0 text-xs font-semibold text-success tabular-nums">
                        {{ tasksProgress[task.step].score }}/{{ task.max_score }}
                    </span>
                </button>
            </li>
        </ol>
    </section>
</template>
