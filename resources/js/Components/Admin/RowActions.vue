<script setup>
/**
 * RowActions — "⋯" actions menu for table rows.
 *
 * Same item API as <Dropdown> (use <DropdownItem> children), but the panel is teleported to
 * <body> with fixed positioning, so it is never clipped by the table's horizontal scroll box
 * (overflow-x-auto also clips vertically). Opens upwards when there is no room below.
 *
 *   <RowActions label="Amallar">
 *     <DropdownItem :href="route('admin.users.edit', user.id)" :icon="PencilSquareIcon">Tahrirlash</DropdownItem>
 *     <DropdownItem divider />
 *     <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(user)">O'chirish</DropdownItem>
 *   </RowActions>
 */
import { defineComponent, onBeforeUnmount, onMounted, ref } from 'vue';
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import { EllipsisHorizontalIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    // accessible name of the trigger (also shown as a native tooltip)
    label: { type: String, default: 'Amallar' },
    // Tailwind width class for the panel
    width: { type: String, default: 'w-52' },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
});

const OFFSCREEN = { position: 'fixed', top: '-9999px', left: '-9999px' };
const buttonRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({ ...OFFSCREEN });

const elOf = (r) => (r && r.$el ? r.$el : r);

const place = () => {
    const btn = elOf(buttonRef.value);
    const panel = elOf(panelRef.value);
    if (!btn || typeof window === 'undefined') return;
    const rect = btn.getBoundingClientRect();
    const h = panel?.offsetHeight || 0;
    const w = panel?.offsetWidth || 208;
    const gap = 4;
    const margin = 8;
    const below = window.innerHeight - rect.bottom;
    const openUp = h > 0 && below < h + gap + margin && rect.top > below;
    const left = Math.max(margin, Math.min(rect.right - w, window.innerWidth - w - margin));
    const top = openUp ? Math.max(margin, rect.top - h - gap) : rect.bottom + gap;
    panelStyle.value = { position: 'fixed', top: `${top}px`, left: `${left}px` };
};

// Mounted inside the open panel: positions it and follows scroll / resize while open.
const PositionTracker = defineComponent({
    name: 'RowActionsPositionTracker',
    setup() {
        onMounted(() => {
            place();
            window.requestAnimationFrame(place);
            window.addEventListener('scroll', place, true);
            window.addEventListener('resize', place);
        });
        onBeforeUnmount(() => {
            window.removeEventListener('scroll', place, true);
            window.removeEventListener('resize', place);
            panelStyle.value = { ...OFFSCREEN };
        });
        return () => null;
    },
});

const sizes = { sm: 'h-8 w-8', md: 'h-10 w-10' };
</script>

<template>
    <Menu v-slot="{ open, close }" as="div" class="relative inline-block text-left">
        <MenuButton ref="buttonRef" :aria-label="label" :title="label" :class="[
            'focus-ring inline-flex shrink-0 items-center justify-center rounded-md transition-colors duration-150',
            'hover:bg-surface-2 hover:text-fg',
            open ? 'bg-surface-2 text-fg' : 'text-fg-subtle',
            sizes[props.size] || sizes.md,
        ]">
            <EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
        </MenuButton>

        <Teleport to="body">
            <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <MenuItems ref="panelRef" :style="panelStyle" :class="[
                    'z-50 origin-top-right overflow-hidden rounded-md border border-border bg-surface py-1 shadow-md focus:outline-none',
                    width,
                ]">
                    <PositionTracker />
                    <div class="py-0.5">
                        <slot :close="close" />
                    </div>
                </MenuItems>
            </transition>
        </Teleport>
    </Menu>
</template>
