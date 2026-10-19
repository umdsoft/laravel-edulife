<script setup>
/**
 * Dropdown / menu (Headless UI Menu → full keyboard support: Enter/Space/↑/↓/Home/End/Escape,
 * type-ahead, click outside, focus return).
 *
 *   <Dropdown align="right" width="w-56">
 *     <template #trigger="{ open }">
 *       <button class="...">Amallar</button>        ← exactly ONE element
 *     </template>
 *     <template #header>…optional non-interactive header…</template>
 *     <DropdownItem :href="route('x.edit', id)" :icon="PencilIcon">Tahrirlash</DropdownItem>
 *     <DropdownItem divider />
 *     <DropdownItem danger :icon="TrashIcon" @click="destroy">O'chirish</DropdownItem>
 *   </Dropdown>
 *
 * Without a #trigger slot, a secondary button with `label` and a chevron is rendered.
 */
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';

defineProps({
    label: { type: String, default: 'Amallar' },
    align: { type: String, default: 'right', validator: (v) => ['left', 'right'].includes(v) },
    // Tailwind width class for the panel
    width: { type: String, default: 'w-56' },
    // open upwards (e.g. sidebar footer)
    up: Boolean,
});
</script>

<template>
    <Menu v-slot="{ open, close }" as="div" class="relative inline-block text-left">
        <MenuButton v-if="$slots.trigger" as="template">
            <slot name="trigger" :open="open" />
        </MenuButton>
        <MenuButton v-else
            class="focus-ring inline-flex h-10 items-center gap-1.5 rounded-md border border-border-strong bg-surface px-3 text-sm font-medium text-fg shadow-xs hover:bg-surface-2">
            {{ label }}
            <ChevronDownIcon :class="['h-4 w-4 text-fg-subtle transition-transform', open ? 'rotate-180' : '']"
                aria-hidden="true" />
        </MenuButton>

        <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <MenuItems :class="[
                'absolute z-50 overflow-hidden rounded-md border border-border bg-surface py-1 shadow-md focus:outline-none',
                width,
                align === 'right' ? 'right-0' : 'left-0',
                up ? 'bottom-full mb-2 origin-bottom' : 'mt-2',
                !up && align === 'right' ? 'origin-top-right' : '',
                !up && align === 'left' ? 'origin-top-left' : '',
            ]">
                <div v-if="$slots.header" class="border-b border-border px-3 py-2.5">
                    <slot name="header" :close="close" />
                </div>
                <div class="py-0.5">
                    <slot :close="close" />
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
