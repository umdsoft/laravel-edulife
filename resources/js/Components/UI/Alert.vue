<script setup>
/**
 * Alert — inline, persistent message inside the page (use Toast for transient feedback).
 *   <Alert variant="warning" title="Imtihon shanba kuni">Tayyorgarlik uchun 3 kun qoldi.</Alert>
 *   <Alert variant="danger" dismissible @dismiss="hide = true">…</Alert>
 * variant: info | success | warning | danger (alias: error).  #actions slot for buttons/links.
 */
import { computed } from 'vue';
import { CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    variant: { type: String, default: 'info' },
    title: { type: String, default: null },
    dismissible: Boolean,
    icon: { type: [Object, Function, Boolean], default: null },
});
const emit = defineEmits(['dismiss']);

const v = computed(() => (props.variant === 'error' ? 'danger' : props.variant));
const styles = {
    info: 'bg-info-50 border-info-100',
    success: 'bg-success-50 border-success-100',
    warning: 'bg-warning-50 border-warning-100',
    danger: 'bg-danger-50 border-danger-100',
};
const fg = { info: 'text-info', success: 'text-success', warning: 'text-warning', danger: 'text-danger' };
const icons = { info: InformationCircleIcon, success: CheckCircleIcon, warning: ExclamationTriangleIcon, danger: XCircleIcon };
const iconComp = computed(() => (props.icon === false ? null : props.icon || icons[v.value] || icons.info));
const role = computed(() => (v.value === 'danger' || v.value === 'warning' ? 'alert' : 'status'));
</script>

<template>
    <div :role="role" :class="['flex gap-3 rounded-md border p-4 text-sm', styles[v] || styles.info]">
        <component :is="iconComp" v-if="iconComp" :class="['mt-0.5 h-5 w-5 shrink-0', fg[v] || fg.info]" aria-hidden="true" />
        <div class="min-w-0 flex-1">
            <p v-if="title" :class="['font-semibold', fg[v] || fg.info]">{{ title }}</p>
            <div :class="['text-fg-muted', title ? 'mt-1' : '']">
                <slot />
            </div>
            <div v-if="$slots.actions" class="mt-3 flex flex-wrap gap-2">
                <slot name="actions" />
            </div>
        </div>
        <button v-if="dismissible" type="button"
            class="focus-ring -m-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-fg-subtle hover:text-fg"
            aria-label="Yopish" @click="emit('dismiss')">
            <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>
</template>
