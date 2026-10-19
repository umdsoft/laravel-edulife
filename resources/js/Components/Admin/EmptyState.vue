<script setup>
/**
 * Admin EmptyState — kept for backward compatibility; renders the UI kit EmptyState.
 *   <EmptyState :icon="BookOpenIcon" title="Kurslar yo'q" description="…"><Button …/></EmptyState>
 * `icon` takes a heroicon component. Legacy emoji strings are ignored (no emoji as UI icons).
 */
import { computed } from 'vue';
import UiEmptyState from '@/Components/UI/EmptyState.vue';

const props = defineProps({
    icon: { type: [String, Object, Function], default: null },
    title: { type: String, required: true },
    description: String,
    size: { type: String, default: 'md' },
    bordered: Boolean,
});

const iconComponent = computed(() => (props.icon && typeof props.icon !== 'string' ? props.icon : null));
</script>

<template>
    <UiEmptyState :icon="iconComponent" :title="title" :description="description || null" :size="size"
        :bordered="bordered">
        <template v-if="$slots.default" #default>
            <slot />
        </template>
    </UiEmptyState>
</template>
