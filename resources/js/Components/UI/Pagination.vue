<template>
    <div v-if="data.links && data.links.length > 3"
        class="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-100">
        <!-- Info -->
        <div class="text-sm text-gray-500">
            Ko'rsatilmoqda
            <span class="font-medium text-gray-900">{{ data.from }}</span>
            dan
            <span class="font-medium text-gray-900">{{ data.to }}</span>
            gacha, jami
            <span class="font-medium text-gray-900">{{ data.total }}</span>
            ta
        </div>

        <!-- Pagination Links -->
        <nav class="flex items-center gap-1">
            <component :is="link.url ? 'button' : 'span'" v-for="(link, index) in data.links" :key="index"
                @click="link.url && changePage(link.url)" :disabled="!link.url" :class="[
                    'px-3 py-2 text-sm font-medium rounded-lg transition',
                    link.active
                        ? 'bg-primary text-white'
                        : link.url
                            ? 'text-gray-700 hover:bg-gray-100'
                            : 'text-gray-400 cursor-not-allowed',
                ]" v-html="link.label" />
        </nav>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

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
    const urlObj = new URL(url);
    const page = urlObj.searchParams.get('page');

    emit('page-change', page);

    // Use Inertia to navigate (preserves search/filters)
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
