<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRightIcon, DocumentChartBarIcon } from '@heroicons/vue/24/outline';
import GuardianLayout from '@/Layouts/GuardianLayout.vue';
import { Badge, Card, EmptyState, PageHeader, Pagination } from '@/Components/UI';
import { formatDelta, formatPercent } from '@/Components/Exam/People/mastery';

defineProps({
    reports: { type: Object, required: true }, // Laravel paginator
});
</script>

<template>
    <GuardianLayout title="Hisobotlar">
        <Head title="Yakshanba hisobotlari" />

        <PageHeader title="Yakshanba hisobotlari" description="Har yakshanba ertalab: ball, o'sish, mavzular xaritasi va 3 ta tavsiya" />

        <Card padding="none">
            <EmptyState v-if="!reports.data.length" :icon="DocumentChartBarIcon" class="py-10" title="Hali hisobot yo'q"
                description="Birinchi hisobot farzandingiz bog'langandan keyingi yakshanba keladi." />
            <ul v-else class="divide-y divide-border">
                <li v-for="r in reports.data" :key="r.id">
                    <Link :href="route('guardian.reports.show', r.id)" class="focus-ring flex items-center gap-3 px-5 py-3.5 hover:bg-surface-2">
                        <DocumentChartBarIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-fg">{{ r.student }} · {{ r.week_label }}</p>
                            <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-fg-subtle tabular-nums">
                                <template v-if="r.participated">
                                    <span v-for="s in r.subjects.filter((x) => x.percent !== null)" :key="s.name">
                                        {{ s.name }}: {{ formatPercent(s.percent) }}<span v-if="s.delta" :class="s.delta > 0 ? 'text-success' : ''"> ({{ formatDelta(s.delta) }})</span>
                                    </span>
                                </template>
                                <span v-else>Imtihon topshirilmagan</span>
                            </p>
                        </div>
                        <Badge v-if="r.unread" variant="brand" size="sm" dot>Yangi</Badge>
                        <ChevronRightIcon class="h-4 w-4 shrink-0 text-fg-subtle" aria-hidden="true" />
                    </Link>
                </li>
            </ul>
            <Pagination :data="reports" />
        </Card>
    </GuardianLayout>
</template>
