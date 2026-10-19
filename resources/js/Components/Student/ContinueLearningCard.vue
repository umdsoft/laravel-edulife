<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { PlayIcon } from '@heroicons/vue/20/solid';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import CourseThumbnail from './CourseThumbnail.vue';

const props = defineProps({
    enrollment: Object,
});

const progress = computed(() => Math.round(Number(props.enrollment.progress) || 0));
const href = computed(() => route('student.courses.show', props.enrollment.course.slug));
</script>

<template>
    <article
        class="group relative flex items-center gap-4 rounded-lg border border-border bg-surface p-3 shadow-xs transition-shadow duration-150 ease-out hover:border-border-strong hover:shadow-md sm:p-4">
        <CourseThumbnail :src="enrollment.course.thumbnail_url" alt="" icon-class="h-6 w-6"
            class="h-16 w-16 shrink-0 rounded-md sm:h-[72px] sm:w-[72px]" />

        <div class="min-w-0 flex-1">
            <h3 class="truncate text-sm font-semibold text-fg">
                <Link :href="href" class="focus-ring rounded-sm after:absolute after:inset-0 after:content-['']">
                    {{ enrollment.course.title }}
                </Link>
            </h3>
            <p class="mt-0.5 truncate text-13 text-fg-subtle">
                {{ enrollment.last_lesson?.title || 'Hali boshlanmagan' }}
            </p>
            <div class="mt-2.5 flex items-center gap-3">
                <ProgressBar :value="progress" size="xs" class="flex-1" />
                <span class="text-xs font-medium text-fg-muted tabular-nums">{{ progress }}%</span>
            </div>
        </div>

        <span aria-hidden="true"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand transition-colors duration-150 group-hover:bg-brand-600 group-hover:text-white">
            <PlayIcon class="ml-0.5 h-5 w-5" />
        </span>
    </article>
</template>
