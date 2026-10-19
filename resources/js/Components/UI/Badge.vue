<script setup>
/**
 * Badge — small status / category label.
 *   <Badge variant="success" dot>Faol</Badge>
 *   <Badge variant="physics">Fizika</Badge>
 * variant: neutral | brand | success | warning | danger | info | math | physics
 *   (aliases accepted: gray → neutral, primary → brand, error → danger)
 * size: sm | md.  `dot` shows a coloured dot; `icon` takes a heroicon component.
 */
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'neutral' },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
    dot: Boolean,
    icon: { type: [Object, Function], default: null },
    // solid = filled with white text (for counts on dark areas)
    solid: Boolean,
});

const ALIASES = { gray: 'neutral', secondary: 'neutral', default: 'neutral', primary: 'brand', error: 'danger', purple: 'brand', green: 'success', red: 'danger', yellow: 'warning', blue: 'info' };
const v = computed(() => ALIASES[props.variant] || props.variant);

const soft = {
    neutral: 'bg-surface-2 text-fg-muted ring-border',
    brand: 'bg-brand-50 text-brand ring-brand-100',
    success: 'bg-success-50 text-success ring-success-100',
    warning: 'bg-warning-50 text-warning ring-warning-100',
    danger: 'bg-danger-50 text-danger ring-danger-100',
    info: 'bg-info-50 text-info ring-info-100',
    math: 'bg-math-50 text-math ring-math-100',
    physics: 'bg-physics-50 text-physics ring-physics-100',
};
const solidMap = {
    neutral: 'bg-fg-muted text-white ring-transparent',
    brand: 'bg-brand-600 text-white ring-transparent',
    success: 'bg-success-600 text-white ring-transparent',
    warning: 'bg-warning-600 text-white ring-transparent',
    danger: 'bg-danger-600 text-white ring-transparent',
    info: 'bg-info-600 text-white ring-transparent',
    math: 'bg-math-600 text-white ring-transparent',
    physics: 'bg-physics-600 text-white ring-transparent',
};
const dotColor = {
    neutral: 'bg-fg-subtle', brand: 'bg-brand-600', success: 'bg-success-600', warning: 'bg-warning-600',
    danger: 'bg-danger-600', info: 'bg-info-600', math: 'bg-math-600', physics: 'bg-physics-600',
};

const classes = computed(() => [
    'inline-flex max-w-full items-center gap-1 whitespace-nowrap rounded-full font-medium ring-1 ring-inset',
    props.size === 'sm' ? 'px-2 py-0.5 text-[11px] leading-4' : 'px-2.5 py-0.5 text-xs leading-5',
    (props.solid ? solidMap : soft)[v.value] || soft.neutral,
]);
</script>

<template>
    <span :class="classes">
        <span v-if="dot" :class="['h-1.5 w-1.5 shrink-0 rounded-full', solid ? 'bg-white' : (dotColor[v] || dotColor.neutral)]"
            aria-hidden="true" />
        <component :is="icon" v-if="icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        <span class="truncate"><slot /></span>
    </span>
</template>
