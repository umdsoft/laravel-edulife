<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    attempt: {
        type: Object,
        required: true
    },
    passRate: {
        type: Number,
        default: 0
    }
});

const isPassed = computed(() => props.attempt.is_passed);
const score = computed(() => Math.round(props.attempt.score));

const statusColor = computed(() => isPassed.value ? 'text-green-600' : 'text-red-600');
const statusBg = computed(() => isPassed.value ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30');
const statusIcon = computed(() => isPassed.value ? '✅' : '❌');
const statusText = computed(() => isPassed.value ? "O'TDINGIZ" : "O'TMADINGIZ");
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-6" :class="statusBg">
                <span class="text-4xl">{{ statusIcon }}</span>
            </div>

            <h2 class="text-3xl font-bold mb-2" :class="statusColor">
                {{ score }}%
            </h2>

            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
                {{ statusText }}
            </h3>

            <div v-if="attempt.xp_awarded"
                class="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold mb-6">
                +{{ attempt.xp_amount }} XP 🌟
            </div>

            <div
                class="grid grid-cols-2 gap-4 max-w-md mx-auto text-left bg-gray-50 dark:bg-gray-700/50 p-6 rounded-lg">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">To'g'ri javoblar</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ attempt.correct_answers }} / {{
                        attempt.total_questions }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Noto'g'ri javoblar</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ attempt.wrong_answers }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sarflangan vaqt</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ attempt.formatted_time_spent }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'tish balli</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ passRate }}%</p>
                </div>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <Link v-if="attempt.test?.show_answers_after" :href="route('student.tests.review', attempt.id)"
                    class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                📝 Javoblarni ko'rish
                </Link>

                <Link :href="route('student.tests.index', attempt.course_id)"
                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                → Davom etish
                </Link>
            </div>
        </div>
    </div>
</template>
