<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, StatCard } from '@/Components/UI';
import {
    AcademicCapIcon,
    ArrowDownTrayIcon,
    ArrowRightIcon,
    BanknotesIcon,
    CheckCircleIcon,
    CircleStackIcon,
    ClipboardDocumentListIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    history: Array,
    pagination: Object,
    statistics: Object,
});

const getStatusBadge = (status) => {
    const badges = {
        'completed': { label: 'Yakunlangan', variant: 'success' },
        'disqualified': { label: 'Diskvalifikatsiya', variant: 'danger' },
        'in_progress': { label: 'Jarayonda', variant: 'info' },
    };
    return badges[status] || { label: status, variant: 'neutral' };
};

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const formatPrice = (price) => {
    if (!price || Number(price) === 0) return '—';
    return `${groupDigits(price)} so'm`;
};

// Calm rank chip: top 3 → warning tint with trophy, top 10 → brand tint, others neutral
const rankChipClass = (rank) => {
    if (rank && rank <= 3) return 'bg-warning-50 text-warning ring-warning-100';
    if (rank && rank <= 10) return 'bg-brand-50 text-brand ring-brand-100';
    return 'bg-surface-2 text-fg-muted ring-border';
};

const goToPage = (page) => router.visit(route('student.olympiad-history', { page }));
</script>

<template>
    <StudentLayout title="Olimpiada tarixi">
        <Head title="Olimpiada tarixi" />

        <PageHeader title="Olimpiada tarixi" description="Barcha ishtirok etgan olimpiadalaringiz" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: 'Tarix' },
        ]" />

        <div class="space-y-6">
            <!-- Statistics -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <StatCard label="Ishtirok etilgan" :value="statistics.total_participated" :icon="ClipboardDocumentListIcon"
                    tone="brand" />
                <StatCard label="Yakunlangan" :value="statistics.total_completed" :icon="CheckCircleIcon" tone="success" />
                <StatCard label="Eng yaxshi o'rin" :value="statistics.best_rank ? `#${statistics.best_rank}` : null"
                    :icon="TrophyIcon" tone="warning" />
                <StatCard label="Jami coin" :value="Number(statistics.total_coins) || 0" :icon="CircleStackIcon"
                    tone="neutral" />
                <StatCard label="Naqd sovrin" :value="formatPrice(statistics.total_cash)" :icon="BanknotesIcon"
                    tone="success" />
                <StatCard label="Sertifikatlar" :value="statistics.certificates" :icon="AcademicCapIcon" tone="info" />
            </div>

            <!-- History List -->
            <Card v-if="history.length > 0" padding="none" title="Ishtiroklar">
                <ul class="divide-y divide-border">
                    <li v-for="item in history" :key="item.id"
                        class="flex flex-col gap-4 px-5 py-4 sm:px-6 md:flex-row md:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <!-- Rank chip -->
                            <span :class="['inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold ring-1 ring-inset tabular-nums', rankChipClass(item.rank)]"
                                :aria-label="item.rank ? `${item.rank}-o'rin` : 'O\'rin yo\'q'">
                                <TrophyIcon v-if="item.rank && item.rank <= 3" class="h-5 w-5" aria-hidden="true" />
                                <span v-else-if="item.rank" aria-hidden="true">{{ item.rank }}</span>
                                <span v-else aria-hidden="true">—</span>
                            </span>

                            <div class="min-w-0">
                                <h3 class="truncate font-medium text-fg">{{ item.olympiad_title }}</h3>
                                <p class="mt-0.5 flex flex-wrap gap-x-2 text-13 text-fg-subtle">
                                    <span>{{ item.olympiad_type }}</span>
                                    <span v-if="item.stage" aria-hidden="true">·</span>
                                    <span v-if="item.stage">{{ item.stage }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span class="tabular-nums">{{ item.created_at }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <dl class="flex flex-wrap gap-6 text-sm">
                            <div>
                                <dt class="text-xs text-fg-subtle">Ball</dt>
                                <dd class="font-semibold tabular-nums"
                                    :class="item.score_percent >= 60 ? 'text-success' : 'text-danger'">
                                    {{ item.score_percent }}%
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-fg-subtle">O'rin</dt>
                                <dd class="font-semibold text-fg tabular-nums">
                                    {{ item.rank ? `#${item.rank}` : '—' }}
                                    <span class="text-xs font-normal text-fg-subtle">/ {{ item.total_participants }}</span>
                                </dd>
                            </div>
                            <div v-if="item.coins_earned">
                                <dt class="text-xs text-fg-subtle">Coin</dt>
                                <dd class="font-semibold text-fg tabular-nums">+{{ item.coins_earned }}</dd>
                            </div>
                        </dl>

                        <!-- Status & Actions -->
                        <div class="flex items-center gap-3 md:w-56 md:justify-end">
                            <Badge :variant="getStatusBadge(item.status).variant" dot>
                                {{ getStatusBadge(item.status).label }}
                            </Badge>
                            <a v-if="item.certificate_issued"
                                :href="route('student.olympiads.certificate.download', item.olympiad_id)"
                                class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 hover:text-fg"
                                aria-label="Sertifikatni yuklab olish" title="Sertifikatni yuklab olish">
                                <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                            </a>
                        </div>
                    </li>
                </ul>

                <!-- Pagination -->
                <template v-if="pagination.last_page > 1" #footer>
                    <nav class="flex items-center justify-between gap-2" aria-label="Sahifalar">
                        <span class="text-13 text-fg-subtle tabular-nums">
                            {{ pagination.current_page }} / {{ pagination.last_page }} sahifa
                        </span>
                        <div class="flex flex-wrap items-center gap-1">
                            <button type="button"
                                class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 disabled:opacity-50"
                                :disabled="pagination.current_page <= 1" aria-label="Oldingi sahifa"
                                @click="goToPage(pagination.current_page - 1)">
                                <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                            <button v-for="page in pagination.last_page" :key="page" type="button" :class="[
                                'focus-ring hidden h-10 min-w-10 items-center justify-center rounded-md px-3 text-sm font-medium tabular-nums sm:inline-flex',
                                page === pagination.current_page
                                    ? 'bg-brand-600 text-white'
                                    : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                            ]" :aria-current="page === pagination.current_page ? 'page' : undefined"
                                @click="goToPage(page)">
                                {{ page }}
                            </button>
                            <button type="button"
                                class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 disabled:opacity-50"
                                :disabled="pagination.current_page >= pagination.last_page" aria-label="Keyingi sahifa"
                                @click="goToPage(pagination.current_page + 1)">
                                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                    </nav>
                </template>
            </Card>

            <!-- Empty State -->
            <Card v-else>
                <EmptyState :icon="TrophyIcon" title="Hali olimpiadalarda ishtirok etmagansiz"
                    description="Birinchi olimpiadangizni tanlang va o'z bilimingizni sinab ko'ring.">
                    <Button :href="route('student.olympiads.index')" :icon-right="ArrowRightIcon">
                        Olimpiadalarni ko'rish
                    </Button>
                </EmptyState>
            </Card>
        </div>
    </StudentLayout>
</template>
