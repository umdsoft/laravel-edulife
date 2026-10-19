<script setup>
/**
 * Table — data table with sticky header, hover rows, loading skeleton and empty state.
 *
 *   <Table :columns="[
 *            { key: 'name', label: 'Ism' },
 *            { key: 'score', label: 'Ball', numeric: true, sortable: true },
 *            { key: 'actions', label: '', align: 'right', width: '1%' },
 *          ]"
 *          :rows="users.data" :loading="loading" row-key="id"
 *          :sort-key="filters.sort" :sort-dir="filters.dir" @sort="onSort" @row-click="open">
 *     <template #cell-name="{ row }"><span class="font-medium">{{ row.full_name }}</span></template>
 *     <template #cell-actions="{ row }"><Dropdown>…</Dropdown></template>
 *     <template #empty><EmptyState title="Hech narsa topilmadi" /></template>
 *   </Table>
 *
 * Column fields: key, label, align (left|center|right), numeric (right + tabular-nums),
 *   width, sortable, class (td), headerClass (th), hideBelow ('sm'|'md'|'lg' → hidden on small screens).
 * Sticky header: the header sticks while scrolling inside the table when `max-height` is set
 *   (a horizontally scrollable box cannot stick to the page scroll).
 * Value lookup supports dot paths ("user.full_name"). Use `#cell-<key>` / `#head-<key>` slots.
 * For fully custom rows pass `<tr>` elements in the default slot (columns still render the header).
 */
import { computed } from 'vue';
import { ChevronUpIcon, ChevronDownIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid';
import EmptyState from './EmptyState.vue';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    rowKey: { type: [String, Function], default: 'id' },
    loading: Boolean,
    loadingRows: { type: Number, default: 5 },
    stickyHeader: { type: Boolean, default: true },
    // e.g. "32rem" — makes the body scroll inside the table so the sticky header stays visible
    maxHeight: { type: String, default: null },
    dense: Boolean,
    hoverable: { type: Boolean, default: true },
    clickable: Boolean,
    sortKey: { type: String, default: null },
    sortDir: { type: String, default: 'asc' },
    emptyTitle: { type: String, default: 'Ma\'lumot topilmadi' },
    emptyDescription: { type: String, default: null },
    caption: { type: String, default: null },
    // wrap in bordered surface (set false when already inside a Card with padding="none")
    bordered: { type: Boolean, default: true },
});

const emit = defineEmits(['sort', 'row-click']);

const get = (row, key) => String(key).split('.').reduce((acc, k) => (acc == null ? acc : acc[k]), row);
const keyOf = (row, i) => (typeof props.rowKey === 'function' ? props.rowKey(row, i) : get(row, props.rowKey) ?? i);

const hideCls = { sm: 'hidden sm:table-cell', md: 'hidden md:table-cell', lg: 'hidden lg:table-cell' };
const alignOf = (col) => (col.numeric ? 'right' : col.align || 'left');
const alignCls = { left: 'text-left', center: 'text-center', right: 'text-right' };
const cellPad = computed(() => (props.dense ? 'px-3 py-2' : 'px-4 py-3'));

const onSort = (col) => {
    if (!col.sortable) return;
    const dir = props.sortKey === col.key && props.sortDir === 'asc' ? 'desc' : 'asc';
    emit('sort', { key: col.key, dir });
};

const ariaSort = (col) => {
    if (!col.sortable) return undefined;
    if (props.sortKey !== col.key) return 'none';
    return props.sortDir === 'desc' ? 'descending' : 'ascending';
};

const onRowClick = (row, i, e) => {
    if (!props.clickable) return;
    // ignore clicks on interactive children
    if (e.target.closest('a,button,input,select,textarea,label,[role="menu"]')) return;
    emit('row-click', row, i);
};
</script>

<template>
    <div :class="[bordered ? 'overflow-hidden rounded-lg border border-border bg-surface shadow-xs' : '']">
        <div class="overflow-x-auto scrollbar-thin" :class="maxHeight ? 'overflow-y-auto' : ''"
            :style="maxHeight ? { maxHeight } : null">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <caption v-if="caption" class="sr-only">{{ caption }}</caption>
                <thead v-if="columns.length">
                    <tr>
                        <th v-for="col in columns" :key="col.key" scope="col" :style="col.width ? { width: col.width } : null"
                            :aria-sort="ariaSort(col)" :class="[
                                'border-b border-border bg-surface-2 text-xs font-medium uppercase tracking-wide text-fg-subtle',
                                stickyHeader ? 'sticky top-0 z-10' : '',
                                dense ? 'px-3 py-2' : 'px-4 py-2.5',
                                alignCls[alignOf(col)], hideCls[col.hideBelow] || '', col.headerClass || '',
                            ]">
                            <slot :name="`head-${col.key}`" :column="col">
                                <button v-if="col.sortable" type="button"
                                    class="focus-ring -mx-1 inline-flex items-center gap-1 rounded-sm px-1 uppercase hover:text-fg"
                                    @click="onSort(col)">
                                    {{ col.label }}
                                    <ChevronUpIcon v-if="sortKey === col.key && sortDir === 'asc'" class="h-4 w-4" aria-hidden="true" />
                                    <ChevronDownIcon v-else-if="sortKey === col.key" class="h-4 w-4" aria-hidden="true" />
                                    <ChevronUpDownIcon v-else class="h-4 w-4 opacity-50" aria-hidden="true" />
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </slot>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <tr v-for="n in loadingRows" :key="`sk-${n}`" aria-hidden="true">
                            <td v-for="col in columns" :key="col.key"
                                :class="['border-b border-border', cellPad, hideCls[col.hideBelow] || '']">
                                <div class="h-4 animate-pulse rounded bg-surface-2 motion-reduce:animate-none"
                                    :class="alignOf(col) === 'right' ? 'ml-auto w-12' : 'w-3/4'" />
                            </td>
                        </tr>
                    </template>

                    <!-- Custom body -->
                    <slot v-else-if="$slots.default" />

                    <!-- Rows -->
                    <template v-else-if="rows.length">
                        <tr v-for="(row, i) in rows" :key="keyOf(row, i)" :class="[
                            'group',
                            hoverable ? 'hover:bg-surface-2/60' : '',
                            clickable ? 'cursor-pointer' : '',
                        ]" @click="onRowClick(row, i, $event)">
                            <td v-for="col in columns" :key="col.key" :class="[
                                'border-b border-border align-middle text-fg group-last:border-b-0',
                                cellPad, alignCls[alignOf(col)], col.numeric ? 'tabular-nums' : '',
                                hideCls[col.hideBelow] || '', col.class || '',
                            ]">
                                <slot :name="`cell-${col.key}`" :row="row" :value="get(row, col.key)" :index="i">
                                    {{ get(row, col.key) ?? '—' }}
                                </slot>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td :colspan="columns.length || 1" class="px-4 py-10">
                            <slot name="empty">
                                <EmptyState :title="emptyTitle" :description="emptyDescription" size="sm" />
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="$slots.footer" class="border-t border-border">
            <slot name="footer" />
        </div>
    </div>
</template>
