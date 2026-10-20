<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Card, EmptyState, PageHeader, Table } from '@/Components/UI';
import { ChevronRightIcon, UserIcon, LinkIcon, ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    payment: Object,
    // the online order (Payme / Click) with its provider transactions; null for manual payments
    order: { type: Object, default: null },
});

const TYPES = {
    course_purchase: { label: 'Kurs', variant: 'brand' },
    subscription: { label: 'Obuna', variant: 'info' },
    subscription_renewal: { label: 'Obuna (uzaytirish)', variant: 'info' },
    coin_package: { label: 'Coin paketi', variant: 'warning' },
    olympiad_registration: { label: 'Olimpiada', variant: 'math' },
};
const STATUSES = {
    pending: { label: 'Kutilmoqda', variant: 'neutral' },
    processing: { label: "To'lanmoqda", variant: 'warning' },
    completed: { label: 'Tugallangan', variant: 'success' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
    failed: { label: 'Xato', variant: 'danger' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
};
const ORDER_STATUSES = {
    pending: { label: "To'lov kutilmoqda", variant: 'warning' },
    paid: { label: "To'langan", variant: 'success' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};
// Payme states: 1 created, 2 performed, -1 cancelled, -2 cancelled after perform (Click: 1, 2, -1)
const STATE_VARIANTS = { 1: 'warning', 2: 'success', '-1': 'danger', '-2': 'neutral' };
const PROVIDERS = { payme: 'Payme', click: 'Click', uzum: 'Uzum', manual: "Qo'lda / promokod" };

const typeOf = (t) => TYPES[t] || { label: t || '—', variant: 'neutral' };
const statusOf = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };
const orderStatusOf = (s) => ORDER_STATUSES[s] || { label: s || '—', variant: 'neutral' };
const money = (v) => Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const hasRoute = (name) => {
    try {
        return route().has(name);
    } catch {
        return false;
    }
};

const heading = computed(() => (props.order ? `№${props.order.id}` : (props.payment.transaction_id ? `#${props.payment.transaction_id}` : '')));
const breadcrumbs = computed(() => [
    { label: "To'lovlar", href: route('admin.payments.index') },
    { label: heading.value || "To'lov" },
]);

const granted = computed(() => {
    const f = props.order?.fulfilment;
    if (!f) return null;
    if (f.coins) return `${money(f.coins)} coin hisobga qo'shildi`;
    if (f.enrollment_id) return f.already_had_access ? 'Kursga kirish oldin ochilgan edi' : 'Kursga yozildi';
    if (f.subscription_id) return f.renewal ? 'Obuna muddati uzaytirildi' : 'Obuna faollashtirildi';
    if (f.registration_id) return f.already_confirmed ? "Ro'yxat oldin tasdiqlangan edi" : "Olimpiada ro'yxati tasdiqlandi";
    return null;
});

const txColumns = [
    { key: 'provider_transaction_id', label: 'Tizimdagi ID' },
    { key: 'state', label: 'Holat' },
    { key: 'create_time', label: 'Yaratilgan', hideBelow: 'md' },
    { key: 'perform_time', label: "O'tkazilgan", hideBelow: 'lg' },
    { key: 'cancel_time', label: 'Bekor', hideBelow: 'lg' },
    { key: 'amount', label: 'Summa', numeric: true },
];
</script>

<template>
    <AdminLayout>
        <Head :title="`To'lov ${heading || payment.id}`" />

        <PageHeader :breadcrumbs="breadcrumbs">
            <template #title>
                <h1 class="text-2xl font-semibold tracking-tight text-fg">
                    To'lov <span class="font-mono">{{ heading || payment.id }}</span>
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
                    <p v-if="Number(payment.discount_amount) > 0" class="mt-1 text-13 tabular-nums text-fg-muted">
                        Asl narx {{ money(payment.original_amount) }} so'm, chegirma <span class="text-success">−{{ money(payment.discount_amount) }} so'm</span>
                    </p>
                    <p v-if="payment.promo_code" class="mt-2 text-13 text-fg-muted">
                        Promo kod
                        <code class="rounded-sm bg-surface-2 px-1.5 py-0.5 font-mono text-xs text-fg">{{ payment.promo_code.code }}</code>
                        <span class="ml-1 text-success">−{{ payment.promo_code.type === 'fixed' ? `${money(payment.promo_code.discount)} so'm` : `${payment.promo_code.discount}%` }}</span>
                    </p>
                </Card>

                <Card title="Tranzaksiya tafsilotlari">
                    <dl class="divide-y divide-border text-sm">
                        <div v-if="order" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Buyurtma raqami</dt>
                            <dd class="text-right font-mono text-13 text-fg">№{{ order.id }}</dd>
                        </div>
                        <div v-if="order" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Xarid</dt>
                            <dd class="text-right font-medium">{{ order.title }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tranzaksiya ID</dt>
                            <dd class="break-all text-right font-mono text-13 text-fg">{{ payment.transaction_id || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">To'lov tizimi</dt>
                            <dd class="text-right font-medium">{{ PROVIDERS[payment.payment_method] || payment.payment_method || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tur</dt>
                            <dd class="text-right font-medium">{{ typeOf(payment.type).label }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Holat</dt>
                            <dd class="text-right font-medium">{{ statusOf(payment.status).label }}</dd>
                        </div>
                        <div v-if="order" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Buyurtma holati</dt>
                            <dd class="text-right"><Badge :variant="orderStatusOf(order.status).variant" size="sm">{{ orderStatusOf(order.status).label }}</Badge></dd>
                        </div>
                        <div v-if="granted" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Berildi</dt>
                            <dd class="text-right font-medium">{{ granted }}<span v-if="order.fulfilled_at" class="block text-13 font-normal tabular-nums text-fg-subtle">{{ order.fulfilled_at }}</span></dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Sana</dt>
                            <dd class="text-right font-medium tabular-nums">{{ payment.created_at }}</dd>
                        </div>
                        <div v-if="payment.paid_at" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">To'langan</dt>
                            <dd class="text-right font-medium tabular-nums">{{ payment.paid_at }}</dd>
                        </div>
                        <div v-if="payment.refunded_at" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Qaytarilgan</dt>
                            <dd class="text-right font-medium tabular-nums">{{ payment.refunded_at }}<span v-if="payment.refund_reason" class="block text-13 font-normal text-fg-subtle">{{ payment.refund_reason }}</span></dd>
                        </div>
                    </dl>
                </Card>

                <Card v-if="order" padding="none" title="Provayder tranzaksiyalari"
                    description="Payme / Click so'rovlari: holat 1 — yaratilgan, 2 — o'tkazilgan, −1 — bekor, −2 — o'tkazilgandan keyin bekor">
                    <Table :columns="txColumns" :rows="order.transactions" :bordered="false" dense caption="Provayder tranzaksiyalari"
                        empty-title="Hali tranzaksiya yo'q" empty-description="Talaba to'lov sahifasiga o'tgan, lekin to'lov boshlanmagan">
                        <template #cell-provider_transaction_id="{ row }">
                            <span class="font-mono text-13 text-fg">{{ row.provider_transaction_id }}</span>
                            <p class="text-xs text-fg-subtle">
                                {{ PROVIDERS[row.provider] || row.provider }} · ichki №{{ row.id }}<template v-if="row.click_paydoc_id"> · paydoc {{ row.click_paydoc_id }}</template>
                            </p>
                            <a v-if="row.fiscal_url" :href="row.fiscal_url" target="_blank" rel="noopener" class="focus-ring rounded-sm text-xs text-brand hover:underline">Fiskal chek</a>
                        </template>
                        <template #cell-state="{ row }">
                            <Badge :variant="STATE_VARIANTS[row.state] || 'neutral'" size="sm">{{ row.state }} · {{ row.state_label }}</Badge>
                            <p v-if="row.reason_label" class="mt-1 text-xs text-fg-subtle">{{ row.reason !== null ? `Sabab ${row.reason}: ` : '' }}{{ row.reason_label }}</p>
                        </template>
                        <template #cell-create_time="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                        <template #cell-perform_time="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                        <template #cell-cancel_time="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                        <template #cell-amount="{ value }"><span class="whitespace-nowrap">{{ money(value) }} so'm</span></template>
                        <template #empty>
                            <EmptyState :icon="ArrowsRightLeftIcon" title="Hali tranzaksiya yo'q" size="sm"
                                description="Talaba to'lov sahifasiga o'tgan, lekin to'lov boshlanmagan" />
                        </template>
                    </Table>
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
                    <div v-else-if="order" class="px-5 py-3">
                        <p class="text-13 text-fg-subtle">{{ typeOf(payment.type).label }}</p>
                        <p class="text-sm font-medium text-fg">{{ order.title }}</p>
                    </div>
                    <EmptyState v-else :icon="LinkIcon" title="Bog'liq ma'lumot yo'q" size="sm" class="py-6" />
                </Card>
            </aside>
        </div>
    </AdminLayout>
</template>
