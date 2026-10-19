<script setup>
import { Head } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon, CheckCircleIcon, XCircleIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, StatCard, EmptyState, Pagination } from '@/Components/UI';
import TestHistoryItem from '@/Components/Student/TestHistoryItem.vue';

defineProps({
    attempts: Object,
    stats: Object
});
</script>

<template>
    <StudentLayout title="Test tarixi">

        <Head title="Test Tarixi" />

        <PageHeader title="Test tarixim" description="Barcha urinishlaringiz va natijalaringiz." />

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <StatCard label="Jami urinishlar" :value="stats?.total_attempts ?? 0" :icon="ClipboardDocumentListIcon" tone="neutral" />
                <StatCard label="Muvaffaqiyatli" :value="stats?.passed ?? 0" :icon="CheckCircleIcon" tone="success" />
                <StatCard label="Muvaffaqiyatsiz" :value="stats?.failed ?? 0" :icon="XCircleIcon" tone="danger" />
                <StatCard label="O'rtacha ball" :value="Math.round(stats?.average_score || 0)" unit="%" :icon="ChartBarIcon" tone="brand" />
            </div>

            <div v-if="attempts.data.length > 0" class="space-y-3">
                <TestHistoryItem v-for="attempt in attempts.data" :key="attempt.id" :attempt="attempt" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="ClipboardDocumentListIcon" title="Hozircha testlar tarixi yo'q"
                description="Kurs testlarini topshirganingizdan keyin natijalar shu yerda saqlanadi." />

            <Pagination :data="attempts" />
        </div>
    </StudentLayout>
</template>
