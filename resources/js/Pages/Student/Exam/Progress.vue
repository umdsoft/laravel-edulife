<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowDownTrayIcon, ArrowTrendingUpIcon, ChartBarIcon, ClipboardDocumentIcon, MapIcon, TrophyIcon, CalendarDaysIcon,
} from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, StatCard, Tabs } from '@/Components/UI';
import GrowthChart from '@/Components/Exam/People/GrowthChart.vue';
import TopicMap from '@/Components/Exam/People/TopicMap.vue';
import Recommendations from '@/Components/Exam/People/Recommendations.vue';
import { formatDelta, formatPercent, formatWeek, masteryTone } from '@/Components/Exam/People/mastery';
import { useToast } from '@/Composables/useToast';
import { routePath } from '@/Utils/nav';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    topicMap: { type: Array, default: () => [] },
    weakest: { type: Array, default: () => [] },
    recommendations: { type: Array, default: () => [] },
    latest: { type: Object, default: null },
    referralLink: { type: String, default: '' },
});

const toast = useToast();
const names = computed(() => Object.fromEntries(props.subjects.map((s) => [s.code, s.name])));

const mapTab = ref(props.topicMap[0]?.code || null);
const mapTabs = computed(() => props.topicMap.map((m) => ({ key: m.code, label: m.name })));
const activeMap = computed(() => props.topicMap.find((m) => m.code === mapTab.value) || props.topicMap[0] || null);

const weeksCount = computed(() => new Set(props.history.map((r) => r.week_start)).size);
const leaderboardHref = computed(() => routePath('student.exam.leaderboard'));
const examHref = computed(() => routePath('student.exam.index'));
const cardUrl = computed(() => (props.latest ? route('student.exam.card', { result: props.latest.result_id }) : null));
const cardDownload = computed(() => (props.latest ? route('student.exam.card', { result: props.latest.result_id, download: 1 }) : null));

const toneText = { danger: 'text-danger', warning: 'text-warning', brand: 'text-brand', success: 'text-success', neutral: 'text-fg-subtle' };

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(props.referralLink);
        toast.success('Havola nusxalandi');
    } catch {
        toast.error('Nusxalab bo\'lmadi — havolani qo\'lda belgilang');
    }
};
</script>

<template>
    <StudentLayout title="O'sishim">
        <Head title="O'sishim" />

        <PageHeader title="O'sishim" description="Haftalik natijalar, mavzular xaritasi va keyingi haftaga tavsiyalar">
            <template #actions>
                <Button v-if="leaderboardHref" variant="secondary" :href="leaderboardHref" :icon-left="TrophyIcon">Reyting</Button>
                <Button v-if="cardDownload" :href="cardDownload" external :icon-left="ArrowDownTrayIcon">Natija kartasi</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Oxirgi natija" :value="latest ? formatPercent(latest.percent) : '—'" :icon="ChartBarIcon"
                    :hint="latest ? `${formatWeek(latest.week_start)} · ${names[latest.subject] || ''}` : 'Hali natija yo\'q'" />
                <StatCard label="O'sish" :value="latest && latest.delta !== null ? formatDelta(latest.delta) : '—'"
                    :icon="ArrowTrendingUpIcon" :tone="latest?.delta > 0 ? 'success' : 'neutral'" hint="o'tgan haftaga nisbatan, %" />
                <StatCard label="Reytingdagi o'rin" :value="latest?.rank ? `${latest.rank}-o'rin` : '—'" :icon="TrophyIcon" tone="warning"
                    :hint="latest?.participants ? `${latest.participants} ishtirokchi` : null" />
                <StatCard label="Haftalar" :value="weeksCount" :icon="CalendarDaysIcon" tone="info" hint="topshirilgan imtihonlar" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="O'sish grafigi" description="Har shanba imtihonidagi natija, foizda">
                    <GrowthChart v-if="history.length" :history="history" :names="names" />
                    <EmptyState v-else :icon="ChartBarIcon" title="Hali natija yo'q"
                        description="Birinchi shanba imtihonidan keyin grafik shu yerda paydo bo'ladi.">
                        <Button v-if="examHref" :href="examHref" size="sm">Shanba imtihoni</Button>
                    </EmptyState>
                </Card>

                <Card title="Keyingi hafta uchun" description="Shu 3 mavzuni takrorlang">
                    <Recommendations :items="recommendations" />
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="Mavzular xaritasi" description="Har bo'lim bo'yicha o'zlashtirish">
                    <template v-if="mapTabs.length > 1" #actions>
                        <Tabs v-model="mapTab" :tabs="mapTabs" variant="pills" label="Fan" />
                    </template>
                    <TopicMap v-if="activeMap && activeMap.sections.some((s) => s.score !== null)" :sections="activeMap.sections" />
                    <EmptyState v-else :icon="MapIcon" title="Xarita hali bo'sh"
                        description="Qabul testini topshiring — mavzular bo'yicha darajangiz aniqlanadi." />
                </Card>

                <div class="space-y-6">
                    <Card title="Zaif mavzular">
                        <ul v-if="weakest.length" class="space-y-2.5">
                            <li v-for="t in weakest" :key="t.code" class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-fg">{{ t.name }}</span>
                                    <span v-if="t.section" class="block truncate text-xs text-fg-subtle">{{ t.section }}</span>
                                </span>
                                <span :class="['shrink-0 font-medium tabular-nums', toneText[masteryTone(t.score)]]">{{ formatPercent(t.score) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-fg-muted">Hozircha ma'lumot yo'q.</p>
                    </Card>

                    <Card v-if="latest" title="Natija kartasi" description="Do'stlaringizga ulashing — ismingiz kartada ko'rinmaydi">
                        <div class="flex items-start gap-4">
                            <img :src="cardUrl" alt="Natija kartasi" width="108" height="192" loading="lazy"
                                class="h-48 w-[108px] shrink-0 rounded-md border border-border bg-surface-2 object-cover" />
                            <div class="space-y-2">
                                <Badge variant="brand">{{ formatWeek(latest.week_start) }}</Badge>
                                <p class="text-13 text-fg-muted">1080×1920 PNG — Instagram yoki Telegram storis uchun.</p>
                                <Button :href="cardDownload" external size="sm" :icon-left="ArrowDownTrayIcon">Yuklab olish</Button>
                            </div>
                        </div>
                    </Card>

                    <Card v-if="referralLink" title="Do'stni taklif qilish" description="Do'stingiz qabul testini topshirsa, sizga bonus coin beriladi">
                        <div class="flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate rounded-md bg-surface-2 px-3 py-2 font-mono text-13 text-fg">{{ referralLink }}</code>
                            <Button variant="secondary" size="sm" :icon-left="ClipboardDocumentIcon" @click="copyLink">Nusxa</Button>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
