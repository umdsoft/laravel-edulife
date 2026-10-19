<script setup>
import { Link } from '@inertiajs/vue3';
import { RectangleStackIcon } from '@heroicons/vue/24/outline';

defineProps({
    direction: Object,
});
</script>

<template>
    <Link :href="route('student.courses.category', direction.id)"
        class="focus-ring group flex items-center gap-3 rounded-lg border border-border bg-surface p-3 shadow-xs transition-shadow duration-150 ease-out hover:border-border-strong hover:shadow-md">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
            <img v-if="direction.icon_url" :src="direction.icon_url" alt="" class="h-6 w-6 object-contain">
            <RectangleStackIcon v-else class="h-5 w-5" aria-hidden="true" />
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-medium text-fg">{{ direction.name }}</span>
            <span class="block text-xs text-fg-subtle tabular-nums">{{ direction.courses_count || 0 }} ta kurs</span>
        </span>
    </Link>
</template>
