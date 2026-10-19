<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { UsersIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import CourseThumbnail from './CourseThumbnail.vue';
import StarRating from './StarRating.vue';
import { formatNumber, formatPrice } from './format.js';

const props = defineProps({
    course: Object,
});

const rating = computed(() => Number(props.course.reviews_avg_rating || 0));
const price = computed(() => formatPrice(props.course.price, props.course.is_free));
</script>

<template>
    <article
        class="group relative flex h-full flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xs transition-shadow duration-150 ease-out hover:border-border-strong hover:shadow-md">
        <CourseThumbnail :src="course.thumbnail_url" :alt="''" class="aspect-video">
            <div v-if="course.is_bestseller || course.is_new" class="absolute left-3 top-3 flex gap-1.5">
                <Badge v-if="course.is_bestseller" variant="warning" size="sm" solid>Bestseller</Badge>
                <Badge v-if="course.is_new" variant="success" size="sm" solid>Yangi</Badge>
            </div>
        </CourseThumbnail>

        <div class="flex flex-1 flex-col p-4 sm:p-5">
            <div class="mb-2 flex items-center justify-between gap-2">
                <Badge v-if="course.direction?.name" variant="brand" size="sm" class="truncate">
                    {{ course.direction.name }}
                </Badge>
                <span v-else />
                <StarRating :value="rating" :count="course.reviews_count || 0" compact size="xs" />
            </div>

            <h3 class="line-clamp-2 text-base font-semibold leading-snug text-fg">
                <!-- Stretched link: the whole card is clickable, but there is one focusable target -->
                <Link :href="route('student.courses.show', course.slug)"
                    class="focus-ring rounded-sm after:absolute after:inset-0 after:content-['']">
                    {{ course.title }}
                </Link>
            </h3>

            <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                <div class="flex min-w-0 items-center gap-2">
                    <Avatar :src="course.teacher?.avatar_url" :name="course.teacher?.full_name || ''" size="xs" />
                    <span class="truncate text-13 text-fg-muted">{{ course.teacher?.full_name }}</span>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1 text-13 text-fg-subtle tabular-nums"
                    :title="`${course.enrollments_count || 0} o'quvchi`">
                    <UsersIcon class="h-4 w-4" aria-hidden="true" />
                    {{ formatNumber(course.enrollments_count || 0) }}
                    <span class="sr-only">o'quvchi</span>
                </span>
            </div>

            <div class="mt-3 border-t border-border pt-3">
                <span class="text-sm font-semibold tabular-nums" :class="course.is_free ? 'text-success' : 'text-fg'">
                    {{ price }}
                </span>
            </div>
        </div>
    </article>
</template>
