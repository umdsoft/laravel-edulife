<template>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th v-for="column in columns" :key="column.key" :class="[
                            'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                            column.class || '',
                        ]">
                            <button v-if="column.sortable" @click="$emit('sort', column.key)"
                                class="flex items-center gap-2 hover:text-gray-700 transition">
                                {{ column.label }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </button>
                            <span v-else>{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>

                <tbody v-if="!loading && rows.length > 0">
                    <slot :rows="rows" />
                </tbody>
            </table>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && rows.length === 0" class="py-12">
            <slot name="empty">
                <EmptyState icon="📭" :title="emptyText || 'Ma\'lumot topilmadi'"
                    description="Hech qanday ma'lumot mavjud emas" />
            </slot>
        </div>
    </div>
</template>

<script setup>
import EmptyState from './EmptyState.vue';

defineProps({
    columns: {
        type: Array,
        required: true,
        // Example: [{ key: 'name', label: 'Ism', sortable: true, class: 'w-1/4' }]
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
});

defineEmits(['sort']);
</script>
