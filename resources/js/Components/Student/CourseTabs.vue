<script setup>
/**
 * CourseTabs — tab bar for the course detail page (panels are rendered by the page).
 * API unchanged: tabs [{ id, name, count? }], activeTab, emits update:activeTab.
 */
import { ref } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
    activeTab: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:activeTab']);
const btns = ref([]);

const onKeydown = (e, index) => {
    const last = props.tabs.length - 1;
    let next = null;
    if (e.key === 'ArrowRight') next = index === last ? 0 : index + 1;
    else if (e.key === 'ArrowLeft') next = index === 0 ? last : index - 1;
    else if (e.key === 'Home') next = 0;
    else if (e.key === 'End') next = last;
    if (next === null) return;
    e.preventDefault();
    emit('update:activeTab', props.tabs[next].id);
    btns.value[next]?.focus();
};
</script>

<template>
    <div class="-mx-1 overflow-x-auto px-1">
        <div role="tablist" aria-label="Kurs bo'limlari" class="flex gap-6 border-b border-border">
            <button v-for="(tab, i) in tabs" :key="tab.id" :ref="(el) => (btns[i] = el)" type="button" role="tab"
                :aria-selected="activeTab === tab.id" :tabindex="activeTab === tab.id ? 0 : -1" :class="[
                    'focus-ring -mb-px inline-flex h-11 items-center gap-2 whitespace-nowrap border-b-2 px-1 text-sm font-medium transition-colors duration-150',
                    activeTab === tab.id
                        ? 'border-brand-600 text-fg'
                        : 'border-transparent text-fg-muted hover:border-border-strong hover:text-fg',
                ]" @click="emit('update:activeTab', tab.id)" @keydown="onKeydown($event, i)">
                {{ tab.name }}
                <span v-if="tab.count" :class="[
                    'rounded-full px-1.5 py-px text-[11px] font-semibold tabular-nums',
                    activeTab === tab.id ? 'bg-brand-50 text-brand' : 'bg-surface-2 text-fg-subtle',
                ]">
                    {{ tab.count }}
                </span>
            </button>
        </div>
    </div>
</template>
