<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowTrendingUpIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import GuardianLayout from '@/Layouts/GuardianLayout.vue';
import { Alert, Button, Card, PageHeader, StatCard } from '@/Components/UI';
import TopicMap from '@/Components/Exam/People/TopicMap.vue';
import Recommendations from '@/Components/Exam/People/Recommendations.vue';
import { formatDelta, formatPercent } from '@/Components/Exam/People/mastery';

const props = defineProps({
    report: { type: Object, required: true },
});

const title = computed(() => `${props.report.student?.first_name || 'Farzandingiz'} — haftalik hisobot`);
</script>

<template>
    <GuardianLayout title="Hisobot">
        <Head :title="title" />

        <PageHeader :title="title" :description="report.week_label"
            :breadcrumbs="[{ label: 'Hisobotlar', href: route('guardian.reports.index') }, { label: report.week_label }]">
            <template #actions>
                <Button variant="secondary" :href="route('guardian.children.show', report.student_id)">Farzand sahifasi</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="!report.participated" variant="warning" title="Bu hafta imtihon topshirilmadi">
                Keyingi shanba imtihonini o'tkazib yubormaslik uchun farzandingizga eslatib qo'ying.
            </Alert>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <template v-for="s in report.subjects" :key="s.code">
                    <StatCard :label="`${s.name} · ball`" :value="s.participated ? `${String(s.score).replace('.', ',')} / ${String(s.max_score).replace('.', ',')}` : '—'"
                        :icon="ChartBarIcon" :tone="s.code === 'physics' ? 'physics' : 'math'"
                        :hint="s.participated ? `${formatPercent(s.percent)}${s.rank ? ` · ${s.rank}-o'rin / ${s.participants}` : ''}` : 'Topshirilmagan'" />
                    <StatCard :label="`${s.name} · o'sish`" :value="s.delta !== null ? formatDelta(s.delta) : '—'" :icon="ArrowTrendingUpIcon"
                        :tone="s.delta > 0 ? 'success' : 'neutral'" hint="o'tgan haftaga nisbatan, ball" />
                </template>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="Mavzular xaritasi">
                    <div class="space-y-6">
                        <div v-for="s in report.subjects" :key="s.code">
                            <h3 v-if="report.subjects.length > 1" class="mb-3 text-sm font-semibold text-fg">{{ s.name }}</h3>
                            <TopicMap :sections="s.topics" compact />
                        </div>
                    </div>
                </Card>
                <Card title="Keyingi hafta uchun 3 ta mavzu">
                    <Recommendations :items="report.recommendations" />
                </Card>
            </div>
        </div>
    </GuardianLayout>
</template>
