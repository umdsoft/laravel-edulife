<script setup>
/**
 * Admin "AI qoralamalar" (stage 4): run log with tokens and estimated cost,
 * weekly budget, and a form to start a drafting run.
 */
import { Head } from '@inertiajs/vue3';
import { BanknotesIcon, CpuChipIcon, DocumentDuplicateIcon, CalendarDaysIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge, Button, Card, PageHeader, StatCard } from '@/Components/UI';
import AiDraftRunForm from '@/Components/Exam/AiDrafts/AiDraftRunForm.vue';
import AiDraftRunsTable from '@/Components/Exam/AiDrafts/AiDraftRunsTable.vue';

defineProps({
    runs: { type: Object, required: true },
    totals: { type: Object, required: true },
    settings: { type: Object, required: true },
    options: { type: Object, required: true },
});

const usd = (n) => `$${Number(n || 0).toFixed(2)}`;
const int = (n) => Number(n || 0).toLocaleString('uz-UZ');
</script>

<template>
    <AdminLayout>
        <Head title="AI qoralamalar" />

        <PageHeader title="AI qoralamalar" description="Ustoz manbalaridan AI tuzgan savol qoralamalari: so'rovlar, tokenlar va taxminiy xarajat">
            <template #meta>
                <Badge :variant="settings.configured ? 'success' : 'warning'" dot>
                    {{ settings.configured ? 'Ulangan' : 'Kalit kiritilmagan' }}
                </Badge>
                <Badge variant="neutral">{{ settings.model }}</Badge>
                <Badge v-if="settings.prompt_version" variant="neutral">{{ settings.prompt_version }}</Badge>
            </template>
            <template #actions>
                <Button v-if="settings.review_url" variant="secondary" :icon-left="ClipboardDocumentCheckIcon" :href="settings.review_url">
                    Tekshiruv navbati
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Shu hafta xarajat" :value="usd(totals.week.cost_usd)" :icon="BanknotesIcon"
                :hint="settings.weekly_budget_usd ? `Haftalik limit: ${usd(settings.weekly_budget_usd)}` : null" />
            <StatCard label="Shu oy xarajat" :value="usd(totals.month.cost_usd)" :icon="CalendarDaysIcon" tone="info"
                :hint="`${int(totals.month.runs)} ta so'rov`" />
            <StatCard label="Shu hafta qoralama" :value="int(totals.week.drafts)" :icon="DocumentDuplicateIcon" tone="success"
                :hint="settings.weekly_enabled ? `Haftalik reja: ${int(settings.weekly_target)} ta` : 'Haftalik reja o\'chiq'" />
            <StatCard label="Shu oy tokenlar" :value="int(totals.month.input_tokens + totals.month.output_tokens)" :icon="CpuChipIcon" tone="warning"
                :hint="`kirish ${int(totals.month.input_tokens)} · chiqish ${int(totals.month.output_tokens)}`" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <Card title="So'rovlar jurnali" padding="none" class="xl:col-span-2">
                <div class="p-4">
                    <AiDraftRunsTable :runs="runs" />
                </div>
            </Card>

            <Card title="Qoralama yaratish" description="So'rov navbatga qo'yiladi; tayyor qoralamalar tekshiruv navbatiga tushadi.">
                <AiDraftRunForm :options="options" :settings="settings" submit-route="admin.exam.ai-drafts.store" />
            </Card>
        </div>
    </AdminLayout>
</template>
