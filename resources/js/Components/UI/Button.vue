<script setup>
/**
 * Button — the only primary/secondary action style in the app.
 *
 * Backward compatible with the old API: type, variant (primary|secondary|ghost|danger),
 * loading, disabled, @click, default slot.
 *
 * New: size (sm|md|lg), variant "link" | "soft", block, href (renders Inertia <Link>,
 * or <a> when `external`), method/as/data/preserveScroll (Link passthrough),
 * icon components (`iconLeft`, `iconRight`) or slots (#leading, #trailing).
 */
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';
import Spinner from './Spinner.vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost', 'danger', 'link', 'soft'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    loading: Boolean,
    disabled: Boolean,
    block: Boolean,
    href: { type: String, default: null },
    external: Boolean,
    method: { type: String, default: 'get' },
    data: { type: Object, default: undefined },
    preserveScroll: Boolean,
    iconLeft: { type: [Object, Function], default: null },
    iconRight: { type: [Object, Function], default: null },
});

const emit = defineEmits(['click']);
const slots = useSlots();

const isDisabled = computed(() => props.disabled || props.loading);

const tag = computed(() => {
    if (!props.href) return 'button';
    return props.external ? 'a' : Link;
});

const sizeClasses = {
    sm: 'h-8 px-3 text-13 gap-1.5',
    md: 'h-10 px-4 text-sm gap-2',
    lg: 'h-12 px-5 text-base gap-2',
};
const iconSize = { sm: 'h-4 w-4', md: 'h-[18px] w-[18px]', lg: 'h-5 w-5' };

const variantClasses = {
    primary: 'bg-brand-600 text-white shadow-xs hover:bg-brand-700 active:bg-brand-700',
    secondary: 'border border-border-strong bg-surface text-fg shadow-xs hover:bg-surface-2 active:bg-surface-3',
    ghost: 'text-fg-muted hover:bg-surface-2 hover:text-fg active:bg-surface-3',
    danger: 'bg-danger-600 text-white shadow-xs hover:bg-danger-700 active:bg-danger-700',
    soft: 'bg-brand-50 text-brand hover:bg-brand-100',
    link: 'text-brand underline-offset-4 hover:underline !h-auto !px-0',
};

const classes = computed(() => [
    'focus-ring inline-flex shrink-0 select-none items-center justify-center whitespace-nowrap rounded-md font-medium',
    'transition-colors duration-150 ease-out',
    sizeClasses[props.size],
    variantClasses[props.variant] || variantClasses.primary,
    props.block ? 'w-full' : '',
    isDisabled.value ? 'pointer-events-none cursor-not-allowed opacity-50' : 'cursor-pointer',
]);

const bindings = computed(() => {
    if (!props.href) {
        return { type: props.type, disabled: isDisabled.value, 'aria-busy': props.loading || undefined };
    }
    const common = { href: props.href, 'aria-disabled': isDisabled.value || undefined };
    if (props.external) return { ...common, rel: 'noopener', target: '_blank' };
    const link = { ...common, method: props.method, data: props.data, preserveScroll: props.preserveScroll };
    if (props.method && props.method.toLowerCase() !== 'get') link.as = 'button';
    return link;
});

const onClick = (event) => {
    if (isDisabled.value) {
        event.preventDefault();
        return;
    }
    emit('click', event);
};

const hasLeading = computed(() => props.loading || !!props.iconLeft || !!slots.leading);
</script>

<template>
    <component :is="tag" v-bind="bindings" :class="classes" @click="onClick">
        <span v-if="hasLeading" class="inline-flex shrink-0 items-center" aria-hidden="true">
            <Spinner v-if="loading" :size="iconSize[size]" />
            <component :is="iconLeft" v-else-if="iconLeft" :class="iconSize[size]" />
            <slot v-else name="leading" />
        </span>
        <slot />
        <span v-if="(iconRight || $slots.trailing) && !loading" class="inline-flex shrink-0 items-center"
            aria-hidden="true">
            <component :is="iconRight" v-if="iconRight" :class="iconSize[size]" />
            <slot v-else name="trailing" />
        </span>
    </component>
</template>
