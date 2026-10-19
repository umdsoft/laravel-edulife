<template>
    <StudentLayout title="Do'stlar">
        <Head title="Do'stlar" />

        <PageHeader title="Do'stlar" description="Siz kuzatayotgan o'quvchilarning so'nggi faoliyati." />

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Kuzatuvchilar" :value="stats?.followers ?? 0" :icon="UsersIcon" tone="info"
                    :href="route('student.friends.followers')" />
                <StatCard label="Kuzatilmoqda" :value="stats?.following ?? 0" :icon="UserPlusIcon" tone="brand"
                    :href="route('student.friends.following')" />
            </div>

            <Card title="Faoliyat lentasi" padding="none">
                <EmptyState v-if="!activities || activities.length === 0" :icon="UsersIcon" size="sm"
                    title="Faoliyat yo'q" description="Boshqa o'quvchilarni kuzating — ularning yutuqlari shu yerda ko'rinadi.">
                    <Button v-if="route().has('student.leaderboard.index')" size="sm" variant="secondary"
                        :icon-left="TrophyIcon" :href="route('student.leaderboard.index')">Reytingdan topish</Button>
                </EmptyState>

                <ul v-else class="divide-y divide-border">
                    <li v-for="activity in activities" :key="activity.id" class="flex items-start gap-3 px-5 py-4 sm:px-6">
                        <Avatar :src="activity.user?.avatar_url || activity.user?.avatar" :name="userName(activity.user)" size="md" class="shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-fg">
                                <span class="font-semibold">{{ userName(activity.user) }}</span>
                            </p>
                            <p class="mt-0.5 flex items-start gap-1.5 text-sm text-fg-muted">
                                <span v-if="activity.icon" class="shrink-0" aria-hidden="true">{{ activity.icon }}</span>
                                <span>{{ activity.title }}</span>
                            </p>
                            <p v-if="activity.description" class="mt-0.5 text-13 text-fg-subtle">{{ activity.description }}</p>
                            <time class="mt-1 block text-xs tabular-nums text-fg-subtle" :datetime="activity.occurred_at">
                                {{ formatDate(activity.occurred_at) }}
                            </time>
                        </div>
                    </li>
                </ul>
            </Card>
        </div>
    </StudentLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { TrophyIcon, UserPlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Button, Card, EmptyState, PageHeader, StatCard } from '@/Components/UI';

const props = defineProps({
    activities: Array,
    stats: Object,
});

const userName = (u) => {
    if (!u) return 'O\'quvchi';
    return u.full_name || [u.first_name, u.last_name].filter(Boolean).join(' ') || 'O\'quvchi';
};

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
</script>
