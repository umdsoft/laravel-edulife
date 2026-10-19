<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import {
    Alert,
    Badge,
    Button,
    Card,
    EmptyState,
    Modal,
    PageHeader,
    ProgressBar,
    ProgressRing,
    StatCard,
    Tabs,
} from '@/Components/UI';
import {
    AcademicCapIcon,
    ArrowDownTrayIcon,
    ChartBarIcon,
    ClockIcon,
    DocumentTextIcon,
    LinkIcon,
    PaperAirplaneIcon,
    PuzzlePieceIcon,
    ShareIcon,
    TrophyIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { ClockIcon as ClockSolidIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    sectionDetails: Array,
    ranking: Object,
    nearbyCompetitors: Object,
    statistics: Object,
});

const activeTab = ref('overview');
const showShareModal = ref(false);
const showCertificateModal = ref(false);
const chartCanvas = ref(null);

// Chart data for radar/bar visualization
const chartData = computed(() => {
    return props.sectionDetails.map(s => ({
        label: s.section_title,
        value: s.score_percent,
        color: getBarColor(s.score_percent),
    }));
});

const getScoreColor = (percent) => {
    if (percent >= 80) return 'text-success';
    if (percent >= 60) return 'text-info';
    if (percent >= 40) return 'text-warning';
    return 'text-danger';
};

// Tone name for UI ProgressBar / ProgressRing
const getBarColor = (percent) => {
    if (percent >= 80) return 'success';
    if (percent >= 60) return 'info';
    if (percent >= 40) return 'warning';
    return 'danger';
};

// Share functions
const shareToTelegram = () => {
    const text = `${props.olympiad.title} olimpiadasida ${props.attempt.score_percent}% ball to'pladim! 🎓`;
    const url = window.location.href;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
};

const shareToTwitter = () => {
    const text = `I scored ${props.attempt.score_percent}% in ${props.olympiad.title}! 🏆`;
    const url = window.location.href;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
};

const copyShareLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        alert('Havola nusxalandi!');
    } catch (e) { }
};

const downloadCertificate = (format) => {
    window.location.href = route('student.olympiads.certificate.download', { slug: props.olympiad.slug, format });
};

const tabs = [
    { key: 'overview', label: 'Umumiy', icon: ChartBarIcon },
    { key: 'sections', label: "Bo'limlar", icon: PuzzlePieceIcon },
    { key: 'leaderboard', label: 'Reyting', icon: TrophyIcon },
];

const scorePercent = computed(() => Number(props.attempt.score_percent) || 0);
const hasCompetitors = computed(() => (props.nearbyCompetitors?.above?.length || 0) + (props.nearbyCompetitors?.below?.length || 0) > 0);
</script>

<template>
    <StudentLayout title="Natijalar">
        <Head :title="`Natijalar - ${olympiad.title}`" />

        <PageHeader title="Natijalar" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'Natijalar' },
        ]">
            <template #meta>
                <Badge v-if="olympiad.type" variant="neutral">{{ olympiad.type }}</Badge>
                <Badge v-if="olympiad.stage" variant="neutral">{{ olympiad.stage }}</Badge>
            </template>
            <template #actions>
                <Button variant="secondary" :icon-left="ShareIcon" @click="showShareModal = true">Ulashish</Button>
                <Button :icon-left="AcademicCapIcon" @click="showCertificateModal = true">Sertifikat</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Status alerts -->
            <Alert v-if="attempt.is_disqualified" variant="danger" title="Diskvalifikatsiya">
                {{ attempt.disqualified_reason }}
            </Alert>
            <Alert v-if="attempt.is_grading" variant="info" title="Baholanmoqda">
                Ba'zi bo'limlar hali baholanmoqda. Yakuniy natija keyinroq yangilanadi.
            </Alert>

            <!-- Score summary -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="col-span-2 flex items-center gap-5 rounded-lg border border-border bg-surface p-5 shadow-xs lg:col-span-1">
                    <ProgressRing :value="scorePercent" :size="88" :stroke="8" :tone="getBarColor(scorePercent)"
                        label="Umumiy natija" />
                    <div>
                        <p class="text-13 font-medium text-fg-muted">Umumiy natija</p>
                        <p class="mt-1 text-13 text-fg-subtle">Maksimal balldan ulush</p>
                    </div>
                </div>
                <StatCard label="Ball" :value="`${attempt.total_score} / ${attempt.max_score}`" :icon="ChartBarIcon"
                    tone="brand" />
                <StatCard label="O'rin" :value="ranking.rank ? `#${ranking.rank}` : null" :icon="TrophyIcon" tone="warning"
                    :hint="`${ranking.total_participants} ishtirokchidan`" />
                <StatCard label="Sarflangan vaqt" :value="attempt.duration" :icon="ClockIcon" tone="neutral"
                    class="col-span-2 sm:col-span-1" />
            </div>

            <Tabs v-model="activeTab" :tabs="tabs" label="Natija bo'limlari">
                <!-- Overview Tab -->
                <template #overview>
                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="space-y-6 lg:col-span-2">
                            <Card title="Bo'limlar natijalari">
                                <ul v-if="sectionDetails.length" class="space-y-5">
                                    <li v-for="section in sectionDetails" :key="section.section_type">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="flex items-center gap-2 font-medium text-fg">
                                                    <span class="truncate">{{ section.section_title }}</span>
                                                    <Badge v-if="section.requires_grading" variant="warning" size="sm"
                                                        :icon="ClockSolidIcon">Baholanmoqda</Badge>
                                                </p>
                                                <p class="mt-0.5 text-13 text-fg-subtle tabular-nums">
                                                    {{ section.questions_correct }}/{{ section.questions_answered }} to'g'ri ·
                                                    {{ section.raw_score }}/{{ section.max_score }} ball
                                                </p>
                                            </div>
                                            <span :class="['text-lg font-semibold tabular-nums', getScoreColor(section.score_percent)]">
                                                {{ section.score_percent }}%
                                            </span>
                                        </div>
                                        <ProgressBar :value="Number(section.score_percent) || 0" size="sm"
                                            :tone="getBarColor(section.score_percent)" class="mt-2" />
                                    </li>
                                </ul>
                                <EmptyState v-else size="sm" :icon="PuzzlePieceIcon" title="Bo'lim natijalari yo'q" />
                            </Card>

                            <Card title="Imtihon ma'lumotlari">
                                <dl class="grid gap-x-8 text-sm sm:grid-cols-2">
                                    <div class="flex justify-between gap-3 border-b border-border py-2.5">
                                        <dt class="text-fg-muted">Boshlangan</dt>
                                        <dd class="font-medium text-fg tabular-nums">{{ attempt.started_at }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3 border-b border-border py-2.5">
                                        <dt class="text-fg-muted">Yakunlangan</dt>
                                        <dd class="font-medium text-fg tabular-nums">{{ attempt.completed_at }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3 border-b border-border py-2.5 sm:border-b-0">
                                        <dt class="text-fg-muted">Sarflangan vaqt</dt>
                                        <dd class="font-medium text-fg tabular-nums">{{ attempt.duration }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3 py-2.5">
                                        <dt class="text-fg-muted">Percentil</dt>
                                        <dd class="font-medium text-fg tabular-nums">Top {{ 100 - (ranking.percentile || 0) }}%</dd>
                                    </div>
                                </dl>
                            </Card>
                        </div>

                        <aside class="space-y-6">
                            <Card title="Harakatlar">
                                <div class="space-y-2">
                                    <Button block :icon-left="AcademicCapIcon" @click="showCertificateModal = true">
                                        Sertifikat olish
                                    </Button>
                                    <Button block variant="secondary" :icon-left="DocumentTextIcon"
                                        :href="route('student.olympiads.results.review', olympiad.slug)">
                                        Javoblarni ko'rish
                                    </Button>
                                    <Button block variant="secondary" :icon-left="ShareIcon" @click="showShareModal = true">
                                        Ulashish
                                    </Button>
                                </div>
                            </Card>

                            <Card title="Yaqin raqiblar">
                                <ul v-if="hasCompetitors || ranking.rank" class="space-y-1 text-sm">
                                    <li v-for="comp in nearbyCompetitors.above" :key="`a-${comp.rank}`"
                                        class="flex items-center justify-between gap-3 rounded-md px-3 py-2">
                                        <span class="flex min-w-0 items-center gap-2 text-fg-muted">
                                            <span class="w-8 shrink-0 tabular-nums text-fg-subtle">#{{ comp.rank }}</span>
                                            <span class="truncate">{{ comp.name }}</span>
                                        </span>
                                        <span class="font-medium text-fg tabular-nums">{{ comp.score }}</span>
                                    </li>
                                    <li v-if="ranking.rank"
                                        class="flex items-center justify-between gap-3 rounded-md bg-brand-50 px-3 py-2 ring-1 ring-inset ring-brand-100"
                                        aria-current="true">
                                        <span class="flex items-center gap-2 font-medium text-brand">
                                            <span class="w-8 shrink-0 tabular-nums">#{{ ranking.rank }}</span>
                                            Siz
                                        </span>
                                        <span class="font-semibold text-brand tabular-nums">{{ attempt.total_score }}</span>
                                    </li>
                                    <li v-for="comp in nearbyCompetitors.below" :key="`b-${comp.rank}`"
                                        class="flex items-center justify-between gap-3 rounded-md px-3 py-2">
                                        <span class="flex min-w-0 items-center gap-2 text-fg-muted">
                                            <span class="w-8 shrink-0 tabular-nums text-fg-subtle">#{{ comp.rank }}</span>
                                            <span class="truncate">{{ comp.name }}</span>
                                        </span>
                                        <span class="font-medium text-fg tabular-nums">{{ comp.score }}</span>
                                    </li>
                                </ul>
                                <EmptyState v-else size="sm" :icon="UsersIcon" title="Hali reyting shakllanmagan" />
                            </Card>
                        </aside>
                    </div>
                </template>

                <!-- Sections Tab -->
                <template #sections>
                    <Card v-if="sectionDetails.length" padding="none">
                        <ul class="divide-y divide-border">
                            <li v-for="section in sectionDetails" :key="section.section_type" class="p-5 sm:p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="font-semibold text-fg">{{ section.section_title }}</h3>
                                    <span :class="['text-xl font-semibold tabular-nums', getScoreColor(section.score_percent)]">
                                        {{ section.score_percent }}%
                                    </span>
                                </div>
                                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-md bg-surface-2 p-3">
                                        <dt class="text-xs text-fg-subtle">Ball</dt>
                                        <dd class="mt-1 text-lg font-semibold text-fg tabular-nums">{{ section.raw_score }}</dd>
                                    </div>
                                    <div class="rounded-md bg-surface-2 p-3">
                                        <dt class="text-xs text-fg-subtle">Maksimum</dt>
                                        <dd class="mt-1 text-lg font-semibold text-fg tabular-nums">{{ section.max_score }}</dd>
                                    </div>
                                    <div class="rounded-md bg-success-50 p-3">
                                        <dt class="text-xs text-success">To'g'ri</dt>
                                        <dd class="mt-1 text-lg font-semibold text-success tabular-nums">{{ section.questions_correct }}</dd>
                                    </div>
                                    <div class="rounded-md bg-surface-2 p-3">
                                        <dt class="text-xs text-fg-subtle">Javob berilgan</dt>
                                        <dd class="mt-1 text-lg font-semibold text-fg tabular-nums">{{ section.questions_answered }}</dd>
                                    </div>
                                </dl>
                            </li>
                        </ul>
                    </Card>
                    <Card v-else>
                        <EmptyState :icon="PuzzlePieceIcon" title="Bo'lim natijalari yo'q" />
                    </Card>
                </template>

                <!-- Leaderboard Tab -->
                <template #leaderboard>
                    <Card>
                        <EmptyState :icon="TrophyIcon" title="To'liq reyting"
                            description="Barcha ishtirokchilar natijalari va o'rningizni ko'ring.">
                            <Button :icon-left="TrophyIcon" :href="route('student.olympiads.results.leaderboard', olympiad.slug)">
                                To'liq reytingni ko'rish
                            </Button>
                        </EmptyState>
                    </Card>
                </template>
            </Tabs>
        </div>

        <!-- Share Modal -->
        <Modal :show="showShareModal" title="Natijalarni ulashish" size="sm" @close="showShareModal = false">
            <div class="space-y-2">
                <Button block variant="secondary" :icon-left="PaperAirplaneIcon" class="!justify-start" @click="shareToTelegram">
                    Telegram
                </Button>
                <Button block variant="secondary" :icon-left="ShareIcon" class="!justify-start" @click="shareToTwitter">
                    X (Twitter)
                </Button>
                <Button block variant="secondary" :icon-left="LinkIcon" class="!justify-start" @click="copyShareLink">
                    Havolani nusxalash
                </Button>
            </div>
            <template #footer>
                <Button variant="ghost" @click="showShareModal = false">Yopish</Button>
            </template>
        </Modal>

        <!-- Certificate Modal -->
        <Modal :show="showCertificateModal" title="Sertifikat" size="lg" @close="showCertificateModal = false">
            <div class="rounded-lg border border-border bg-surface-2 p-6 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand">
                    <AcademicCapIcon class="h-6 w-6" aria-hidden="true" />
                </span>
                <p class="mt-3 text-xs font-medium uppercase tracking-wider text-fg-subtle">Sertifikat</p>
                <p class="mt-1 text-lg font-semibold text-fg">{{ olympiad.title }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-fg tabular-nums">{{ attempt.score_percent }}%</p>
                <p v-if="ranking.rank" class="mt-1 text-sm text-fg-muted tabular-nums">#{{ ranking.rank }} o'rin</p>
            </div>
            <template #footer>
                <Button variant="secondary" :icon-left="ArrowDownTrayIcon" @click="downloadCertificate('png')">PNG</Button>
                <Button :icon-left="ArrowDownTrayIcon" @click="downloadCertificate('pdf')">PDF</Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
