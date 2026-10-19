<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from 'vue';
import { Alert, Badge, Button, Card, EmptyState, Modal, PageHeader, ProgressBar, StatCard } from '@/Components/UI';
import {
    ArrowDownTrayIcon, CheckBadgeIcon, ClipboardDocumentListIcon, CheckCircleIcon, ClockIcon, PencilSquareIcon,
    InformationCircleIcon, ArrowRightIcon, DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    olympiad: Object,
    statistics: Object,
    sectionQueue: Array,
});

const s = computed(() => props.statistics || {});
const num = (v) => Number(v) || 0;

const totalAttempts = computed(() => num(s.value.total_attempts));
const gradedAttempts = computed(() => num(s.value.fully_graded ?? s.value.auto_graded));
const pendingAttempts = computed(() => num(s.value.pending_grading));
const pendingManual = computed(() => num(s.value.pending_manual ?? props.sectionQueue?.reduce((a, x) => a + num(x.pending_count), 0)));
const gradedManually = computed(() => num(s.value.graded_manually));
const percent = computed(() => num(s.value.completion_percent ?? s.value.graded_percent));

const queue = computed(() => props.sectionQueue || []);
const canFinalize = computed(() => !(s.value.pending_grading > 0));

const isLoading = ref(false);
const confirmOpen = ref(false);

const finalize = () => {
    isLoading.value = true;
    router.post(route('admin.olympiads.grading.finalize', props.olympiad.id), {}, {
        onFinish: () => {
            isLoading.value = false;
            confirmOpen.value = false;
        },
    });
};

const breadcrumbs = computed(() => [
    { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
    { label: props.olympiad.title, href: route('admin.olympiads.show', props.olympiad.id) },
    { label: 'Baholash' },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="`Baholash — ${olympiad.title}`" />

        <PageHeader title="Baholash paneli" :description="olympiad.title" :breadcrumbs="breadcrumbs">
            <template #actions>
                <Button variant="secondary" :icon-left="ArrowDownTrayIcon" external
                    :href="route('admin.olympiads.grading.export', olympiad.id)">
                    CSV eksport
                </Button>
                <Button :icon-left="CheckBadgeIcon" :disabled="isLoading || !canFinalize" :loading="isLoading"
                    @click="confirmOpen = true">
                    Natijalarni yakunlash
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Yakunlangan urinishlar" :value="totalAttempts" :icon="ClipboardDocumentListIcon" tone="neutral" />
                <StatCard label="To'liq baholangan" :value="gradedAttempts" :icon="CheckCircleIcon" tone="success" />
                <StatCard label="Baholanmagan urinishlar" :value="pendingAttempts" :icon="ClockIcon" tone="warning" />
                <StatCard label="Qo'lda baholash navbati" :value="pendingManual" :icon="PencilSquareIcon" tone="brand"
                    :hint="gradedManually ? `${gradedManually} ta baholandi` : null" />
            </div>

            <Card title="Baholash jarayoni">
                <ProgressBar :value="percent" :tone="percent >= 100 ? 'success' : 'brand'" size="md"
                    label="Baholangan urinishlar ulushi" show-value />
                <p class="mt-2 text-13 text-fg-subtle">
                    <span class="tabular-nums">{{ gradedAttempts }}</span> / <span class="tabular-nums">{{ totalAttempts }}</span>
                    urinish to'liq baholangan
                </p>
                <Alert v-if="!canFinalize" variant="info" class="mt-4" :icon="InformationCircleIcon">
                    Natijalarni yakunlash uchun barcha urinishlar baholangan bo'lishi kerak.
                </Alert>
            </Card>

            <Card padding="none" title="Bo'limlar bo'yicha navbat" description="Ochiq javobli savollar qo'lda baholanadi">
                <ul v-if="queue.length" class="divide-y divide-border">
                    <li v-for="item in queue" :key="item.section.id"
                        class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-subtle">
                                <DocumentTextIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">{{ item.section.title }}</p>
                                <p class="text-13 text-fg-subtle">{{ item.section.type }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                            <Badge v-if="item.pending_count > 0" variant="warning">
                                <span class="tabular-nums">{{ item.pending_count }}</span> ta kutilmoqda
                            </Badge>
                            <Badge v-else variant="success" dot>Baholangan</Badge>
                            <Button v-if="item.pending_count > 0" size="sm" :icon-right="ArrowRightIcon"
                                :href="route('admin.olympiads.grading.section', { olympiad: olympiad.id, section: item.section.id })">
                                Baholashni boshlash
                            </Button>
                        </div>
                    </li>
                </ul>
                <EmptyState v-else :icon="CheckCircleIcon" title="Qo'lda baholash talab qilinmaydi"
                    description="Bu olimpiadada ochiq javobli bo'limlar yo'q" size="sm" class="py-10" />
                <template #footer>
                    <p class="flex items-start gap-2 text-13 text-fg-muted">
                        <InformationCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-info" aria-hidden="true" />
                        Test va ko'p tanlovli savollar avtomatik baholanadi. Faqat yozma yechimli savollar qo'lda baholanadi.
                    </p>
                </template>
            </Card>
        </div>

        <Modal :show="confirmOpen" size="md" title="Natijalarni yakunlash" @close="confirmOpen = false">
            <p class="text-sm text-fg-muted">
                Ballar va o'rinlar yakuniy deb belgilanadi va ishtirokchilarga e'lon qilinadi.
                <span class="font-medium text-fg">Bu amalni qaytarib bo'lmaydi.</span>
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="isLoading" @click="confirmOpen = false">Bekor qilish</Button>
                    <Button :loading="isLoading" :icon-left="CheckBadgeIcon" @click="finalize">Ha, yakunlash</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
