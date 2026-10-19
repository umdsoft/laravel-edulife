<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowRightIcon, CalendarIcon, TrophyIcon, UsersIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';

const props = defineProps({
    // Controller sends a plain collection (array); a paginator object is also supported
    tournaments: [Object, Array],
});

const list = computed(() => {
    const t = props.tournaments;
    if (Array.isArray(t)) return t;
    return t?.data || [];
});
const isPaginated = computed(() => !Array.isArray(props.tournaments) && !!props.tournaments?.links);

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const STATUS = {
    upcoming: { label: 'Tez orada', variant: 'info' },
    registration: { label: 'Ro\'yxatdan o\'tish', variant: 'success' },
    in_progress: { label: 'Davom etmoqda', variant: 'brand' },
    completed: { label: 'Yakunlangan', variant: 'neutral' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};
const statusOf = (s) => STATUS[s] || { label: s || '—', variant: 'neutral' };
const titleOf = (t) => t.name || t.title || 'Turnir';
</script>

<template>

    <Head title="Turnirlar" />

    <StudentLayout title="Turnirlar">
        <PageHeader title="Turnirlar" description="Sovrinlar uchun bellashuvlar: ro'yxatdan o'ting va bosqichma-bosqich g'olib bo'ling."
            :back="route('student.battles.index')" back-label="Janglar" />

        <div class="space-y-6">
            <EmptyState v-if="list.length === 0" :icon="TrophyIcon" bordered title="Hozircha turnir yo'q"
                description="Yangi turnirlar e'lon qilinganda shu yerda ko'rinadi." />

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="tournament in list" :key="tournament.id" class="flex flex-col">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-warning-50 text-warning">
                                <TrophyIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <Badge :variant="statusOf(tournament.status).variant" dot>{{ statusOf(tournament.status).label }}</Badge>
                        </div>

                        <h3 class="mt-4 line-clamp-2 text-base font-semibold text-fg">{{ titleOf(tournament) }}</h3>
                        <p v-if="tournament.direction?.name" class="mt-0.5 text-13 text-fg-subtle">{{ tournament.direction.name }}</p>

                        <dl class="mt-4 flex-1 divide-y divide-border text-sm">
                            <div class="flex items-center justify-between py-2">
                                <dt class="flex items-center gap-1.5 text-fg-muted">
                                    <CalendarIcon class="h-4 w-4" aria-hidden="true" /> Boshlanish
                                </dt>
                                <dd class="font-medium tabular-nums text-fg">{{ formatDate(tournament.starts_at || tournament.start_date) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <dt class="flex items-center gap-1.5 text-fg-muted">
                                    <UsersIcon class="h-4 w-4" aria-hidden="true" /> Ishtirokchilar
                                </dt>
                                <dd class="font-medium tabular-nums text-fg">
                                    {{ tournament.participants_count || 0 }} / {{ tournament.max_participants || '—' }}
                                </dd>
                            </div>
                        </dl>

                        <Button class="mt-4" variant="secondary" block :icon-right="ArrowRightIcon"
                            :href="route('student.tournaments.show', tournament.id)">
                            Batafsil
                        </Button>
                    </div>
                </Card>
            </div>

            <Pagination v-if="isPaginated" :data="tournaments" />
        </div>
    </StudentLayout>
</template>
