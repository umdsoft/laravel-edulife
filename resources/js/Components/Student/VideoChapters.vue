<script setup>
import { computed } from 'vue';
import { formatClock } from './format.js';

const props = defineProps({
    chapters: {
        type: Array,
        required: true,
    },
    currentTime: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['seek']);

// The chapter being watched = the last one whose timestamp has passed
const activeId = computed(() => {
    let active = null;
    [...props.chapters]
        .sort((a, b) => a.timestamp - b.timestamp)
        .forEach((c) => { if (props.currentTime >= c.timestamp) active = c.id; });
    return active;
});
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-border bg-surface" aria-labelledby="video-chapters-title">
        <h3 id="video-chapters-title" class="border-b border-border px-4 py-3 text-sm font-semibold text-fg">Video bo'limlari</h3>
        <ol class="divide-y divide-border">
            <li v-for="chapter in chapters" :key="chapter.id">
                <button type="button" @click="emit('seek', chapter.timestamp)"
                    :aria-current="activeId === chapter.id ? 'true' : undefined" :class="[
                        'focus-ring flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors duration-150',
                        activeId === chapter.id ? 'bg-brand-50' : 'hover:bg-surface-2/70',
                    ]">
                    <span :class="[
                        'w-14 shrink-0 rounded-sm px-1.5 py-0.5 text-center text-xs font-medium tabular-nums',
                        activeId === chapter.id ? 'bg-brand-600 text-white' : 'bg-surface-2 text-fg-muted',
                    ]">
                        {{ formatClock(chapter.timestamp) }}
                    </span>
                    <span :class="['line-clamp-1 text-sm', activeId === chapter.id ? 'font-medium text-brand' : 'text-fg']">
                        {{ chapter.title }}
                    </span>
                </button>
            </li>
        </ol>
    </section>
</template>
