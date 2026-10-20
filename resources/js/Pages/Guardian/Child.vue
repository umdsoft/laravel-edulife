<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChartBarIcon, ChevronRightIcon, DocumentChartBarIcon, MapIcon } from '@heroicons/vue/24/outline';
import GuardianLayout from '@/Layouts/GuardianLayout.vue';
import { Badge, Card, EmptyState, PageHeader, StatCard, Tabs } from '@/Components/UI';
import GrowthChart from '@/Components/Exam/People/GrowthChart.vue';
import TopicMap from '@/Components/Exam/People/TopicMap.vue';
import Recommendations from '@/Components/Exam/People/Recommendations.vue';
import { formatDelta, formatPercent, formatWeek, masteryTone } from '@/Components/Exam/People/mastery';

const props = defineProps({
    child: { type: Object, required: true },
    reports: { type: Array, default: () => [] },
});

const name = computed(() => [props.child.first_name, props.child.last_name].filter(Boolean).join(' '));
const history = computed(() => props.child.subjects.flatMap((s) => s.history));
const names = computed(() => Object.fromEntries(props.child.subjects.map((s) => [s.code, s.name])));
const mapTab = ref(props.child.topicMap[0]?.code || null);
const mapTabs = computed(() => props.child.topicMap.map((m) => ({ key: m.code, label: m.name })));
const activeMap = computed(() => props.child.topicMap.find((m) => m.code === mapTab.value) || props.child.topicMap[0] || null);
const toneText = { danger: 'text-danger', warning: 'text-warning', brand: 'text-brand', success: 'text-success', neutral: 'text-fg-subtle' };
</script>

<template>
    <GuardianLayout :title="child.first_name">
        <Head :title="name" />

        <PageHeader :title="name" description="Natijalar, o'sish va mavzular bo'yicha holat"
            :breadcrumbs="[{ label: 'Farzandlarim', href: route('guardian.dashboard') }, { label: name }]" />

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard v-for="s in child.subjects" :key="s.code" :label="`${s.name} · oxirgi`"
                    :value="s.last ? formatPercent(s.last.percent) : '—'" :icon="ChartBarIcon" :tone="s.code === 'physics' ? 'physics' : 'math'"
                    :hint="s.last ? (s.last.delta !== null ? `${formatDelta(s.last.delta)} ball · ${formatWeek(s.last.week_start)}` : formatWeek(s.last.week_start)) : 'Hali imtihon yo\'q'" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="O'sish grafigi">
                    <GrowthChart v-if="history.length" :history="history" :names="names" />
                    <EmptyState v-else :icon="ChartBarIcon" title="Hali natija yo'q" description="Birinchi shanba imtihonidan keyin grafik paydo bo'ladi." />
                </Card>
                <Card title="Keyingi hafta uchun">
                    <Recommendations :items="child.recommendations" />
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="Mavzular xaritasi">
                    <template v-if="mapTabs.length > 1" #actions>
                        <Tabs v-model="mapTab" :tabs="mapTabs" variant="pills" label="Fan" />
                    </template>
                    <TopicMap v-if="activeMap && activeMap.sections.some((s) => s.score !== null)" :sections="activeMap.sections" />
                    <EmptyState v-else :icon="MapIcon" title="Xarita hali bo'sh" description="Qabul testidan keyin mavzular bo'yicha daraja ko'rinadi." />
                </Card>
                <div class="space-y-6">
                    <Card title="Zaif mavzular">
                        <ul v-if="child.weakest.length" class="space-y-2.5">
                            <li v-for="t in child.weakest" :key="t.code" class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate text-fg">{{ t.name }}</span>
                                <span :class="['shrink-0 font-medium tabular-nums', toneText[masteryTone(t.score)]]">{{ formatPercent(t.score) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-fg-muted">Hozircha ma'lumot yo'q.</p>
                    </Card>
                    <Card v-if="reports.length" padding="none" title="Hisobotlar">
                        <ul class="divide-y divide-border">
                            <li v-for="r in reports" :key="r.id">
                                <Link :href="route('guardian.reports.show', r.id)" class="focus-ring flex items-center gap-3 px-5 py-3 hover:bg-surface-2">
                                    <DocumentChartBarIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                                    <span class="flex-1 text-sm text-fg">{{ r.week_label || r.week_start }}</span>
                                    <Badge v-if="r.unread" variant="brand" size="sm" dot>Yangi</Badge>
                                    <ChevronRightIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                                </Link>
                            </li>
                        </ul>
                    </Card>
                </div>
            </div>
        </div>
    </GuardianLayout>
</template>
