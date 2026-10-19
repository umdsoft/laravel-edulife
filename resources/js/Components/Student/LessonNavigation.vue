<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, CheckIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';

defineProps({
    prevLesson: Object,
    nextLesson: Object,
    course: Object,
});

const linkBase = 'focus-ring group flex min-w-0 items-center gap-3 rounded-lg border border-border bg-surface p-3 shadow-xs transition-colors duration-150 hover:border-border-strong hover:bg-surface-2/60';
const iconBase = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-2 text-fg-muted transition-colors duration-150 group-hover:bg-brand-50 group-hover:text-brand';
</script>

<template>
    <nav aria-label="Darslar bo'yicha harakat" class="grid gap-3 sm:grid-cols-2">
        <div class="min-w-0">
            <Link v-if="prevLesson" :href="route('student.learn.lesson', { course: course.slug, lesson: prevLesson.id })"
                :class="linkBase" rel="prev">
                <span :class="iconBase"><ChevronLeftIcon class="h-5 w-5" aria-hidden="true" /></span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-fg-subtle">Oldingi dars</span>
                    <span class="block truncate text-sm font-medium text-fg">{{ prevLesson.title }}</span>
                </span>
            </Link>
        </div>

        <div class="min-w-0">
            <Link v-if="nextLesson" :href="route('student.learn.lesson', { course: course.slug, lesson: nextLesson.id })"
                :class="[linkBase, 'justify-end text-right']" rel="next">
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-fg-subtle">Keyingi dars</span>
                    <span class="block truncate text-sm font-medium text-fg">{{ nextLesson.title }}</span>
                </span>
                <span :class="iconBase"><ChevronRightIcon class="h-5 w-5" aria-hidden="true" /></span>
            </Link>

            <!-- Last lesson: back to the course overview -->
            <div v-else class="flex h-full items-center justify-end">
                <Button :href="route('student.learn.course', course.slug)" :icon-right="CheckIcon" class="w-full sm:w-auto">
                    Kursni yakunlash
                </Button>
            </div>
        </div>
    </nav>
</template>
