<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import TestHistoryItem from '@/Components/Student/TestHistoryItem.vue';

defineProps({
    attempts: Object,
    stats: Object
});
</script>

<template>
    <StudentLayout>

        <Head title="Test Tarixi" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                Mening Test Tarixim
            </h1>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Jami urinishlar</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_attempts }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Muvaffaqiyatli</div>
                    <div class="text-2xl font-bold text-green-600">{{ stats.passed }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Muvaffaqiyatsiz</div>
                    <div class="text-2xl font-bold text-red-600">{{ stats.failed }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">O'rtacha ball</div>
                    <div class="text-2xl font-bold text-blue-600">{{ Math.round(stats.average_score) }}%</div>
                </div>
            </div>

            <!-- List -->
            <div class="space-y-4">
                <TestHistoryItem v-for="attempt in attempts.data" :key="attempt.id" :attempt="attempt" />

                <div v-if="attempts.data.length === 0"
                    class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <p class="text-gray-500 dark:text-gray-400">Hozircha testlar tarixi mavjud emas.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="attempts.links.length > 3" class="mt-6 flex justify-center">
                <!-- Pagination component would go here -->
            </div>
        </div>
    </StudentLayout>
</template>
