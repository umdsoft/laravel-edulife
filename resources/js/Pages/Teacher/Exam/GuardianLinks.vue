<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckIcon, CheckBadgeIcon, ExclamationTriangleIcon, XMarkIcon, UsersIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Badge, Button, Card, EmptyState, Modal, PageHeader, Pagination, Table, Tabs, Textarea } from '@/Components/UI';

const props = defineProps({
    links: { type: Object, required: true },
    status: { type: String, default: 'pending' },
    counts: { type: Object, default: () => ({}) },
});

const RELATIONS = { mother: 'Onasi', father: 'Otasi', guardian: 'Vasiy', other: 'Boshqa' };

const tabs = computed(() => [
    { key: 'pending', label: 'Kutilmoqda', count: props.counts.pending, href: route('teacher.exam.guardian-links.index', { status: 'pending' }), active: props.status === 'pending' },
    { key: 'verified', label: 'Tasdiqlangan', count: props.counts.verified, href: route('teacher.exam.guardian-links.index', { status: 'verified' }), active: props.status === 'verified' },
    { key: 'rejected', label: 'Rad etilgan', count: props.counts.rejected, href: route('teacher.exam.guardian-links.index', { status: 'rejected' }), active: props.status === 'rejected' },
]);

const columns = [
    { key: 'guardian', label: 'Ota-ona' },
    { key: 'student', label: 'O\'quvchi' },
    { key: 'check', label: 'Telefon', hideBelow: 'md' },
    { key: 'requested_at', label: 'Sana', hideBelow: 'sm' },
    { key: 'actions', label: '', align: 'right' },
];

const busy = ref(null);
const approve = (row) => router.post(route('teacher.exam.guardian-links.approve', row.id), {}, {
    preserveScroll: true,
    onStart: () => { busy.value = row.id; },
    onFinish: () => { busy.value = null; },
});

const rejecting = ref(null);
const rejectForm = useForm({ reason: '' });
const openReject = (row) => { rejecting.value = row; rejectForm.reset(); };
const submitReject = () => rejectForm.post(route('teacher.exam.guardian-links.reject', rejecting.value.id), {
    preserveScroll: true,
    onSuccess: () => { rejecting.value = null; },
});
</script>

<template>
    <TeacherLayout title="Ota-ona so'rovlari">
        <Head title="Ota-ona so'rovlari" />

        <PageHeader title="Ota-ona so'rovlari"
            description="Ota-ona farzandining raqamini kiritadi, siz tasdiqlaysiz. Tasdiqlangan ota-ona faqat o'z farzandining natijalarini ko'radi.">
            <template #tabs>
                <Tabs :tabs="tabs" label="Holat" />
            </template>
        </PageHeader>

        <Card padding="none">
            <Table :columns="columns" :rows="links.data" :bordered="false" empty-title="So'rovlar yo'q"
                empty-description="Ota-onalar so'rov yuborganda shu yerda paydo bo'ladi.">
                <template #cell-guardian="{ row }">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ row.guardian?.name || '—' }}</p>
                        <p class="text-xs text-fg-subtle tabular-nums">{{ row.guardian?.phone }} · {{ RELATIONS[row.relation] || row.relation }}</p>
                    </div>
                </template>
                <template #cell-student="{ row }">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ row.student?.name || '—' }}</p>
                        <p class="text-xs text-fg-subtle tabular-nums">{{ row.student?.phone }}</p>
                    </div>
                </template>
                <template #cell-check="{ row }">
                    <Badge v-if="row.phone_matches" variant="success" :icon="CheckBadgeIcon">Anketadagi raqam</Badge>
                    <Badge v-else-if="row.parent_phone" variant="warning" :icon="ExclamationTriangleIcon">Anketada: {{ row.parent_phone }}</Badge>
                    <span v-else class="text-xs text-fg-subtle">Anketa to'ldirilmagan</span>
                </template>
                <template #cell-requested_at="{ row }">
                    <span class="text-13 text-fg-muted tabular-nums">{{ row.verified_at || row.rejected_at || row.requested_at }}</span>
                    <p v-if="row.reject_reason" class="text-xs text-fg-subtle">{{ row.reject_reason }}</p>
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex justify-end gap-2">
                        <Button v-if="status !== 'verified'" size="sm" :icon-left="CheckIcon" :loading="busy === row.id" @click="approve(row)">Tasdiqlash</Button>
                        <Button v-if="status !== 'rejected'" size="sm" variant="secondary" :icon-left="XMarkIcon" @click="openReject(row)">
                            {{ status === 'verified' ? 'Bekor qilish' : 'Rad etish' }}
                        </Button>
                    </div>
                </template>
                <template #empty>
                    <EmptyState :icon="UsersIcon" size="sm" title="So'rovlar yo'q" description="Ota-onalar so'rov yuborganda shu yerda paydo bo'ladi." />
                </template>
                <template #footer><Pagination :data="links" /></template>
            </Table>
        </Card>

        <Modal :show="!!rejecting" title="So'rovni rad etish" size="md" @close="rejecting = null">
            <form id="reject-form" @submit.prevent="submitReject">
                <Textarea v-model="rejectForm.reason" label="Sabab" optional :rows="3" maxlength="255"
                    hint="Ota-onaga ko'rsatiladi" :error="rejectForm.errors.reason" />
            </form>
            <template #footer>
                <Button variant="secondary" @click="rejecting = null">Bekor qilish</Button>
                <Button variant="danger" type="submit" form="reject-form" :loading="rejectForm.processing">Rad etish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
