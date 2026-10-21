<script setup>
/**
 * Paid exams: entries to refund by hand — sessions the teacher cancelled and entries paid twice.
 * Provider refund APIs are not called automatically: return the money (Payme / Click cabinet, cash …),
 * then press "Qaytarildi".
 */
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Alert, Badge, Button, EmptyState, Input, Modal, PageHeader, Table, Tabs } from '@/Components/UI';
import { ArrowUturnLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pending: { type: Array, default: () => [] },
    refunded: { type: Array, default: () => [] },
});

const tab = ref('pending');
const tabs = computed(() => [
    { key: 'pending', label: 'Qaytarish kerak', count: props.pending.length },
    { key: 'refunded', label: 'Qaytarilgan' },
]);
const rows = computed(() => (tab.value === 'pending' ? props.pending : props.refunded));
const total = computed(() => props.pending.reduce((sum, r) => sum + (Number(r.amount) || 0), 0));

const PROVIDERS = { payme: 'Payme', click: 'Click', manual: "Qo'lda" };
const money = (v) => String(Math.round(Number(v) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const pad = (n) => String(n).padStart(2, '0');
const dt = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const columns = computed(() => [
    { key: 'student', label: "O'quvchi" },
    { key: 'session', label: 'Imtihon', hideBelow: 'md' },
    { key: 'amount', label: 'Summa', numeric: true },
    { key: 'provider', label: 'Tizim', hideBelow: 'sm' },
    { key: tab.value === 'pending' ? 'requested_at' : 'refunded_at', label: tab.value === 'pending' ? "So'ralgan" : 'Qaytarilgan', hideBelow: 'lg' },
    ...(tab.value === 'pending' ? [{ key: 'actions', label: '', align: 'right' }] : [{ key: 'note', label: 'Izoh', hideBelow: 'md' }]),
]);

const modal = reactive({ row: null, note: '', loading: false });
const open = (row) => Object.assign(modal, { row, note: '', loading: false });
const confirm = () => {
    const row = modal.row;
    if (!row) return;
    modal.loading = true;
    const url = row.kind === 'duplicate'
        ? route('admin.exam.refunds.order', row.order_uuid || row.id)
        : route('admin.exam.refunds.purchase', row.id);
    router.post(url, { note: modal.note || null }, {
        preserveScroll: true,
        onSuccess: () => { modal.row = null; },
        onFinish: () => { modal.loading = false; },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Imtihon to'lovlarini qaytarish" />

        <PageHeader title="Imtihon to'lovlarini qaytarish"
            description="Bekor qilingan shanba imtihonlari va ikki marta to'langan kirishlar. Pul avtomatik qaytarilmaydi."
            :breadcrumbs="[{ label: 'To\'lovlar', href: route('admin.payments.index') }, { label: 'Imtihon qaytarishlari' }]" />

        <div class="space-y-4">
            <Alert variant="info">
                Pulni Payme yoki Click kabinetidan (yoki naqd) qaytaring, so'ng «Qaytarildi» ni bosing. Payme kabinetidan bekor qilingan
                to'lov o'zi «Qaytarilgan» bo'ladi.
            </Alert>

            <Tabs v-model="tab" :tabs="tabs" variant="pills" label="Qaytarishlar" />

            <p v-if="tab === 'pending' && pending.length" class="text-sm text-fg-muted">
                Jami qaytarish kerak: <span class="font-semibold tabular-nums text-fg">{{ money(total) }} so'm</span>
            </p>

            <Table :columns="columns" :rows="rows" row-key="key" caption="Imtihon to'lovlarini qaytarish">
                <template #cell-student="{ row }">
                    <span class="font-medium text-fg">{{ row.student }}</span>
                    <span v-if="row.phone" class="block text-13 tabular-nums text-fg-subtle">{{ row.phone }}</span>
                    <span class="block text-13 text-fg-muted md:hidden">{{ row.session }}</span>
                </template>
                <template #cell-session="{ row }">
                    <span class="text-fg">{{ row.session }}</span>
                    <span class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-fg-subtle">
                        <Badge v-if="row.kind === 'duplicate'" variant="warning" size="sm">Ikki marta to'langan</Badge>
                        <Badge v-else-if="tab === 'pending'" variant="danger" size="sm">Imtihon bekor qilingan</Badge>
                        <Link v-if="row.payment_id" :href="route('admin.payments.show', row.payment_id)" class="focus-ring rounded-sm font-mono hover:text-brand">
                            №{{ row.order_id }}
                        </Link>
                    </span>
                </template>
                <template #cell-amount="{ value }">
                    <span class="whitespace-nowrap font-medium tabular-nums text-fg">{{ money(value) }} <span class="text-fg-subtle">so'm</span></span>
                </template>
                <template #cell-provider="{ value }"><span class="text-fg-muted">{{ PROVIDERS[value] || value || '—' }}</span></template>
                <template #cell-requested_at="{ value }"><span class="tabular-nums text-fg-muted">{{ dt(value) }}</span></template>
                <template #cell-refunded_at="{ value }"><span class="tabular-nums text-fg-muted">{{ dt(value) }}</span></template>
                <template #cell-note="{ value }"><span class="text-13 text-fg-muted">{{ value || '—' }}</span></template>
                <template #cell-actions="{ row }">
                    <Button size="sm" variant="secondary" :icon-left="CheckCircleIcon" @click="open(row)">Qaytarildi</Button>
                </template>
                <template #empty>
                    <EmptyState :icon="ArrowUturnLeftIcon" size="sm"
                        :title="tab === 'pending' ? 'Qaytariladigan to\'lov yo\'q' : 'Hali qaytarilgan to\'lov yo\'q'" />
                </template>
            </Table>
        </div>

        <Modal :show="!!modal.row" size="sm" title="Pul qaytarildimi?" @close="modal.row = null">
            <div v-if="modal.row" class="space-y-4">
                <p class="text-sm text-fg-muted">
                    <span class="font-medium text-fg">{{ modal.row.student }}</span> —
                    <span class="tabular-nums">{{ money(modal.row.amount) }} so'm</span> ({{ PROVIDERS[modal.row.provider] || modal.row.provider }}).
                    Belgilangach to'lov «Qaytarilgan» bo'ladi.
                </p>
                <Input v-model="modal.note" label="Izoh" optional maxlength="200" placeholder="Masalan: Payme kabinetidan qaytarildi" />
            </div>
            <template #footer>
                <Button variant="secondary" :disabled="modal.loading" @click="modal.row = null">Ortga</Button>
                <Button :loading="modal.loading" :icon-left="CheckCircleIcon" @click="confirm">Qaytarildi</Button>
            </template>
        </Modal>
    </AdminLayout>
</template>
