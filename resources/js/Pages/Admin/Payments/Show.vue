<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Card, EmptyState, PageHeader } from '@/Components/UI';
import { ChevronRightIcon, UserIcon, LinkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    payment: Object,
});

const TYPES = {
    subscription: { label: 'Obuna', variant: 'info' },
    course: { label: 'Kurs', variant: 'brand' },
    coins: { label: 'Coin', variant: 'warning' },
};
const STATUSES = {
    pending: { label: 'Kutilmoqda', variant: 'warning' },
    completed: { label: 'Tugallangan', variant: 'success' },
    failed: { label: 'Xato', variant: 'danger' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
};
const typeOf = (t) => TYPES[t] || { label: t || '—', variant: 'neutral' };
const statusOf = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };
const money = (v) => Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const hasRoute = (name) => {
    try {
        return route().has(name);
    } catch {
        return false;
    }
};

const breadcrumbs = computed(() => [
    { label: "To'lovlar", href: route('admin.payments.index') },
    { label: props.payment.transaction_id ? `#${props.payment.transaction_id}` : "To'lov" },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="`To'lov #${payment.transaction_id || payment.id}`" />

        <PageHeader :breadcrumbs="breadcrumbs">
            <template #title>
                <h1 class="text-2xl font-semibold tracking-tight text-fg">
                    To'lov <span class="font-mono">#{{ payment.transaction_id || payment.id }}</span>
                </h1>
            </template>
            <template #meta>
                <Badge :variant="statusOf(payment.status).variant" dot>{{ statusOf(payment.status).label }}</Badge>
                <Badge :variant="typeOf(payment.type).variant">{{ typeOf(payment.type).label }}</Badge>
                <span class="text-13 tabular-nums text-fg-subtle">{{ payment.created_at }}</span>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <p class="text-13 font-medium text-fg-muted">Summa</p>
                    <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums text-fg">
                        {{ money(payment.amount) }} <span class="text-lg font-medium text-fg-subtle">so'm</span>
                    </p>
                    <p v-if="payment.promo_code" class="mt-2 text-13 text-fg-muted">
                        Promo kod
                        <code class="rounded-sm bg-surface-2 px-1.5 py-0.5 font-mono text-xs text-fg">{{ payment.promo_code.code }}</code>
                        <span class="ml-1 text-success">−{{ payment.promo_code.discount }}%</span>
                    </p>
                </Card>

                <Card title="Tranzaksiya tafsilotlari">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tranzaksiya ID</dt>
                            <dd class="break-all text-right font-mono text-13 text-fg">{{ payment.transaction_id || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">To'lov tizimi</dt>
                            <dd class="text-right font-medium capitalize">{{ payment.payment_method || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tur</dt>
                            <dd class="text-right font-medium">{{ typeOf(payment.type).label }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Holat</dt>
                            <dd class="text-right font-medium">{{ statusOf(payment.status).label }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Sana</dt>
                            <dd class="text-right font-medium tabular-nums">{{ payment.created_at }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Foydalanuvchi">
                    <div v-if="payment.user" class="space-y-4">
                        <div class="flex items-center gap-3">
                            <Avatar :name="payment.user.full_name" size="md" />
                            <div class="min-w-0">
                                <Link :href="route('admin.users.show', payment.user.id)"
                                    class="focus-ring block truncate rounded-sm font-medium text-fg hover:text-brand">
                                    {{ payment.user.full_name }}
                                </Link>
                                <p v-if="payment.user.phone" class="text-13 tabular-nums text-fg-subtle">{{ payment.user.phone }}</p>
                                <p v-if="payment.user.email" class="truncate text-13 text-fg-subtle">{{ payment.user.email }}</p>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else :icon="UserIcon" title="Foydalanuvchi topilmadi" size="sm" />
                </Card>

                <Card padding="none" title="Bog'liq ma'lumot">
                    <ul v-if="payment.course || payment.subscription_plan" class="divide-y divide-border">
                        <li v-if="payment.course">
                            <Link v-if="hasRoute('admin.courses.show')" :href="route('admin.courses.show', payment.course.id)"
                                class="focus-ring flex items-center gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-13 text-fg-subtle">Kurs</p>
                                    <p class="truncate text-sm font-medium text-fg">{{ payment.course.title }}</p>
                                </div>
                                <ChevronRightIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                            </Link>
                            <div v-else class="px-5 py-3">
                                <p class="text-13 text-fg-subtle">Kurs</p>
                                <p class="text-sm font-medium text-fg">{{ payment.course.title }}</p>
                            </div>
                        </li>
                        <li v-if="payment.subscription_plan" class="px-5 py-3">
                            <p class="text-13 text-fg-subtle">Obuna tarifi</p>
                            <p class="text-sm font-medium text-fg">{{ payment.subscription_plan.name }}</p>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="LinkIcon" title="Bog'liq ma'lumot yo'q" size="sm" class="py-6" />
                </Card>
            </aside>
        </div>
    </AdminLayout>
</template>
