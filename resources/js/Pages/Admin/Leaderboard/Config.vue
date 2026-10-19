<template>
    <AdminLayout>

        <Head title="Reyting sozlamalari" />

        <PageHeader title="Reyting" description="Leaderboard davri va holatini boshqarish" />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <Card title="Sozlamalar">
                    <form id="leaderboard-settings" class="space-y-5" @submit.prevent="updateSettings">
                        <Select v-model="form.period" label="Davr" :options="periodOptions" :error="form.errors.period"
                            hint="Reyting qaysi davr bo'yicha hisoblanadi" />
                        <Switch v-model="form.is_active" label="Reyting faol"
                            description="O'quvchilarga reyting jadvali ko'rinadi" />
                    </form>
                    <template #footer>
                        <div class="flex justify-end">
                            <Button type="submit" form="leaderboard-settings" :loading="form.processing">Saqlash</Button>
                        </div>
                    </template>
                </Card>

                <section class="rounded-lg border border-danger-100 bg-surface p-5 shadow-xs sm:p-6"
                    aria-labelledby="danger-zone-title">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-danger-50 text-danger">
                            <ExclamationTriangleIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="danger-zone-title" class="text-base font-semibold text-fg">Xavfli hudud</h2>
                            <p class="mt-1 text-13 text-fg-muted">
                                Majburiy yangilash barcha foydalanuvchilarning davriy ballarini 0 ga tushiradi.
                            </p>
                        </div>
                    </div>
                    <Button variant="danger" block class="mt-4" :icon-left="ArrowPathIcon" @click="resetModal = true">
                        Reytingni yangilash
                    </Button>
                </section>
            </div>

            <Card class="lg:col-span-2" title="Top 10" description="Hozirgi holat, XP bo'yicha" padding="none">
                <ol v-if="topUsers?.length" class="divide-y divide-border">
                    <li v-for="(user, index) in topUsers" :key="user.id" class="flex items-center gap-4 px-5 py-3 sm:px-6">
                        <span :class="[
                            'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-13 font-semibold tabular-nums',
                            index === 0 ? 'bg-warning-50 text-warning ring-1 ring-warning-100'
                                : index < 3 ? 'bg-brand-50 text-brand' : 'bg-surface-2 text-fg-muted',
                        ]">
                            <TrophyIcon v-if="index === 0" class="h-4 w-4" aria-hidden="true" />
                            <template v-else>{{ index + 1 }}</template>
                            <span v-if="index === 0" class="sr-only">1-o'rin</span>
                        </span>
                        <Avatar :src="user.avatar" :name="`${user.first_name} ${user.last_name}`" size="sm" />
                        <p class="min-w-0 flex-1 truncate text-sm font-medium text-fg">
                            {{ user.first_name }} {{ user.last_name }}
                        </p>
                        <span class="shrink-0 text-sm font-semibold text-fg tabular-nums">
                            {{ formatNumber(user.xp) }} <span class="text-13 font-medium text-fg-subtle">XP</span>
                        </span>
                    </li>
                </ol>
                <div v-else class="px-6 py-6">
                    <EmptyState :icon="ChartBarIcon" title="Reyting bo'sh" description="Hali XP to'plagan o'quvchilar yo'q"
                        size="sm" />
                </div>
            </Card>
        </div>

        <DeleteConfirm :show="resetModal" :loading="resetting" title="Reytingni yangilash"
            message="Barcha foydalanuvchilarning davriy ballari 0 ga tushadi. Bu amalni ortga qaytarib bo'lmaydi."
            confirm-text="Yangilash" @confirm="resetLeaderboard" @cancel="resetModal = false" />
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import { ArrowPathIcon, ChartBarIcon, ExclamationTriangleIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Button, Card, EmptyState, PageHeader, Select, Switch } from '@/Components/UI';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    topUsers: Array,
    settings: Object,
});

const periodOptions = [
    { value: 'weekly', label: 'Haftalik' },
    { value: 'monthly', label: 'Oylik' },
    { value: 'all_time', label: 'Umumiy' },
];

const form = useForm({
    period: props.settings.period,
    is_active: props.settings.is_active,
});

const resetModal = ref(false);
const resetting = ref(false);

const updateSettings = () => {
    form.put(route('admin.leaderboard.update'), { preserveScroll: true });
};

const resetLeaderboard = () => {
    resetting.value = true;
    router.post(route('admin.leaderboard.reset'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            resetModal.value = false;
        },
        onFinish: () => {
            resetting.value = false;
        },
    });
};
</script>
