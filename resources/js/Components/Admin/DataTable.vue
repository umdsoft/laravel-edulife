<script setup>
/**
 * Admin DataTable — kept for backward compatibility; renders the UI kit Table with custom rows.
 *
 *   <DataTable :columns="[{ key: 'name', label: 'Ism', sortable: true, class: 'w-1/4' }]"
 *              :rows="users.data" :loading="loading" empty-text="Topilmadi" @sort="onSort">
 *     <template #default="{ rows }"><tr v-for="row in rows" :key="row.id">…<td>…</td></tr></template>
 *     <template #footer><Pagination :data="users" /></template>
 *   </DataTable>
 *
 * Column fields: key, label, sortable, class (header cell), align (left|center|right), numeric, width,
 * hideBelow. `sort` is emitted with the column key (as before); pass sortKey / sortDir to show the arrow.
 * Slots: default ({ rows }) → <tr>s, #empty, #footer.
 * For new code prefer <Table> from '@/Components/UI' with #cell-<key> slots.
 */
import { computed } from 'vue';
import Table from '@/Components/UI/Table.vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyText: String,
    emptyDescription: { type: String, default: null },
    sortKey: { type: String, default: null },
    sortDir: { type: String, default: 'asc' },
    dense: Boolean,
    maxHeight: { type: String, default: null },
});

const emit = defineEmits(['sort']);

const alignFromClass = (cls = '') => {
    if (/\btext-center\b/.test(cls)) return 'center';
    if (/\btext-right\b/.test(cls)) return 'right';
    return undefined;
};

const tableColumns = computed(() =>
    props.columns.map((col) => ({
        ...col,
        align: col.align || alignFromClass(col.class),
        headerClass: [col.headerClass, col.class].filter(Boolean).join(' '),
        class: undefined,
    })),
);

const onSort = ({ key }) => emit('sort', key);
</script>

<template>
    <Table :columns="tableColumns" :rows="rows" :loading="loading" :sort-key="sortKey" :sort-dir="sortDir"
        :dense="dense" :max-height="maxHeight" :empty-title="emptyText || 'Ma\'lumot topilmadi'"
        :empty-description="emptyDescription" @sort="onSort">
        <template v-if="rows.length && $slots.default" #default>
            <slot :rows="rows" />
        </template>
        <template v-if="$slots.empty" #empty>
            <slot name="empty" />
        </template>
        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </Table>
</template>
