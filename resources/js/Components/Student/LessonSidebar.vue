<script setup>
/**
 * LessonSidebar — course curriculum (modules → lessons) with per-lesson status.
 * Props unchanged: course, currentLesson, progress (lesson_id → progress), moduleProgress (module_id → {completed,total,percentage}).
 * It no longer hides itself below lg — the parent (LearnFrame) decides where it is shown.
 */
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronDownIcon, CheckCircleIcon, PlayCircleIcon, DocumentTextIcon } from '@heroicons/vue/20/solid';
import { formatClock } from './format.js';

const props = defineProps({
    course: Object,
    currentLesson: Object,
    progress: Object, // Map of lesson_id -> progress object
    moduleProgress: Object, // Map of module_id -> progress object
});

const emit = defineEmits(['navigate']);

const modules = computed(() => props.course?.modules || []);
const progressMap = computed(() => props.progress || {});
const moduleMap = computed(() => props.moduleProgress || {});

const isLessonCompleted = (lessonId) => !!progressMap.value[lessonId]?.is_completed;
const isLessonInProgress = (lessonId) => {
    const p = progressMap.value[lessonId];
    return !!p && !p.is_completed && p.watch_percentage > 0;
};
const isCurrent = (lesson) => !!props.currentLesson && props.currentLesson.id === lesson.id;

const totals = computed(() => {
    let total = 0;
    let done = 0;
    modules.value.forEach((m) => (m.lessons || []).forEach((l) => {
        total++;
        if (isLessonCompleted(l.id)) done++;
    }));
    return { total, done };
});

// Collapsible modules: the module holding the current lesson starts open (or all, when no lesson is active)
const open = ref({});
const initOpen = () => {
    const state = {};
    modules.value.forEach((m) => {
        state[m.id] = !props.currentLesson || (m.lessons || []).some((l) => l.id === props.currentLesson.id);
    });
    open.value = state;
};
initOpen();
watch(() => props.currentLesson?.id, initOpen);

const toggle = (id) => (open.value = { ...open.value, [id]: !open.value[id] });
const formatDuration = (seconds) => (seconds ? formatClock(seconds) : '—');
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
            <h2 class="text-sm font-semibold text-fg">Kurs dasturi</h2>
            <span v-if="totals.total" class="text-xs text-fg-subtle tabular-nums">
                {{ totals.done }}/{{ totals.total }} dars
            </span>
        </div>

        <div class="min-h-0 flex-1 divide-y divide-border overflow-y-auto">
            <section v-for="(module, mi) in modules" :key="module.id">
                <h3>
                    <button type="button"
                        class="focus-ring flex w-full items-start gap-3 px-4 py-3 text-left transition-colors duration-150 hover:bg-surface-2/60"
                        :aria-expanded="!!open[module.id]" :aria-controls="`module-${module.id}`" @click="toggle(module.id)">
                        <span class="mt-0.5 text-xs font-semibold text-fg-subtle tabular-nums">{{ String(mi + 1).padStart(2, '0') }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-13 font-semibold text-fg">{{ module.title }}</span>
                            <span v-if="moduleMap[module.id]" class="mt-1.5 flex items-center gap-2">
                                <span class="h-1 flex-1 overflow-hidden rounded-full bg-surface-2">
                                    <span class="block h-full rounded-full bg-success-600 transition-[width] duration-300"
                                        :style="{ width: `${moduleMap[module.id].percentage || 0}%` }" />
                                </span>
                                <span class="text-[11px] text-fg-subtle tabular-nums">
                                    {{ moduleMap[module.id].completed }}/{{ moduleMap[module.id].total }}
                                </span>
                            </span>
                        </span>
                        <ChevronDownIcon :class="['mt-0.5 h-4 w-4 shrink-0 text-fg-subtle transition-transform duration-150', open[module.id] ? 'rotate-180' : '']"
                            aria-hidden="true" />
                    </button>
                </h3>

                <ul v-show="open[module.id]" :id="`module-${module.id}`" class="pb-2">
                    <li v-for="lesson in module.lessons" :key="lesson.id">
                        <Link :href="route('student.learn.lesson', { course: course.slug, lesson: lesson.id })"
                            :aria-current="isCurrent(lesson) ? 'page' : undefined" :class="[
                                'focus-ring relative mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 transition-colors duration-150',
                                isCurrent(lesson) ? 'bg-brand-50' : 'hover:bg-surface-2/70',
                            ]" @click="emit('navigate', lesson)">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center">
                                <CheckCircleIcon v-if="isLessonCompleted(lesson.id)" class="h-5 w-5 text-success" aria-hidden="true" />
                                <span v-else-if="isLessonInProgress(lesson.id)"
                                    class="flex h-4 w-4 items-center justify-center rounded-full border-2 border-brand-500" aria-hidden="true">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-600" />
                                </span>
                                <span v-else class="h-4 w-4 rounded-full border-2 border-border-strong" aria-hidden="true" />
                                <span class="sr-only">
                                    {{ isLessonCompleted(lesson.id) ? 'Tugatilgan' : (isLessonInProgress(lesson.id) ? 'Jarayonda' : 'Boshlanmagan') }}
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span :class="['block truncate text-13', isCurrent(lesson) ? 'font-semibold text-brand' : 'font-medium text-fg']">
                                    {{ lesson.title }}
                                </span>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs text-fg-subtle">
                                    <DocumentTextIcon v-if="lesson.type === 'text'" class="h-3.5 w-3.5" aria-hidden="true" />
                                    <PlayCircleIcon v-else class="h-3.5 w-3.5" aria-hidden="true" />
                                    <span class="tabular-nums">{{ lesson.type === 'text' ? 'Matn' : formatDuration(lesson.duration) }}</span>
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <p v-if="!modules.length" class="px-4 py-6 text-center text-13 text-fg-subtle">Dastur hali tayyorlanmagan.</p>
        </div>
    </div>
</template>
