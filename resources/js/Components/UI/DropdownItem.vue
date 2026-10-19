<script setup>
/**
 * DropdownItem — one entry of <Dropdown>.
 *   <DropdownItem :href="route('student.settings.index')" :icon="Cog6ToothIcon">Sozlamalar</DropdownItem>
 *   <DropdownItem href="/logout" method="post" danger :icon="ArrowRightOnRectangleIcon">Chiqish</DropdownItem>
 *   <DropdownItem @click="duplicate">Nusxa olish</DropdownItem>
 *   <DropdownItem divider />
 */
import { computed } from 'vue';
import { MenuItem } from '@headlessui/vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, default: null },
    method: { type: String, default: 'get' },
    data: { type: Object, default: undefined },
    external: Boolean,
    icon: { type: [Object, Function], default: null },
    danger: Boolean,
    disabled: Boolean,
    divider: Boolean,
    // secondary text on the right (shortcut, count)
    meta: { type: String, default: null },
});

const emit = defineEmits(['click']);

const tag = computed(() => {
    if (!props.href) return 'button';
    return props.external ? 'a' : Link;
});
const bindings = computed(() => {
    if (!props.href) return { type: 'button', disabled: props.disabled };
    if (props.external) return { href: props.href, target: '_blank', rel: 'noopener' };
    const b = { href: props.href, method: props.method, data: props.data };
    if (props.method.toLowerCase() !== 'get') b.as = 'button';
    return b;
});
</script>

<template>
    <div v-if="divider" role="separator" class="my-1 h-px bg-border" />
    <MenuItem v-else v-slot="{ active }" :disabled="disabled">
        <component :is="tag" v-bind="bindings" :class="[
            'flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm transition-colors',
            danger ? 'text-danger' : 'text-fg',
            active ? (danger ? 'bg-danger-50' : 'bg-surface-2') : '',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        ]" @click="emit('click', $event)">
            <component :is="icon" v-if="icon" :class="['h-[18px] w-[18px] shrink-0', danger ? 'text-danger' : 'text-fg-subtle']"
                aria-hidden="true" />
            <span class="min-w-0 flex-1 truncate"><slot /></span>
            <span v-if="meta" class="ml-auto text-xs text-fg-subtle tabular-nums">{{ meta }}</span>
        </component>
    </MenuItem>
</template>
