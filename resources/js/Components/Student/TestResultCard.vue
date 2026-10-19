<script setup>
import { computed } from 'vue';
import { CheckCircleIcon, XCircleIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import { ClipboardDocumentListIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import ProgressRing from '@/Components/UI/ProgressRing.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    attempt: {
        type: Object,
        required: true,
    },
    passRate: {
        type: Number,
        default: 0,
    },
});

const isPassed = computed(() => props.attempt.is_passed);
const score = computed(() => Math.round(props.attempt.score || 0));
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
        <div class="px-6 pb-6 pt-8 text-center sm:px-10">
            <ProgressRing :value="score" :size="132" :stroke="10" :tone="isPassed ? 'success' : 'danger'" :show-value="false"
                class="mx-auto" :label="`Natija ${score}%`">
                <span class="text-3xl font-semibold tracking-tight text-fg tabular-nums">{{ score }}%</span>
            </ProgressRing>

            <div class="mt-5 flex justify-center">
                <Badge :variant="isPassed ? 'success' : 'danger'" :icon="isPassed ? CheckCircleIcon : XCircleIcon">
                    {{ isPassed ? "Testdan o'tdingiz" : "Testdan o'tmadingiz" }}
                </Badge>
            </div>
            <p class="mt-2 text-sm text-fg-muted">
                O'tish balli — <span class="font-medium text-fg tabular-nums">{{ passRate }}%</span>
            </p>

            <div v-if="attempt.xp_awarded"
                class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-13 font-semibold text-brand tabular-nums">
                <SparklesIcon class="h-4 w-4" aria-hidden="true" />
                +{{ attempt.xp_amount }} XP
            </div>
        </div>

        <dl class="grid grid-cols-2 border-t border-border sm:grid-cols-4">
            <div class="border-b border-r border-border px-4 py-4 text-center sm:border-b-0">
                <dt class="text-xs text-fg-subtle">To'g'ri</dt>
                <dd class="mt-1 text-lg font-semibold text-success tabular-nums">
                    {{ attempt.correct_answers }}<span class="text-sm font-normal text-fg-subtle"> / {{ attempt.total_questions }}</span>
                </dd>
            </div>
            <div class="border-b border-border px-4 py-4 text-center sm:border-b-0 sm:border-r">
                <dt class="text-xs text-fg-subtle">Noto'g'ri</dt>
                <dd class="mt-1 text-lg font-semibold text-danger tabular-nums">{{ attempt.wrong_answers }}</dd>
            </div>
            <div class="border-r border-border px-4 py-4 text-center">
                <dt class="text-xs text-fg-subtle">Sarflangan vaqt</dt>
                <dd class="mt-1 text-lg font-semibold text-fg tabular-nums">{{ attempt.formatted_time_spent || '—' }}</dd>
            </div>
            <div class="px-4 py-4 text-center">
                <dt class="text-xs text-fg-subtle">O'tish balli</dt>
                <dd class="mt-1 text-lg font-semibold text-fg tabular-nums">{{ passRate }}%</dd>
            </div>
        </dl>

        <div class="flex flex-col-reverse gap-2 border-t border-border bg-surface-2/50 px-6 py-4 sm:flex-row sm:justify-center">
            <Button v-if="attempt.test?.show_answers_after" variant="secondary" :icon-left="ClipboardDocumentListIcon"
                :href="route('student.tests.review', attempt.id)">
                Javoblarni ko'rish
            </Button>
            <Button :href="route('student.tests.index', attempt.course_id)" :icon-right="ArrowRightIcon">
                Davom etish
            </Button>
        </div>
    </div>
</template>
