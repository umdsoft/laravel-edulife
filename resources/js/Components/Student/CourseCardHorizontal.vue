<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ClockIcon, UsersIcon } from '@heroicons/vue/20/solid';
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
        class="group relative flex h-full flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xs transition-shadow duration-150 ease-out hover:border-border-strong hover:shadow-md sm:flex-row">
        <CourseThumbnail :src="course.thumbnail_url" alt="" class="aspect-video w-full shrink-0 sm:aspect-auto sm:min-h-[148px] sm:w-56 sm:self-stretch">
            <div v-if="course.is_bestseller" class="absolute left-3 top-3">
                <Badge variant="warning" size="sm" solid>Bestseller</Badge>
            </div>
        </CourseThumbnail>

        <div class="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <Badge v-if="course.direction?.name" variant="brand" size="sm" class="mb-2">
                        {{ course.direction.name }}
                    </Badge>
                    <h3 class="text-base font-semibold leading-snug text-fg sm:text-lg">
                        <Link :href="route('student.courses.show', course.slug)"
                            class="focus-ring rounded-sm after:absolute after:inset-0 after:content-['']">
                            {{ course.title }}
                        </Link>
                    </h3>
                    <p v-if="course.description" class="mt-1.5 line-clamp-2 text-sm text-fg-muted">
                        {{ course.description }}
                    </p>
                </div>

                <span class="hidden shrink-0 text-base font-semibold tabular-nums sm:block"
                    :class="course.is_free ? 'text-success' : 'text-fg'">
                    {{ price }}
                </span>
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-x-5 gap-y-2 pt-4 text-13 text-fg-muted">
                <span class="flex min-w-0 items-center gap-2">
                    <Avatar :src="course.teacher?.avatar_url" :name="course.teacher?.full_name || ''" size="xs" />
                    <span class="truncate">{{ course.teacher?.full_name }}</span>
                </span>
                <StarRating :value="rating" :count="course.reviews_count || 0" compact size="xs" />
                <span class="inline-flex items-center gap-1 tabular-nums">
                    <UsersIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    {{ formatNumber(course.enrollments_count || 0) }} o'quvchi
                </span>
                <span class="inline-flex items-center gap-1 tabular-nums">
                    <ClockIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    {{ course.total_duration || 0 }} soat
                </span>
                <span class="font-semibold tabular-nums sm:hidden" :class="course.is_free ? 'text-success' : 'text-fg'">
                    {{ price }}
                </span>
                <!-- Optional actions (e.g. remove from wishlist); raised above the stretched link -->
                <div v-if="$slots.actions" class="relative z-10 ml-auto flex items-center gap-1.5">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </article>
</template>
