<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Alert, Badge, Button, Card, EmptyState, IconButton, PageHeader, StatCard } from '@/Components/UI';
import {
    UserGroupIcon, CheckCircleIcon, NoSymbolIcon, ChartBarIcon, ArrowPathIcon, PauseIcon, PlayIcon, TrophyIcon,
    ShieldCheckIcon, ShieldExclamationIcon, ChevronRightIcon, ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    olympiad: Object,
    liveStats: Object,
    topPerformers: Array,
    activeViolations: Array,
});

const REFRESH_MS = 5000;
const LISTS_REFRESH_MS = 30000;

const stats = ref({ ...(props.liveStats || {}) });
const lastUpdated = ref(new Date());
const refreshing = ref(false);
const refreshError = ref(false);
const paused = ref(false);
const now = ref(Date.now());

let statsTimer = null;
let listsTimer = null;
let clockTimer = null;

const refreshStats = async () => {
    if (refreshing.value) return;
    refreshing.value = true;
    try {
        const response = await fetch(route('admin.olympiads.monitor.stats', props.olympiad.id), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        stats.value = await response.json();
        lastUpdated.value = new Date();
        refreshError.value = false;
    } catch (error) {
        refreshError.value = true;
        console.error('Error refreshing stats:', error);
    } finally {
        refreshing.value = false;
    }
};

// Leaderboard + violations come from the page props; refresh them with a light partial reload
const refreshLists = () => {
    router.reload({ only: ['topPerformers', 'activeViolations'], preserveScroll: true, preserveState: true });
};

const tick = () => {
    if (paused.value || document.hidden) return;
    refreshStats();
};
const tickLists = () => {
    if (paused.value || document.hidden) return;
    refreshLists();
};

const refreshNow = () => {
    refreshStats();
    refreshLists();
};

const togglePause = () => {
    paused.value = !paused.value;
    if (!paused.value) refreshNow();
};

onMounted(() => {
    statsTimer = setInterval(tick, REFRESH_MS);
    listsTimer = setInterval(tickLists, LISTS_REFRESH_MS);
    clockTimer = setInterval(() => (now.value = Date.now()), 1000);
});

onUnmounted(() => {
    clearInterval(statsTimer);
    clearInterval(listsTimer);
    clearInterval(clockTimer);
});

const pad = (n) => String(n).padStart(2, '0');
const timeLabel = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;

const startMs = computed(() => (props.olympiad.olympiad_start_at ? Date.parse(props.olympiad.olympiad_start_at) : NaN));
const endMs = computed(() => (props.olympiad.olympiad_end_at ? Date.parse(props.olympiad.olympiad_end_at) : NaN));
const hasWindow = computed(() => Number.isFinite(startMs.value) && Number.isFinite(endMs.value) && endMs.value > startMs.value);
const remainingMs = computed(() => (Number.isFinite(endMs.value) ? Math.max(0, endMs.value - now.value) : null));
const elapsedPercent = computed(() => {
    if (!hasWindow.value) return 0;
    const p = ((now.value - startMs.value) / (endMs.value - startMs.value)) * 100;
    return Math.min(100, Math.max(0, p));
});
const remainingLabel = computed(() => {
    if (remainingMs.value === null) return '—';
    const total = Math.floor(remainingMs.value / 1000);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
});

const num = (v) => Number(v) || 0;
const total = computed(() => Math.max(num(stats.value.total_registered), 0));
const segments = computed(() => {
    const t = total.value || 1;
    return [
        { key: 'completed', label: 'Yakunlagan', value: num(stats.value.completed), cls: 'bg-brand-600' },
        { key: 'active', label: 'Hozir ishlamoqda', value: num(stats.value.active_now), cls: 'bg-success-600' },
        { key: 'disqualified', label: 'Diskvalifikatsiya', value: num(stats.value.disqualified), cls: 'bg-danger-600' },
        { key: 'not_started', label: 'Boshlamagan', value: Math.max(num(stats.value.not_started), 0), cls: 'bg-surface-3' },
    ].map((s) => ({ ...s, percent: (s.value / t) * 100 }));
});

const severity = (s) => (s === 'critical' ? { label: 'Jiddiy', variant: 'danger' } : s === 'high' ? { label: 'Yuqori', variant: 'warning' } : { label: s === 'low' ? 'Past' : 'O\'rta', variant: 'warning' });

const breadcrumbs = computed(() => [
    { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
    { label: props.olympiad.title, href: route('admin.olympiads.show', props.olympiad.id) },
    { label: 'Monitoring' },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="`Monitoring — ${olympiad.title}`" />

        <PageHeader title="Jonli monitoring" :description="olympiad.title" :breadcrumbs="breadcrumbs">
            <template #meta>
                <Badge v-if="olympiad.status === 'live'" variant="danger">
                    <span class="relative flex h-2 w-2" aria-hidden="true">
                        <span v-if="!paused" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-danger-600 opacity-60 motion-reduce:animate-none" />
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-danger-600" />
                    </span>
                    Jonli
                </Badge>
                <Badge v-else variant="neutral">Jonli emas</Badge>
                <span class="text-13 text-fg-subtle" aria-live="polite">
                    <template v-if="paused">Avto-yangilash to'xtatilgan</template>
                    <template v-else>Har 5 soniyada yangilanadi</template>
                    · oxirgi: <span class="tabular-nums">{{ timeLabel(lastUpdated) }}</span>
                </span>
            </template>
            <template #actions>
                <IconButton :icon="ArrowPathIcon" label="Hozir yangilash" variant="secondary" :disabled="refreshing"
                    :class="refreshing ? '[&_svg]:animate-spin' : ''" @click="refreshNow" />
                <Button variant="secondary" :icon-left="paused ? PlayIcon : PauseIcon" :aria-pressed="paused" @click="togglePause">
                    {{ paused ? 'Davom ettirish' : 'To\'xtatish' }}
                </Button>
                <Button :icon-left="TrophyIcon" :href="route('admin.olympiads.monitor.leaderboard', olympiad.id)">To'liq reyting</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="refreshError" variant="warning" title="Ma'lumotni yangilab bo'lmadi">
                Ko'rsatilgan raqamlar {{ timeLabel(lastUpdated) }} holatiga tegishli. Ulanish tiklangach avtomatik yangilanadi.
            </Alert>

            <!-- Live counters -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Hozir ishlamoqda" :value="num(stats.active_now)" :icon="UserGroupIcon" tone="success"
                    :hint="`${total} ro'yxatdan`" />
                <StatCard label="Yakunlagan" :value="num(stats.completed)" :icon="CheckCircleIcon" tone="brand"
                    :hint="`${num(stats.completion_rate)}% yakunlangan`" />
                <StatCard label="Diskvalifikatsiya" :value="num(stats.disqualified)" :icon="NoSymbolIcon" tone="danger"
                    :hint="num(stats.active_violations) ? `${num(stats.active_violations)} ochiq qoidabuzarlik` : 'Ochiq qoidabuzarlik yo\'q'" />
                <StatCard label="O'rtacha ball" :value="num(stats.average_score)" unit="%" :icon="ChartBarIcon" tone="info" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Participation breakdown -->
                <Card class="lg:col-span-2" title="Ishtirok holati" :description="`${total} ta tasdiqlangan ishtirokchi`">
                    <div class="flex h-3 w-full overflow-hidden rounded-full bg-surface-2" role="img"
                        :aria-label="segments.map((s) => `${s.label}: ${s.value}`).join(', ')">
                        <div v-for="s in segments" :key="s.key" :class="[s.cls, 'h-full transition-all duration-500 ease-out motion-reduce:transition-none']"
                            :style="{ width: `${s.percent}%` }" />
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="s in segments" :key="s.key" class="flex items-start gap-2">
                            <span :class="[s.cls, 'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full']" aria-hidden="true" />
                            <div>
                                <dt class="text-13 text-fg-muted">{{ s.label }}</dt>
                                <dd class="text-lg font-semibold tabular-nums text-fg">{{ s.value }}</dd>
                            </div>
                        </div>
                    </dl>
                </Card>

                <!-- Time window -->
                <Card title="Vaqt">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand">
                            <ClockIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <p class="text-13 text-fg-muted">Tugashiga qoldi</p>
                            <p class="text-2xl font-semibold tabular-nums tracking-tight text-fg" aria-live="off">{{ remainingLabel }}</p>
                        </div>
                    </div>
                    <div v-if="hasWindow" class="mt-4">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-2">
                            <div class="h-full rounded-full bg-brand-600" :style="{ width: `${elapsedPercent}%` }" />
                        </div>
                        <p class="mt-1.5 text-xs tabular-nums text-fg-subtle">{{ Math.round(elapsedPercent) }}% vaqt o'tdi</p>
                    </div>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Top performers -->
                <Card padding="none" title="Top ishtirokchilar" description="Joriy reyting bo'yicha">
                    <template #actions>
                        <Button size="sm" variant="ghost" :icon-right="ChevronRightIcon"
                            :href="route('admin.olympiads.monitor.leaderboard', olympiad.id)">
                            Barchasi
                        </Button>
                    </template>
                    <ol v-if="topPerformers?.length" class="divide-y divide-border">
                        <li v-for="(p, index) in topPerformers" :key="`${p.rank}-${index}`" class="flex items-center gap-3 px-5 py-3">
                            <span :class="[
                                'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                p.rank <= 3 ? 'bg-brand-50 text-brand' : 'bg-surface-2 text-fg-muted',
                            ]">{{ p.rank }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-fg">{{ p.user_name }}</span>
                            <span class="text-right tabular-nums">
                                <span class="font-semibold text-fg">{{ p.score }}</span>
                                <span class="ml-2 text-13 text-fg-subtle">{{ p.score_percent }}%</span>
                            </span>
                        </li>
                    </ol>
                    <EmptyState v-else :icon="TrophyIcon" title="Hozircha natija yo'q"
                        description="Ishtirokchilar javob bera boshlagach shu yerda ko'rinadi" size="sm" class="py-8" />
                </Card>

                <!-- Violations -->
                <Card padding="none" title="Ochiq qoidabuzarliklar">
                    <template #actions>
                        <Badge :variant="num(stats.active_violations) ? 'danger' : 'success'">
                            <span class="tabular-nums">{{ num(stats.active_violations) }}</span> ta
                        </Badge>
                    </template>
                    <ul v-if="activeViolations?.length" class="divide-y divide-border">
                        <li v-for="v in activeViolations" :key="v.id" class="flex items-start gap-3 px-5 py-3">
                            <ShieldExclamationIcon :class="['mt-0.5 h-5 w-5 shrink-0', v.severity === 'critical' ? 'text-danger' : 'text-warning']"
                                aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-fg">{{ v.user_name }}</p>
                                <p class="text-13 text-fg-muted">
                                    {{ v.type }} <span class="tabular-nums">× {{ v.count }}</span>
                                    <template v-if="v.action"> · {{ v.action }}</template>
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <Badge :variant="severity(v.severity).variant" size="sm">{{ severity(v.severity).label }}</Badge>
                                <span class="text-xs text-fg-subtle">{{ v.created_at }}</span>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="ShieldCheckIcon" title="Qoidabuzarlik yo'q" description="Hammasi joyida" size="sm"
                        class="py-8" />
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
