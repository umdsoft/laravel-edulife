<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatsCard from '@/Components/Teacher/StatsCard.vue';

defineProps({
    enrollments: Object,
    stats: Object,
});
</script>

<template>

    <Head title="O'quvchilarim" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">O'quvchilarim</h2>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <StatsCard title="Jami o'quvchilar" :value="stats.total_students" icon="👥" />
                <StatsCard title="Bu hafta faol" :value="stats.active_this_week" icon="⚡" />
                <StatsCard title="Tugatganlar" :value="stats.completed" icon="🎓" />
            </div>

            <!-- Students Table -->
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">O'quvchi</th>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Kurs</th>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Progress</th>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Oxirgi faollik</th>
                                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id"
                                class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold text-sm">
                                            {{ enrollment.user.first_name[0] }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ enrollment.user.first_name }} {{
                                                enrollment.user.last_name }}</p>
                                            <p class="text-xs text-gray-500">{{ enrollment.user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ enrollment.course.title }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-full max-w-[100px]">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span>{{ enrollment.progress }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                                            <div class="bg-emerald-500 h-1.5 rounded-full"
                                                :style="{ width: `${enrollment.progress}%` }"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ enrollment.last_activity_at ? new
                                        Date(enrollment.last_activity_at).toLocaleDateString()
                                        : 'Kirmagan' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-green-100 text-green-600': enrollment.status === 'completed',
                                        'bg-blue-100 text-blue-600': enrollment.status === 'active',
                                        'bg-gray-100 text-gray-600': enrollment.status === 'dropped',
                                    }" class="px-2 py-1 rounded text-xs font-medium capitalize">
                                        {{ enrollment.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="enrollments.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    O'quvchilar topilmadi
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    <Pagination :data="enrollments" />
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
