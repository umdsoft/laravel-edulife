<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { PhotoIcon, QueueListIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import { StarIcon, UsersIcon, RectangleStackIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { courseStatus, formatMoney, formatNumber } from './format';

const props = defineProps({
    course: Object,
});

const status = computed(() => courseStatus(props.course.status));
const rating = computed(() => (props.course.reviews_avg_rating ? Number(props.course.reviews_avg_rating).toFixed(1) : '0.0'));
const editHref = computed(() => route('teacher.courses.edit', props.course.id));
const curriculumHref = computed(() => route('teacher.courses.modules.index', props.course.id));
const price = computed(() => (props.course.is_free || !Number(props.course.price) ? 'Bepul' : formatMoney(props.course.price)));
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xs transition-shadow duration-150 hover:border-border-strong hover:shadow-md">
        <Link :href="editHref" class="focus-ring relative block aspect-video bg-surface-2" tabindex="-1" aria-hidden="true">
            <img v-if="course.thumbnail_url" :src="course.thumbnail_url" alt="" loading="lazy" class="h-full w-full object-cover" />
            <div v-else class="flex h-full w-full items-center justify-center text-fg-subtle">
                <PhotoIcon class="h-10 w-10" aria-hidden="true" />
            </div>
            <div class="absolute left-3 top-3">
                <Badge :variant="status.variant" dot class="shadow-xs">{{ status.label }}</Badge>
            </div>
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <h3 class="line-clamp-2 text-base font-semibold leading-snug text-fg">
                <Link :href="editHref" class="focus-ring rounded-sm hover:text-brand">{{ course.title }}</Link>
            </h3>

            <dl class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-13 text-fg-muted">
                <div class="flex items-center gap-1" :title="`${course.reviews_count || 0} ta sharh`">
                    <dt class="sr-only">Reyting</dt>
                    <StarIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                    <dd class="tabular-nums"><span class="font-medium text-fg">{{ rating }}</span> ({{ course.reviews_count || 0 }})</dd>
                </div>
                <div class="flex items-center gap-1">
                    <dt class="sr-only">O'quvchilar</dt>
                    <UsersIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    <dd class="tabular-nums">{{ formatNumber(course.enrollments_count || 0) }}</dd>
                </div>
                <div v-if="course.modules_count !== undefined" class="flex items-center gap-1">
                    <dt class="sr-only">Modullar</dt>
                    <RectangleStackIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    <dd class="tabular-nums">{{ course.modules_count }} modul</dd>
                </div>
            </dl>

            <div class="mt-auto flex items-center gap-2 pt-4">
                <span class="mr-auto text-sm font-medium tabular-nums text-fg">{{ price }}</span>
                <IconButton :href="curriculumHref" :icon="QueueListIcon" label="Darslar rejasi" variant="secondary" size="sm" />
                <Button :href="editHref" variant="secondary" size="sm" :icon-left="PencilSquareIcon">Tahrirlash</Button>
            </div>
        </div>
    </article>
</template>
