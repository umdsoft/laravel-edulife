<template>
    <AdminLayout>

        <Head title="Admin Dashboard" />

        <PageHeader title="Dashboard" description="Platformaning umumiy holati">
            <template v-if="has('admin.reports.index')" #actions>
                <Button variant="secondary" :icon-left="DocumentChartBarIcon" :href="route('admin.reports.index')">
                    Hisobotlar
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- KPIs -->
            <section aria-label="Asosiy ko'rsatkichlar" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatsCard title="Foydalanuvchilar" :value="stats.users?.total" :trend="stats.users?.trend" trend-label="o'tgan 30 kunga nisbatan" color="blue"
                    :href="link('admin.users.index')" />
                <StatsCard title="O'qituvchilar" :value="stats.teachers?.total" :trend="stats.teachers?.trend" trend-label="o'tgan 30 kunga nisbatan"
                    color="green" :href="link('admin.teachers.index')" />
                <StatsCard title="Kurslar" :value="stats.courses?.total" :trend="stats.courses?.trend" trend-label="o'tgan 30 kunga nisbatan" color="purple"
                    :href="link('admin.courses.index')" />
                <StatsCard title="Daromad" :value="stats.revenue?.total" :trend="stats.revenue?.trend" trend-label="o'tgan 30 kunga nisbatan" color="yellow"
                    format-as-currency :href="link('admin.payments.index')" />
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent users -->
                <Card title="So'nggi foydalanuvchilar" description="Oxirgi ro'yxatdan o'tganlar" padding="none">
                    <template v-if="has('admin.users.index')" #actions>
                        <Button size="sm" variant="ghost" :icon-right="ArrowRightIcon" :href="route('admin.users.index')">
                            Barchasi
                        </Button>
                    </template>

                    <ul v-if="recentUsers?.length" class="divide-y divide-border">
                        <li v-for="user in recentUsers" :key="user.id">
                            <component :is="has('admin.users.show') ? Link : 'div'"
                                :href="has('admin.users.show') ? route('admin.users.show', user.id) : undefined"
                                class="focus-ring flex items-center gap-3 px-5 py-3 transition-colors hover:bg-surface-2/60 sm:px-6">
                                <Avatar :src="user.avatar" :name="user.name" size="md" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-fg">{{ user.name }}</p>
                                    <p class="truncate text-13 text-fg-subtle tabular-nums">{{ user.phone || user.email || '—' }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <Badge :variant="roleMeta(user.role).variant" size="sm">{{ roleMeta(user.role).label }}</Badge>
                                    <span class="text-xs text-fg-subtle tabular-nums">{{ formatDate(user.created_at) }}</span>
                                </div>
                            </component>
                        </li>
                    </ul>
                    <div v-else class="px-6 py-6">
                        <EmptyState :icon="UsersIcon" title="Foydalanuvchilar yo'q"
                            description="Yangi ro'yxatdan o'tganlar shu yerda ko'rinadi" size="sm" />
                    </div>
                </Card>

                <!-- Recent payments -->
                <Card title="So'nggi to'lovlar" description="Oxirgi 5 ta tranzaksiya" padding="none">
                    <template v-if="has('admin.payments.index')" #actions>
                        <Button size="sm" variant="ghost" :icon-right="ArrowRightIcon"
                            :href="route('admin.payments.index')">
                            Barchasi
                        </Button>
                    </template>

                    <ul v-if="recentPayments?.length" class="divide-y divide-border">
                        <li v-for="payment in recentPayments" :key="payment.id"
                            class="flex items-center gap-3 px-5 py-3 sm:px-6">
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-subtle">
                                <CreditCardIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-fg">{{ payment.user_name }}</p>
                                <p class="truncate text-13 text-fg-subtle">
                                    {{ paymentType(payment.type) }}
                                    <span v-if="payment.created_at" class="tabular-nums"> · {{ formatDate(payment.created_at) }}</span>
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="text-sm font-semibold text-fg tabular-nums">{{ formatMoney(payment.amount) }}</span>
                                <Badge :variant="paymentStatusMeta(payment.status).variant" size="sm" dot>
                                    {{ paymentStatusMeta(payment.status).label }}
                                </Badge>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="px-6 py-6">
                        <EmptyState :icon="CreditCardIcon" title="To'lovlar yo'q"
                            description="Birinchi to'lov amalga oshirilgach shu yerda ko'rinadi" size="sm" />
                    </div>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon, CreditCardIcon, DocumentChartBarIcon, UsersIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';
import { Avatar, Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';
import { formatDate, formatMoney, paymentStatusMeta, roleMeta } from '@/Components/Admin/format';
import { routeHas } from '@/Utils/nav';

defineProps({
    stats: Object,
    recentUsers: Array,
    recentPayments: Array,
});

const has = routeHas;
const link = (name) => (has(name) ? route(name) : null);

const PAYMENT_TYPES = {
    course: 'Kurs',
    subscription: 'Obuna',
    coin_package: 'COIN paketi',
    coins: 'COIN',
    olympiad: 'Olimpiada',
};
const paymentType = (type) => PAYMENT_TYPES[type] || (type ? type.charAt(0).toUpperCase() + type.slice(1) : 'To\'lov');
</script>
