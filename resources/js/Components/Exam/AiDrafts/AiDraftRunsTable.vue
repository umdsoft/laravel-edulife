<script setup>
/**
 * AI drafting run log (stage 4): what was asked, outcome, tokens, cost.
 */
import { Badge, Pagination, Table, Tooltip } from '@/Components/UI';

defineProps({
    runs: { type: Object, required: true }, // Laravel paginator
    showPagination: { type: Boolean, default: true },
});

const STATUS = {
    queued: ['neutral', 'Navbatda'],
    running: ['info', 'Ishlamoqda'],
    succeeded: ['success', 'Tayyor'],
    partial: ['warning', 'Qisman'],
    failed: ['danger', 'Xato'],
    skipped: ['neutral', "O'tkazib yuborildi"],
};

const columns = [
    { key: 'created_at', label: 'Sana', width: '9rem' },
    { key: 'subject', label: 'Fan / mavzu' },
    { key: 'difficulty_level', label: 'Qiyinlik', numeric: true, hideBelow: 'md' },
    { key: 'drafts', label: 'Qoralama', numeric: true },
    { key: 'status', label: 'Holat' },
    { key: 'tokens', label: 'Tokenlar', numeric: true, hideBelow: 'lg' },
    { key: 'cost', label: 'Xarajat', numeric: true },
    { key: 'prompt_version', label: 'Prompt', hideBelow: 'lg' },
];

const fmtDate = (iso) => (iso ? new Date(iso).toLocaleString('uz-UZ', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '—');
const fmtInt = (n) => Number(n || 0).toLocaleString('uz-UZ');
const fmtUsd = (n) => `$${Number(n || 0).toFixed(Number(n) < 1 ? 4 : 2)}`;
</script>

<template>
    <div>
        <Table :columns="columns" :rows="runs.data" empty-title="Hali so'rov yo'q"
            empty-description="Qoralama yaratilgach bu yerda tokenlar va xarajat ko'rinadi." dense>
            <template #cell-created_at="{ row }">
                <span class="tabular-nums">{{ fmtDate(row.created_at) }}</span>
                <span v-if="row.trigger === 'schedule'" class="block text-xs text-fg-subtle">jadval bo'yicha</span>
            </template>
            <template #cell-subject="{ row }">
                <Badge v-if="row.subject" :variant="row.subject.code === 'physics' ? 'physics' : 'math'" size="sm">{{ row.subject.name }}</Badge>
                <span class="ml-1 text-sm text-fg-muted">{{ row.topic || 'istalgan mavzu' }}</span>
            </template>
            <template #cell-drafts="{ row }">
                <span class="tabular-nums">{{ row.drafts_created }} / {{ row.count_requested }}</span>
                <span v-if="row.drafts_rejected || row.duplicates_skipped" class="block text-xs text-fg-subtle">
                    <template v-if="row.drafts_rejected">{{ row.drafts_rejected }} rad</template>
                    <template v-if="row.drafts_rejected && row.duplicates_skipped"> · </template>
                    <template v-if="row.duplicates_skipped">{{ row.duplicates_skipped }} takror</template>
                </span>
            </template>
            <template #cell-status="{ row }">
                <Tooltip v-if="row.error" :text="row.error">
                    <Badge :variant="(STATUS[row.status] || STATUS.queued)[0]" dot>{{ (STATUS[row.status] || STATUS.queued)[1] }}</Badge>
                </Tooltip>
                <Badge v-else :variant="(STATUS[row.status] || STATUS.queued)[0]" dot>{{ (STATUS[row.status] || STATUS.queued)[1] }}</Badge>
            </template>
            <template #cell-tokens="{ row }">
                <span class="tabular-nums text-sm">{{ fmtInt(row.input_tokens) }} / {{ fmtInt(row.output_tokens) }}</span>
            </template>
            <template #cell-cost="{ row }">
                <span class="tabular-nums">{{ fmtUsd(row.estimated_cost_usd) }}</span>
            </template>
            <template #cell-prompt_version="{ row }">
                <span class="font-mono text-xs text-fg-muted">{{ row.prompt_version || '—' }}</span>
                <span v-if="row.model" class="block font-mono text-xs text-fg-subtle">{{ row.model }}</span>
            </template>
        </Table>
        <Pagination v-if="showPagination && runs.last_page > 1" :data="runs" class="mt-4" />
    </div>
</template>
