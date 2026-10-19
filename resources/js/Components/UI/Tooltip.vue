<script setup>
/**
 * Tooltip — short hint on hover / keyboard focus (Escape hides). Not for essential info.
 *   <Tooltip text="Haftalik reyting">
 *     <IconButton :icon="TrophyIcon" label="Reyting" />
 *   </Tooltip>
 * The default slot receives { id } — bind :aria-describedby="id" on the trigger for screen readers
 * (IconButton already has an aria-label, so it's optional there).
 * placement: top (default) | bottom | left | right
 */
import { onBeforeUnmount, ref, useId } from 'vue';

const props = defineProps({
    text: { type: String, required: true },
    placement: { type: String, default: 'top', validator: (v) => ['top', 'bottom', 'left', 'right'].includes(v) },
    delay: { type: Number, default: 250 },
    disabled: Boolean,
});

const open = ref(false);
const id = `tt-${useId()}`;
let timer = null;

const show = () => {
    if (props.disabled) return;
    clearTimeout(timer);
    timer = setTimeout(() => (open.value = true), props.delay);
};
const hide = () => {
    clearTimeout(timer);
    open.value = false;
};
const onKey = (e) => {
    if (e.key === 'Escape') hide();
};
onBeforeUnmount(() => clearTimeout(timer));

const pos = {
    top: 'bottom-full left-1/2 mb-2 -translate-x-1/2',
    bottom: 'top-full left-1/2 mt-2 -translate-x-1/2',
    left: 'right-full top-1/2 mr-2 -translate-y-1/2',
    right: 'left-full top-1/2 ml-2 -translate-y-1/2',
};
</script>

<template>
    <span class="relative inline-flex" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide"
        @keydown="onKey">
        <slot :id="id" />
        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition duration-75 ease-in" leave-to-class="opacity-0">
            <span v-if="open" :id="id" role="tooltip" :class="[
                'pointer-events-none absolute z-tooltip w-max max-w-[16rem] rounded-sm bg-fg px-2 py-1 text-xs font-medium text-surface shadow-md',
                pos[placement],
            ]">
                {{ text }}
            </span>
        </transition>
    </span>
</template>
