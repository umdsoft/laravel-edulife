<script setup>
/**
 * IconButton — square button with only an icon. `label` is required (aria-label + title).
 *   <IconButton :icon="BellIcon" label="Bildirishnomalar" :badge="3" href="/student/notifications" />
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    icon: { type: [Object, Function], default: null },
    label: { type: String, required: true },
    variant: {
        type: String,
        default: 'ghost',
        validator: (v) => ['ghost', 'secondary', 'primary', 'danger', 'soft'].includes(v),
    },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
    type: { type: String, default: 'button' },
    href: { type: String, default: null },
    disabled: Boolean,
    // number → count pill, true → dot
    badge: { type: [Number, Boolean, String], default: null },
    showTooltip: { type: Boolean, default: true },
});

const emit = defineEmits(['click']);

const sizes = { sm: 'h-8 w-8', md: 'h-10 w-10', lg: 'h-12 w-12' };
const iconSizes = { sm: 'h-4 w-4', md: 'h-5 w-5', lg: 'h-6 w-6' };
const variants = {
    ghost: 'text-fg-subtle hover:bg-surface-2 hover:text-fg',
    secondary: 'border border-border-strong bg-surface text-fg-muted shadow-xs hover:bg-surface-2 hover:text-fg',
    primary: 'bg-brand-600 text-white shadow-xs hover:bg-brand-700',
    danger: 'text-danger hover:bg-danger-50',
    soft: 'bg-brand-50 text-brand hover:bg-brand-100',
};

const showCount = computed(() => typeof props.badge === 'number' || (typeof props.badge === 'string' && props.badge !== ''));
const countText = computed(() => {
    const n = Number(props.badge);
    return Number.isFinite(n) && n > 99 ? '99+' : String(props.badge);
});
const hasBadge = computed(() => (showCount.value ? Number(props.badge) !== 0 : props.badge === true));

const tag = computed(() => (props.href ? Link : 'button'));
const bindings = computed(() =>
    props.href ? { href: props.href } : { type: props.type, disabled: props.disabled },
);

const onClick = (e) => {
    if (props.disabled) return e.preventDefault();
    emit('click', e);
};
</script>

<template>
    <component :is="tag" v-bind="bindings" :aria-label="label" :title="showTooltip ? label : undefined" :class="[
        'focus-ring relative inline-flex shrink-0 items-center justify-center rounded-md transition-colors duration-150',
        sizes[size], variants[variant] || variants.ghost,
        disabled ? 'pointer-events-none opacity-50' : '',
    ]" @click="onClick">
        <component :is="icon" v-if="icon" :class="iconSizes[size]" aria-hidden="true" />
        <slot v-else />
        <span v-if="hasBadge && showCount"
            class="absolute -right-0.5 -top-0.5 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-danger-600 px-1 text-[10px] font-semibold leading-none text-white tabular-nums ring-2 ring-surface">
            {{ countText }}
        </span>
        <span v-else-if="hasBadge" class="absolute right-2 top-2 h-2 w-2 rounded-full bg-danger-600 ring-2 ring-surface" />
    </component>
</template>
