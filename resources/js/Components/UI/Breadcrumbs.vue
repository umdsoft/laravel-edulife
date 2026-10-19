<script setup>
/**
 * Breadcrumbs — items: [{ label, href? }]; the last item is the current page.
 *   <Breadcrumbs :items="[{ label: 'Kurslar', href: route('student.courses.index') }, { label: course.title }]" />
 */
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/20/solid';

defineProps({
    items: { type: Array, required: true },
    // href for a leading home icon (optional)
    home: { type: String, default: null },
});
</script>

<template>
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1 text-13">
            <li v-if="home" class="flex items-center">
                <Link :href="home" class="focus-ring rounded-sm text-fg-subtle hover:text-fg" aria-label="Bosh sahifa">
                    <HomeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
            </li>
            <li v-for="(item, i) in items" :key="i" class="flex min-w-0 items-center gap-1">
                <ChevronRightIcon v-if="i > 0 || home" class="h-4 w-4 shrink-0 text-fg-subtle/60" aria-hidden="true" />
                <Link v-if="item.href && i < items.length - 1" :href="item.href"
                    class="focus-ring truncate rounded-sm text-fg-subtle hover:text-fg">
                    {{ item.label }}
                </Link>
                <span v-else :aria-current="i === items.length - 1 ? 'page' : undefined"
                    :class="['truncate', i === items.length - 1 ? 'font-medium text-fg-muted' : 'text-fg-subtle']">
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>
