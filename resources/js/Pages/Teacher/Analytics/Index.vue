<script setup>
import { Head } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import StatsCard from '@/Components/Teacher/StatsCard.vue';

defineProps({
    stats: Object,
    coursePerformance: Array,
    dailyEnrollments: Array,
    trafficSources: Array,
});
</script>

<template>

    <Head title="Statistika" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Statistika</h2>
        </template>

        <div class="space-y-6">
            <!-- Overview Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatsCard title="Jami ko'rishlar" :value="stats.total_views" icon="👁" />
                <StatsCard title="Yozilishlar" :value="stats.total_enrollments" icon="👥" />
                <StatsCard title="Tugallash ko'rsatkichi" :value="`${stats.completion_rate}%`" icon="✅" />
                <StatsCard title="Ko'rish vaqti (soat)" :value="Math.round(stats.total_watch_time / 60)" icon="⏱" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Course Performance -->
                <div class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Kurslar samaradorligi</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 rounded-lg">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Kurs</th>
                                    <th class="px-4 py-3">O'quvchilar</th>
                                    <th class="px-4 py-3">Reyting</th>
                                    <th class="px-4 py-3">Tugallash</th>
                                    <th class="px-4 py-3 rounded-r-lg">Daromad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="course in coursePerformance" :key="course.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ course.title }}</td>
                                    <td class="px-4 py-3">{{ course.enrollments }}</td>
                                    <td class="px-4 py-3 flex items-center gap-1">
                                        <span class="text-yellow-400">★</span> {{ course.rating }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                                <div class="bg-emerald-500 h-1.5 rounded-full"
                                                    :style="{ width: `${course.completion_rate}%` }"></div>
                                            </div>
                                            <span class="text-xs">{{ course.completion_rate }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-emerald-600">
                                        {{ new Intl.NumberFormat('uz-UZ').format(course.earnings) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Traffic Sources -->
                <div class="bg-white border border-gray-100 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Trafik manbalari</h3>
                    <div class="space-y-4">
                        <div v-for="(source, index) in trafficSources" :key="index" class="space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ source.source }}</span>
                                <span class="font-medium text-gray-900">{{ source.percent }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full" :style="{ width: `${source.percent}%` }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
