<script setup>
/**
 * FilterBar — the row of search / filter controls above an admin table.
 *   <FilterBar :active="hasFilters" @reset="resetFilters">
 *     <SearchInput v-model="filters.search" class="sm:w-72" … />
 *     <Select v-model="filters.status" :options="statusOptions" class="sm:w-48" @change="filter" />
 *     <template #meta>{{ users.total }} ta</template>
 *   </FilterBar>
 * `active` shows a "Tozalash" button that emits `reset`.
 */
import { XMarkIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';

defineProps({
    active: Boolean,
    label: { type: String, default: 'Filtrlar' },
});

defineEmits(['reset']);
</script>

<template>
    <div role="search" :aria-label="label"
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <slot />
        <div v-if="active || $slots.meta" class="flex items-center gap-3 sm:ml-auto">
            <span v-if="$slots.meta" class="text-13 text-fg-subtle tabular-nums">
                <slot name="meta" />
            </span>
            <Button v-if="active" variant="ghost" size="sm" :icon-left="XMarkIcon" @click="$emit('reset')">
                Tozalash
            </Button>
        </div>
    </div>
</template>
