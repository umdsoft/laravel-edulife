<script setup>
import { computed } from 'vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    attempt: {
        type: Object,
        required: true,
    },
});

const pad = (n) => String(n).padStart(2, '0');
// "2026-10-19T19:48:35.000000Z" → "19.10.2026, 19:48"
const formatDateTime = (v) => {
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const status = computed(() => {
    if (props.attempt.status === 'in_progress') return { variant: 'info', text: 'Jarayonda' };
    if (props.attempt.status === 'expired') return { variant: 'warning', text: 'Vaqt tugadi' };
    return props.attempt.is_passed ? { variant: 'success', text: "O'tdi" } : { variant: 'danger', text: "O'tmadi" };
});
</script>

<template>
    <article class="flex flex-col gap-4 rounded-lg border border-border bg-surface p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-fg">{{ attempt.test?.title || 'Test' }}</h3>
            <p class="mt-0.5 truncate text-13 text-fg-subtle">
                <span v-if="attempt.course?.title">{{ attempt.course.title }} · </span>
                <span class="tabular-nums">{{ formatDateTime(attempt.submitted_at || attempt.created_at) }}</span>
            </p>
        </div>

        <div class="flex items-center justify-between gap-4 sm:justify-end">
            <div class="text-left sm:text-right">
                <div class="text-base font-semibold tabular-nums" :class="attempt.is_passed ? 'text-success' : 'text-fg'">
                    {{ Math.round(attempt.score || 0) }}%
                </div>
                <div v-if="attempt.formatted_time_spent" class="flex items-center gap-1 text-xs text-fg-subtle tabular-nums sm:justify-end">
                    <ClockIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ attempt.formatted_time_spent }}
                </div>
            </div>

            <Badge :variant="status.variant" dot>{{ status.text }}</Badge>

            <Button v-if="attempt.status === 'in_progress'" size="sm" :href="route('student.tests.attempt', attempt.id)">
                Davom etish
            </Button>
            <Button v-else size="sm" variant="secondary" :href="route('student.tests.result', attempt.id)">
                Natija
            </Button>
        </div>
    </article>
</template>
