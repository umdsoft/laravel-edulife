<script setup>
/**
 * TopicMap — mastery (0..100) per section with its topics, one subject.
 *   <TopicMap :sections="[{ code, name, score, topics: [{ code, name, score }] }]" />
 * score null = no data yet. Sections open on click (native <details>).
 */
import { masteryTone, masteryLabel, formatPercent } from './mastery';

defineProps({
    sections: { type: Array, default: () => [] },
    compact: Boolean, // sections only, no topic lists
});

const bar = {
    danger: 'bg-danger-600', warning: 'bg-warning-600', brand: 'bg-brand-600', success: 'bg-success-600', neutral: 'bg-surface-3',
};
const text = {
    danger: 'text-danger', warning: 'text-warning', brand: 'text-brand', success: 'text-success', neutral: 'text-fg-subtle',
};
</script>

<template>
    <ul class="divide-y divide-border">
        <li v-for="section in sections" :key="section.code" class="py-3 first:pt-0 last:pb-0">
            <component :is="compact || !section.topics?.length ? 'div' : 'details'" class="group">
                <component :is="compact || !section.topics?.length ? 'div' : 'summary'"
                    class="flex cursor-pointer list-none items-center gap-3 rounded-sm focus-ring [&::-webkit-details-marker]:hidden">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-medium text-fg">{{ section.name }}</span>
                            <span :class="['shrink-0 text-13 font-medium tabular-nums', text[masteryTone(section.score)]]">
                                {{ formatPercent(section.score) }}
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-surface-2" role="progressbar"
                            :aria-label="`${section.name}: ${masteryLabel(section.score)}`" aria-valuemin="0" aria-valuemax="100"
                            :aria-valuenow="section.score ?? undefined">
                            <div :class="['h-full rounded-full transition-[width] duration-200', bar[masteryTone(section.score)]]"
                                :style="{ width: `${section.score ?? 0}%` }" />
                        </div>
                    </div>
                </component>
                <ul v-if="!compact && section.topics?.length" class="mt-3 space-y-2 border-l border-border pl-3">
                    <li v-for="topic in section.topics" :key="topic.code" class="flex items-center justify-between gap-3 text-13">
                        <span class="min-w-0 truncate text-fg-muted">{{ topic.name }}</span>
                        <span :class="['shrink-0 tabular-nums', text[masteryTone(topic.score)]]">{{ formatPercent(topic.score) }}</span>
                    </li>
                </ul>
            </component>
        </li>
    </ul>
</template>
