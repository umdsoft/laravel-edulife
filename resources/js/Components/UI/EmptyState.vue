<script setup>
/**
 * EmptyState — shown when a list / section has nothing to display.
 *   <EmptyState :icon="BookOpenIcon" title="Hali kurs yo'q" description="Birinchi kursingizni yarating.">
 *     <Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Yangi kurs</Button>
 *   </EmptyState>
 * size: sm | md (default) | lg.  Default slot = actions.
 */
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
    icon: { type: [Object, Function], default: null },
    title: { type: String, required: true },
    description: { type: String, default: null },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
    // draw a dashed outline box (for empty cards / drop zones)
    bordered: Boolean,
});
</script>

<template>
    <div :class="[
        'flex flex-col items-center text-center',
        size === 'sm' ? 'gap-2 py-4' : size === 'lg' ? 'gap-4 py-16' : 'gap-3 py-10',
        bordered ? 'rounded-lg border border-dashed border-border-strong px-6' : '',
    ]">
        <div :class="['flex items-center justify-center rounded-full bg-surface-2 text-fg-subtle', size === 'sm' ? 'h-10 w-10' : 'h-12 w-12']">
            <slot name="icon">
                <component :is="icon || InboxIcon" :class="size === 'sm' ? 'h-5 w-5' : 'h-6 w-6'" aria-hidden="true" />
            </slot>
        </div>
        <div class="max-w-sm">
            <h3 :class="['font-semibold text-fg', size === 'sm' ? 'text-sm' : 'text-base']">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-fg-muted">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="mt-1 flex flex-wrap items-center justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
