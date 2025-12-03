<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    attempt: {
        type: Object,
        required: true
    }
});

const statusColor = computed(() => {
    if (props.attempt.status === 'in_progress') return 'bg-blue-100 text-blue-800';
    if (!props.attempt.is_passed) return 'bg-red-100 text-red-800';
    return 'bg-green-100 text-green-800';
});

const statusText = computed(() => {
    if (props.attempt.status === 'in_progress') return 'Jarayonda';
    if (props.attempt.status === 'expired') return 'Vaqt tugadi';
    return props.attempt.is_passed ? "O'tdi" : "O'tmadi";
});
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h4 class="font-medium text-gray-900 dark:text-white">
                {{ attempt.test?.title || 'Test' }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ attempt.course?.title }} • {{ attempt.created_at }}
            </p>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-sm font-bold" :class="attempt.is_passed ? 'text-green-600' : 'text-red-600'">
                    {{ Math.round(attempt.score) }}%
                </div>
                <div class="text-xs text-gray-500">
                    {{ attempt.formatted_time_spent }}
                </div>
            </div>

            <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="statusColor">
                {{ statusText }}
            </span>

            <Link v-if="attempt.status === 'in_progress'" :href="route('student.tests.attempt', attempt.id)"
                class="text-purple-600 hover:text-purple-700 text-sm font-medium">
            Davom etish
            </Link>
            <Link v-else :href="route('student.tests.result', attempt.id)"
                class="text-gray-500 hover:text-gray-700 text-sm">
            Natija
            </Link>
        </div>
    </div>
</template>
