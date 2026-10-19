<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ClockIcon, ChartBarIcon, ExclamationTriangleIcon, ComputerDesktopIcon, NoSymbolIcon, ArrowPathIcon, StopCircleIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Alert, Avatar, Badge, Button, Card, EmptyState, Modal, PageHeader, StatCard, Table, Textarea } from '@/Components/UI';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    device: { type: Object, default: null },
    sections: { type: Array, default: () => [] },
    violations: { type: Array, default: () => [] },
});

const STATUS = {
    in_progress: { label: 'Jarayonda', variant: 'info' },
    completed: { label: 'Yakunlangan', variant: 'success' },
    submitted: { label: 'Topshirilgan', variant: 'success' },
    expired: { label: 'Vaqti tugagan', variant: 'warning' },
    disqualified: { label: 'Chetlatilgan', variant: 'danger' },
};
const statusOf = (s) => STATUS[s] || { label: s || '—', variant: 'neutral' };
const SEVERITY = { low: 'neutral', medium: 'warning', high: 'danger', critical: 'danger' };

const duration = computed(() => {
    const s = Number(props.attempt.duration_seconds) || 0;
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h ? `${h} soat ${m} daq` : `${m} daq`;
});
const inProgress = computed(() => props.attempt.status === 'in_progress');

const busy = ref(false);
const post = (name, data = {}) => {
    router.post(route(name, { olympiad: props.olympiad.id, attempt: props.attempt.id }), data, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => (busy.value = false),
    });
};

const showDisqualify = ref(false);
const dqForm = useForm({ reason: '' });
const disqualify = () => {
    dqForm.post(route('admin.olympiads.monitor.disqualify', { olympiad: props.olympiad.id, attempt: props.attempt.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDisqualify.value = false;
            dqForm.reset();
        },
    });
};

const sectionColumns = [
    { key: 'section_title', label: "Bo'lim" },
    { key: 'status', label: 'Holat', hideBelow: 'sm' },
    { key: 'questions_answered', label: 'Javoblar', numeric: true },
    { key: 'score', label: 'Ball', numeric: true },
];
const violationColumns = [
    { key: 'type', label: 'Qoidabuzarlik' },
    { key: 'severity', label: 'Darajasi', hideBelow: 'sm' },
    { key: 'count', label: 'Soni', numeric: true },
    { key: 'created_at', label: 'Vaqt', numeric: true },
];
</script>

<template>
    <AdminLayout>
        <Head :title="`${attempt.user.name} — ishtirokchi`" />

        <PageHeader :title="attempt.user.name" :description="olympiad.title"
            :breadcrumbs="[
                { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
                { label: olympiad.title, href: route('admin.olympiads.show', olympiad.id) },
                { label: 'Monitoring', href: route('admin.olympiads.monitor', olympiad.id) },
                { label: attempt.user.name },
            ]">
            <template #meta>
                <Badge :variant="attempt.is_disqualified ? 'danger' : statusOf(attempt.status).variant" dot>
                    {{ attempt.is_disqualified ? 'Chetlatilgan' : statusOf(attempt.status).label }}
                </Badge>
                <Badge v-if="attempt.rank" variant="brand">{{ attempt.rank }}-o'rin</Badge>
            </template>
            <template #actions>
                <Button v-if="inProgress && !attempt.is_disqualified" variant="secondary" :icon-left="StopCircleIcon" :loading="busy"
                    @click="post('admin.olympiads.monitor.force-submit')">Majburiy yakunlash</Button>
                <Button v-if="attempt.is_disqualified" variant="secondary" :icon-left="ArrowPathIcon" :loading="busy"
                    @click="post('admin.olympiads.monitor.reinstate')">Qayta tiklash</Button>
                <Button v-else variant="danger" :icon-left="NoSymbolIcon" @click="showDisqualify = true">Chetlatish</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="attempt.is_disqualified" variant="danger" title="Ishtirokchi chetlatilgan">
                {{ attempt.disqualified_reason || 'Sabab ko\'rsatilmagan' }}
            </Alert>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Ball" :value="`${attempt.score ?? 0} / ${attempt.max_score}`" :icon="ChartBarIcon" tone="brand" />
                <StatCard label="Natija" :value="attempt.score_percent" unit="%" :icon="ChartBarIcon" tone="success" />
                <StatCard label="Sarflangan vaqt" :value="duration" :icon="ClockIcon" tone="info" />
                <StatCard label="Ogohlantirishlar" :value="attempt.warnings_count" :hint="`Tab: ${attempt.tab_switches} · To'liq ekran: ${attempt.fullscreen_exits}`"
                    :icon="ExclamationTriangleIcon" :tone="attempt.warnings_count ? 'warning' : 'neutral'" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <Card title="Bo'limlar" padding="none">
                        <Table :columns="sectionColumns" :rows="sections" :bordered="false" caption="Bo'limlar bo'yicha natija"
                            empty-title="Bo'limlar boshlanmagan">
                            <template #cell-status="{ value }"><Badge :variant="statusOf(value).variant" size="sm">{{ statusOf(value).label }}</Badge></template>
                            <template #cell-score="{ row }">{{ row.score ?? 0 }} / {{ row.max_score }}</template>
                        </Table>
                    </Card>

                    <Card title="Qoidabuzarliklar" padding="none">
                        <Table :columns="violationColumns" :rows="violations" :bordered="false" caption="Qoidabuzarliklar"
                            empty-title="Qoidabuzarlik qayd etilmagan">
                            <template #cell-severity="{ value }"><Badge :variant="SEVERITY[value] || 'neutral'" size="sm">{{ value }}</Badge></template>
                        </Table>
                    </Card>
                </div>

                <aside class="space-y-6">
                    <Card>
                        <div class="flex items-center gap-4">
                            <Avatar :src="attempt.user.avatar" :name="attempt.user.name" size="lg" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-fg">{{ attempt.user.name }}</p>
                                <p class="truncate text-13 text-fg-subtle">{{ attempt.user.email || '—' }}</p>
                            </div>
                        </div>
                        <dl class="mt-4 divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Boshlagan</dt><dd class="font-medium tabular-nums">{{ attempt.started_at || '—' }}</dd></div>
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Yakunlagan</dt><dd class="font-medium tabular-nums">{{ attempt.completed_at || '—' }}</dd></div>
                        </dl>
                    </Card>

                    <Card title="Qurilma">
                        <dl v-if="device" class="divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Tizim</dt><dd class="font-medium">{{ device.os || '—' }}</dd></div>
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Brauzer</dt><dd class="font-medium">{{ device.browser || '—' }}</dd></div>
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Ekran</dt><dd class="font-medium tabular-nums">{{ device.screen || '—' }}</dd></div>
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">IP</dt><dd class="font-medium tabular-nums">{{ device.ip || '—' }}</dd></div>
                            <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Ishonch</dt><dd class="font-medium tabular-nums">{{ device.trust_score ?? '—' }}</dd></div>
                        </dl>
                        <EmptyState v-else size="sm" :icon="ComputerDesktopIcon" title="Qurilma qayd etilmagan" />
                    </Card>
                </aside>
            </div>
        </div>

        <Modal :show="showDisqualify" title="Ishtirokchini chetlatish" size="md" @close="showDisqualify = false">
            <form id="dq-form" class="space-y-4" @submit.prevent="disqualify">
                <p class="text-sm text-fg-muted">{{ attempt.user.name }} natijasi hisobga olinmaydi.</p>
                <Textarea v-model="dqForm.reason" label="Sabab" required :rows="3" :maxlength="500" :error="dqForm.errors.reason" />
            </form>
            <template #footer>
                <Button variant="secondary" @click="showDisqualify = false">Bekor qilish</Button>
                <Button variant="danger" type="submit" form="dq-form" :loading="dqForm.processing">Chetlatish</Button>
            </template>
        </Modal>
    </AdminLayout>
</template>
