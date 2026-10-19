<script setup>
/** RatingStars — read-only 0–5 star rating.  <RatingStars :value="review.rating" size="sm" /> */
import { computed } from 'vue';
import { StarIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    size: { type: String, default: 'sm', validator: (v) => ['xs', 'sm', 'md'].includes(v) },
});

const rounded = computed(() => Math.round(Number(props.value) || 0));
const sizes = { xs: 'h-3.5 w-3.5', sm: 'h-4 w-4', md: 'h-5 w-5' };
</script>

<template>
    <span class="inline-flex items-center gap-px" role="img" :aria-label="`Reyting: ${rounded} / 5`">
        <StarIcon v-for="i in 5" :key="i" :class="[sizes[size], i <= rounded ? 'text-warning' : 'text-surface-3']"
            aria-hidden="true" />
    </span>
</template>
