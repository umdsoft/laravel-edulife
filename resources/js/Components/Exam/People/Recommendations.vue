<script setup>
/**
 * Recommendations — "keyingi hafta uchun" topic list.
 *   <Recommendations :items="[{ code, name, subject, section, score, reason }]" />
 */
import { Badge, EmptyState } from '@/Components/UI';
import { LightBulbIcon } from '@heroicons/vue/24/outline';
import { formatPercent, REASON_LABELS, SUBJECT_NAMES } from './mastery';

defineProps({
    items: { type: Array, default: () => [] },
});

const reasonVariant = { weak: 'danger', improve: 'warning', new: 'info' };
</script>

<template>
    <ol v-if="items.length" class="space-y-3">
        <li v-for="(item, i) in items" :key="item.code" class="flex items-start gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-13 font-semibold text-brand tabular-nums">
                {{ i + 1 }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-fg">{{ item.name }}</p>
                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-fg-subtle">
                    <Badge v-if="item.subject" :variant="item.subject === 'physics' ? 'physics' : 'math'" size="sm">
                        {{ SUBJECT_NAMES[item.subject] || item.subject }}
                    </Badge>
                    <span v-if="item.section">{{ item.section }}</span>
                    <span v-if="item.score !== null && item.score !== undefined" class="tabular-nums">· {{ formatPercent(item.score) }}</span>
                </p>
            </div>
            <Badge :variant="reasonVariant[item.reason] || 'neutral'" size="sm" class="shrink-0">
                {{ REASON_LABELS[item.reason] || '' }}
            </Badge>
        </li>
    </ol>
    <EmptyState v-else :icon="LightBulbIcon" size="sm" title="Tavsiyalar hali yo'q"
        description="Qabul testidan keyin mavzular bo'yicha tavsiyalar paydo bo'ladi." />
</template>
