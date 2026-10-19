<script setup>
/**
 * Card — the standard content container.
 *   <Card title="Natijalar" description="Oxirgi 7 kun">
 *     <template #actions><Button size="sm" variant="secondary">Barchasi</Button></template>
 *     ...content...
 *     <template #footer>...</template>
 *   </Card>
 * padding: none | sm | md (default) | lg.  `interactive` adds hover elevation (for clickable cards).
 * `href` renders the whole card as an Inertia Link.
 */
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    padding: { type: String, default: 'md', validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v) },
    as: { type: String, default: 'div' },
    href: { type: String, default: null },
    interactive: Boolean,
    // Draw a divider under the header / above the footer
    divided: { type: Boolean, default: true },
});

const slots = useSlots();
const pad = { none: '', sm: 'p-4', md: 'p-5 sm:p-6', lg: 'p-6 sm:p-8' };
const padX = { none: 'px-5 sm:px-6', sm: 'px-4', md: 'px-5 sm:px-6', lg: 'px-6 sm:px-8' };

const hasHeader = computed(() => !!(props.title || props.description || slots.header || slots.actions));
const tag = computed(() => (props.href ? Link : props.as));
</script>

<template>
    <component :is="tag" :href="href || undefined" :class="[
        'block rounded-lg border border-border bg-surface text-fg shadow-xs',
        (interactive || href) ? 'focus-ring transition-shadow duration-150 hover:border-border-strong hover:shadow-md' : '',
    ]">
        <div v-if="hasHeader"
            :class="['flex items-start justify-between gap-4 py-4', padX[padding] || padX.md, divided ? 'border-b border-border' : 'pb-0']">
            <slot name="header">
                <div class="min-w-0">
                    <h3 v-if="title" class="text-base font-semibold text-fg">{{ title }}</h3>
                    <p v-if="description" class="mt-0.5 text-13 text-fg-subtle">{{ description }}</p>
                </div>
            </slot>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div :class="pad[padding]">
            <slot />
        </div>

        <div v-if="$slots.footer"
            :class="['py-3', padX[padding] || padX.md, divided ? 'border-t border-border bg-surface-2/50 rounded-b-lg' : '']">
            <slot name="footer" />
        </div>
    </component>
</template>
