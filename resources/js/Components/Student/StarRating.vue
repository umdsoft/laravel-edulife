<script setup>
/**
 * StarRating — read-only star display.
 *   <StarRating :value="4.6" />                       (5 stars, partially filled by rounding to halves)
 *   <StarRating :value="course.rating" show-value :count="course.reviews_count" size="sm" />
 */
import { computed } from 'vue';
import { StarIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    max: { type: Number, default: 5 },
    size: { type: String, default: 'sm', validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v) },
    showValue: Boolean,
    // number of reviews, shown as "(12)" when given
    count: { type: [Number, String], default: null },
    // compact mode: a single star + value
    compact: Boolean,
});

const rating = computed(() => Math.max(0, Math.min(props.max, Number(props.value) || 0)));
const label = computed(() => {
    const base = `Reyting: ${rating.value.toFixed(1)} / ${props.max}`;
    return props.count !== null && props.count !== undefined ? `${base}, ${props.count} ta sharh` : base;
});
const sizes = { xs: 'h-3.5 w-3.5', sm: 'h-4 w-4', md: 'h-5 w-5', lg: 'h-6 w-6' };
const fill = (i) => Math.max(0, Math.min(1, rating.value - (i - 1))) * 100;
</script>

<template>
    <span class="inline-flex items-center gap-1" role="img" :aria-label="label">
        <template v-if="compact">
            <StarIcon :class="[sizes[size], 'text-warning-600']" aria-hidden="true" />
        </template>
        <span v-else class="inline-flex items-center" aria-hidden="true">
            <span v-for="i in max" :key="i" class="relative inline-block" :class="sizes[size]">
                <StarIcon :class="[sizes[size], 'absolute inset-0 text-surface-3']" />
                <span class="absolute inset-0 overflow-hidden" :style="{ width: `${fill(i)}%` }">
                    <StarIcon :class="[sizes[size], 'text-warning-600']" />
                </span>
            </span>
        </span>
        <span v-if="showValue || compact" class="text-13 font-semibold text-fg tabular-nums" aria-hidden="true">
            {{ rating.toFixed(1) }}
        </span>
        <span v-if="count !== null && count !== undefined" class="text-13 text-fg-subtle tabular-nums" aria-hidden="true">
            ({{ count }})
        </span>
    </span>
</template>
