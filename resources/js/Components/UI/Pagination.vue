<template>
    <div v-if="data.links && data.links.length > 3"
        class="flex flex-col-reverse items-center justify-between gap-3 border-t border-border bg-surface px-4 py-3 sm:flex-row sm:px-6">
        <!-- Info -->
        <p v-if="data.total !== undefined" class="text-13 text-fg-subtle">
            <span class="tabular-nums text-fg">{{ data.from ?? 0 }}–{{ data.to ?? 0 }}</span>
            ko'rsatilmoqda, jami
            <span class="tabular-nums text-fg">{{ data.total }}</span>
            ta
        </p>
        <span v-else />

        <!-- Links -->
        <nav class="flex items-center gap-1" aria-label="Sahifalar">
            <template v-for="(link, index) in data.links" :key="index">
                <!-- Prev / Next -->
                <component :is="link.url ? 'button' : 'span'" v-if="index === 0 || index === data.links.length - 1"
                    :type="link.url ? 'button' : undefined" :disabled="!link.url || undefined"
                    :aria-label="index === 0 ? 'Oldingi sahifa' : 'Keyingi sahifa'" :class="[
                        'focus-ring inline-flex h-9 items-center gap-1 rounded-md px-2.5 text-sm font-medium transition-colors',
                        link.url ? 'text-fg-muted hover:bg-surface-2 hover:text-fg' : 'cursor-not-allowed text-fg-subtle/50',
                    ]" @click="link.url && changePage(link.url)">
                    <ChevronLeftIcon v-if="index === 0" class="h-4 w-4" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ index === 0 ? 'Oldingi' : 'Keyingi' }}</span>
                    <ChevronRightIcon v-if="index !== 0" class="h-4 w-4" aria-hidden="true" />
                </component>

                <!-- Ellipsis -->
                <span v-else-if="!link.url" class="hidden h-9 min-w-9 items-center justify-center px-1 text-sm text-fg-subtle sm:inline-flex"
                    aria-hidden="true" v-html="link.label" />

                <!-- Page numbers (desktop: all; mobile: current only) -->
                <button v-else type="button" :aria-current="link.active ? 'page' : undefined" :class="[
                    'focus-ring h-9 min-w-[2.25rem] items-center justify-center rounded-md px-2 text-sm font-medium tabular-nums transition-colors',
                    link.active ? 'inline-flex bg-brand-600 text-white shadow-xs' : 'hidden text-fg-muted hover:bg-surface-2 hover:text-fg sm:inline-flex',
                ]" @click="changePage(link.url)" v-html="link.label" />
            </template>
        </nav>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    data: {
        type: Object,
        required: true,
        // Laravel paginator object with links, from, to, total
    },
});

const emit = defineEmits(['page-change']);

const changePage = (url) => {
    if (!url) return;

    // Extract page number from URL
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get('page');

    emit('page-change', page);

    // Use Inertia to navigate (preserves search/filters)
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
