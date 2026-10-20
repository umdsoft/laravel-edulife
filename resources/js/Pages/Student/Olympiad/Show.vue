<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import { Alert, Badge, Button, Card, EmptyState, PageHeader, Tabs } from '@/Components/UI';
import {
    ChartBarIcon,
    ClockIcon,
    CreditCardIcon,
    DocumentTextIcon,
    GiftIcon,
    InformationCircleIcon,
    PlayIcon,
    PuzzlePieceIcon,
    ScaleIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    registration: Object,
    canRegister: Object,
});

const activeTab = ref('about');
const isRegistering = ref(false);

const getStatusBadge = (status) => {
    const badges = {
        'upcoming': { label: 'Tez kunda', variant: 'info' },
        'registration_open': { label: "Ro'yxat ochiq", variant: 'success' },
        'live': { label: 'Jonli', variant: 'danger' },
        'grading': { label: 'Baholanmoqda', variant: 'warning' },
        'completed': { label: 'Yakunlangan', variant: 'neutral' },
    };
    return badges[status] || badges['upcoming'];
};

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const formatPrice = (price) => {
    if (!price || Number(price) === 0) return 'Bepul';
    return `${groupDigits(price)} so'm`;
};

const getSectionIcon = (type) => {
    const icons = {
        'test': DocumentTextIcon,
    };
    return icons[type] || PuzzlePieceIcon;
};

const handleRegister = () => {
    router.visit(route('student.olympiads.register', props.olympiad.slug));
};

const handleStartExam = () => {
    router.visit(route('student.olympiads.preflight', props.olympiad.slug));
};

const tabs = [
    { key: 'about', label: "Ma'lumot", icon: InformationCircleIcon },
    { key: 'sections', label: "Bo'limlar", icon: PuzzlePieceIcon },
    { key: 'prizes', label: 'Sovrinlar', icon: TrophyIcon },
];

const coverImage = computed(() => props.olympiad.banner_image || props.olympiad.cover_image || null);

const places = computed(() => {
    const dist = props.olympiad.prize_distribution;
    if (!dist) return [];
    return ['1', '2', '3']
        .filter((k) => dist[k] !== undefined && dist[k] !== null)
        .map((k) => ({ place: Number(k), amount: dist[k] }));
});

const placeTone = (place) => (place === 1
    ? 'bg-warning-50 text-warning ring-warning-100'
    : 'bg-surface-2 text-fg-muted ring-border');

const closedReason = computed(() => {
    const reason = props.canRegister?.reason;
    if (reason === 'registration_closed') return "Ro'yxat yopilgan";
    if (reason === 'max_participants_reached') return 'Joylar tugagan';
    if (reason === 'registration_not_started') return "Ro'yxat hali boshlanmagan";
    return "Ro'yxatdan o'tish imkonsiz";
});

const richText = 'text-sm leading-relaxed text-fg [&_a]:text-brand [&_a]:underline [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-3 [&_ul]:list-disc [&_ul]:pl-5';
</script>

<template>
    <StudentLayout :title="olympiad.title">
        <Head :title="olympiad.title" />

        <PageHeader :title="olympiad.title" :description="olympiad.series || null" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title },
        ]">
            <template #meta>
                <Badge :variant="getStatusBadge(olympiad.status).variant" dot>
                    {{ getStatusBadge(olympiad.status).label }}
                </Badge>
                <Badge v-if="olympiad.type" variant="neutral">{{ olympiad.type }}</Badge>
                <Badge v-if="olympiad.stage" variant="neutral">{{ olympiad.stage }}</Badge>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <div v-if="coverImage" class="overflow-hidden rounded-lg border border-border bg-surface-2">
                    <img :src="coverImage" :alt="olympiad.title" class="aspect-[3/1] w-full object-cover">
                </div>

                <Card>
                    <Tabs v-model="activeTab" :tabs="tabs" label="Olimpiada bo'limlari">
                        <!-- About Tab -->
                        <template #about>
                            <div class="space-y-6">
                                <div v-if="olympiad.description" :class="richText" v-html="olympiad.description"></div>
                                <p v-else-if="olympiad.short_description" class="whitespace-pre-line text-sm leading-relaxed text-fg">{{ olympiad.short_description }}</p>
                                <p v-else class="text-sm text-fg-muted">Tavsif kiritilmagan.</p>

                                <Alert v-if="olympiad.rules" variant="warning" title="Qoidalar" :icon="ScaleIcon">
                                    <div :class="richText" v-html="olympiad.rules"></div>
                                </Alert>
                            </div>
                        </template>

                        <!-- Sections Tab -->
                        <template #sections>
                            <ul v-if="olympiad.sections?.length" class="divide-y divide-border">
                                <li v-for="(section, index) in olympiad.sections" :key="section.id"
                                    class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                                        <component :is="getSectionIcon(section.type)" class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-medium text-fg">{{ section.title }}</h4>
                                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-13 text-fg-subtle tabular-nums">
                                            <span class="inline-flex items-center gap-1">
                                                <ClockIcon class="h-4 w-4" aria-hidden="true" />
                                                {{ section.duration_minutes }} daqiqa
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <ChartBarIcon class="h-4 w-4" aria-hidden="true" />
                                                {{ section.max_points }} ball
                                            </span>
                                            <span>{{ section.weight_percent }}% vazn</span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-fg-subtle tabular-nums" aria-hidden="true">
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                </li>
                            </ul>
                            <EmptyState v-else size="sm" :icon="PuzzlePieceIcon" title="Bo'limlar hali qo'shilmagan" />
                        </template>

                        <!-- Prizes Tab -->
                        <template #prizes>
                            <div class="space-y-6">
                                <div v-if="places.length" class="grid gap-3 sm:grid-cols-3">
                                    <div v-for="item in places" :key="item.place"
                                        class="rounded-lg border border-border bg-surface p-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="['inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold ring-1 ring-inset tabular-nums', placeTone(item.place)]">
                                                <TrophyIcon v-if="item.place === 1" class="h-4 w-4" aria-hidden="true" />
                                                <span v-else>{{ item.place }}</span>
                                            </span>
                                            <span class="text-sm font-medium text-fg-muted">{{ item.place }}-o'rin</span>
                                        </div>
                                        <p class="mt-3 text-lg font-semibold text-fg tabular-nums">
                                            {{ formatPrice(item.amount) }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="olympiad.reward_config" class="rounded-lg bg-surface-2 p-4">
                                    <h4 class="flex items-center gap-2 text-sm font-semibold text-fg">
                                        <GiftIcon class="h-5 w-5 text-brand" aria-hidden="true" />
                                        Qo'shimcha mukofotlar
                                    </h4>
                                    <ul class="mt-3 space-y-2 text-sm text-fg-muted">
                                        <li v-if="olympiad.reward_config.coins_per_correct" class="flex items-start gap-2">
                                            <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                                            <span>Har bir to'g'ri javob: <span class="font-medium text-fg tabular-nums">{{ olympiad.reward_config.coins_per_correct }} coin</span></span>
                                        </li>
                                        <li v-if="olympiad.reward_config.top_10_coins" class="flex items-start gap-2">
                                            <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                                            <span>Top 10 uchun: <span class="font-medium text-fg tabular-nums">{{ olympiad.reward_config.top_10_coins }} coin</span></span>
                                        </li>
                                        <li v-if="olympiad.reward_config.advancement" class="flex items-start gap-2">
                                            <CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                                            <span>Keyingi bosqichga o'tish imkoniyati</span>
                                        </li>
                                    </ul>
                                </div>

                                <EmptyState v-if="!places.length && !olympiad.reward_config" size="sm" :icon="TrophyIcon"
                                    title="Sovrinlar e'lon qilinmagan" />
                            </div>
                        </template>
                    </Tabs>
                </Card>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-6">
                <Card title="Ishtirok" class="lg:sticky lg:top-24">
                    <div>
                        <p class="text-13 text-fg-muted">Ro'yxatdan o'tish narxi</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums"
                            :class="olympiad.registration_fee ? 'text-fg' : 'text-success'">
                            {{ formatPrice(olympiad.registration_fee) }}
                        </p>
                    </div>

                    <dl class="mt-4 divide-y divide-border border-t border-border text-sm">
                        <div v-if="olympiad.registration_end_at" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Ro'yxat tugashi</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ olympiad.registration_end_at }}</dd>
                        </div>
                        <div v-if="olympiad.olympiad_start_at" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Boshlanishi</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ olympiad.olympiad_start_at }}</dd>
                        </div>
                        <div v-if="olympiad.total_duration" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Davomiyligi</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ olympiad.total_duration }} daqiqa</dd>
                        </div>
                    </dl>

                    <!-- Action -->
                    <div class="mt-5">
                        <div v-if="registration">
                            <!-- Already registered -->
                            <div v-if="registration.status === 'confirmed'" class="space-y-3">
                                <Alert variant="success">Ro'yxatdan o'tgansiz</Alert>
                                <Button v-if="olympiad.status === 'live'" block size="lg" :icon-left="PlayIcon"
                                    @click="handleStartExam">
                                    Imtihonni boshlash
                                </Button>

                                <!-- Demo Purchase -->
                                <div v-if="olympiad.demo_price > 0 && !registration.demo_purchased"
                                    class="rounded-md border border-border bg-surface-2 p-4">
                                    <p class="text-sm text-fg-muted">Demo testni sinab ko'ring</p>
                                    <Button variant="secondary" block class="mt-3"
                                        :href="route('student.olympiads.show', olympiad.slug) + '#demo'">
                                        Demo sotib olish
                                        <span class="tabular-nums">({{ formatPrice(olympiad.demo_price) }})</span>
                                    </Button>
                                </div>
                            </div>

                            <div v-else-if="registration.status === 'pending_payment'" class="space-y-3">
                                <Alert variant="warning">To'lov hali yakunlanmagan</Alert>
                                <Button block size="lg" :icon-left="CreditCardIcon"
                                    :href="route('student.olympiads.payment', { slug: olympiad.slug, registration: registration.id })">
                                    To'lovni yakunlash
                                </Button>
                            </div>
                        </div>

                        <div v-else-if="canRegister.can_register">
                            <Button block size="lg" :loading="isRegistering" @click="handleRegister">
                                Ro'yxatdan o'tish
                            </Button>
                        </div>

                        <Alert v-else variant="info">{{ closedReason }}</Alert>
                    </div>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>
