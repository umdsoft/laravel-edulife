<script setup>
import { Head } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import ScoreCard from '@/Components/Teacher/ScoreCard.vue';
import ScoreBreakdown from '@/Components/Teacher/ScoreBreakdown.vue';
import ScoreChart from '@/Components/Teacher/ScoreChart.vue';
import { format } from 'date-fns';
import { uz } from 'date-fns/locale';

defineProps({
    score: Number,
    level: String,
    breakdown: Object,
    recommendations: Array,
    history: Array,
    levelChanges: Array,
    nextLevel: Object,
    commissionRate: Number,
});

const getLevelName = (level) => {
    switch (level) {
        case 'top': return 'TOP';
        case 'featured': return 'FEATURED';
        case 'verified': return 'VERIFIED';
        default: return 'NEW';
    }
};
</script>

<template>
    <TeacherLayout>

        <Head title="Mening Reytingim" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Mening Reytingim</h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Score Card -->
                    <ScoreCard :score="score" :level="level" :next-level="nextLevel"
                        :commission-rate="commissionRate" />

                    <!-- Recommendations -->
                    <div v-if="recommendations.length > 0"
                        class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Tavsiyalar</h3>
                        <div class="space-y-4">
                            <div v-for="(rec, index) in recommendations" :key="index"
                                class="flex items-start p-3 bg-indigo-50 rounded-md border border-indigo-100">
                                <span class="text-xl mr-3">💡</span>
                                <div>
                                    <p class="text-sm font-medium text-indigo-900">{{ rec.message }}</p>
                                    <p class="text-xs text-indigo-700 mt-1">
                                        Hozirgi: <span class="font-bold">{{ rec.current }}</span> • Maqsad: <span
                                            class="font-bold">{{ rec.target }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score History Chart -->
                    <ScoreChart :history="history" />

                    <!-- Level Changes History -->
                    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Daraja O'zgarishlari</h3>
                        <div v-if="levelChanges.length === 0" class="text-center py-4 text-gray-500 text-sm">
                            Hali daraja o'zgarishlari yo'q
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="change in levelChanges" :key="change.id"
                                class="flex items-start border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                        :class="change.new_score > change.old_score ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'">
                                        <svg v-if="change.new_score > change.old_score" class="w-5 h-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ getLevelName(change.old_level) }} &rarr; {{ getLevelName(change.new_level) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ format(new Date(change.created_at), 'dd MMM, yyyy HH:mm', { locale: uz }) }}
                                        • {{ change.old_score }} &rarr; {{ change.new_score }} ball
                                    </p>
                                    <p v-if="change.reason" class="text-xs text-gray-600 mt-1 italic">
                                        "{{ change.reason }}"
                                    </p>
                                    <p v-if="!change.reason" class="text-xs text-gray-400 mt-1">
                                        Avtomatik hisoblash
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Score Breakdown -->
                    <ScoreBreakdown :breakdown="breakdown" />
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
