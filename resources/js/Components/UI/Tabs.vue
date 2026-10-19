<script setup>
/**
 * Tabs — two modes.
 *
 * 1) Panels (client-side): v-model holds the active key; ←/→/Home/End move focus (roving tabindex).
 *    <Tabs v-model="tab" :tabs="[{ key: 'info', label: 'Ma\'lumot' }, { key: 'lessons', label: 'Darslar', count: 12 }]">
 *      <template #info>...</template>
 *      <template #lessons>...</template>
 *    </Tabs>
 *    (or render panels yourself and only use the tab bar; the default slot gets { active })
 *
 * 2) Navigation: give tabs an `href` → renders Inertia links inside <nav>, active by `active` flag
 *    or by matching the current URL.
 *    <Tabs :tabs="[{ key: 'all', label: 'Barchasi', href: route('student.my-courses.index') }, …]" />
 *
 * variant: underline (default) | pills.  Tab fields: key, label, count?, icon?, disabled?, href?, active?
 */
import { computed, ref, useId } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { isActivePath } from '@/Utils/nav';

const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    tabs: { type: Array, required: true },
    variant: { type: String, default: 'underline', validator: (v) => ['underline', 'pills'].includes(v) },
    label: { type: String, default: 'Bo\'limlar' },
    // stretch tabs to full width (mobile segmented control)
    fitted: Boolean,
});

const emit = defineEmits(['update:modelValue', 'change']);
const page = usePage();
const uid = useId();
const btns = ref([]);

const isNav = computed(() => props.tabs.some((t) => t.href));
const activeKey = computed(() => {
    if (isNav.value) {
        const explicit = props.tabs.find((t) => t.active);
        if (explicit) return explicit.key;
        const byUrl = props.tabs.find((t) => t.href && isActivePath(page.url, t.href, true));
        return byUrl ? byUrl.key : null;
    }
    return props.modelValue ?? props.tabs[0]?.key;
});

const select = (tab) => {
    if (tab.disabled) return;
    emit('update:modelValue', tab.key);
    emit('change', tab.key);
};

const onKeydown = (e, index) => {
    const enabled = props.tabs.map((t, i) => (t.disabled ? -1 : i)).filter((i) => i >= 0);
    const pos = enabled.indexOf(index);
    let next = null;
    if (e.key === 'ArrowRight') next = enabled[(pos + 1) % enabled.length];
    else if (e.key === 'ArrowLeft') next = enabled[(pos - 1 + enabled.length) % enabled.length];
    else if (e.key === 'Home') next = enabled[0];
    else if (e.key === 'End') next = enabled[enabled.length - 1];
    if (next === null || next === undefined) return;
    e.preventDefault();
    select(props.tabs[next]);
    btns.value[next]?.focus();
};

const tabClass = (tab) => {
    const on = tab.key === activeKey.value;
    const base = 'focus-ring inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium transition-colors duration-150';
    const state = tab.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer';
    if (props.variant === 'pills') {
        return [base, state, 'h-9 rounded-md px-3', on ? 'bg-surface text-fg shadow-xs ring-1 ring-border' : 'text-fg-muted hover:text-fg', props.fitted ? 'flex-1' : ''];
    }
    return [base, state, '-mb-px h-11 border-b-2 px-1', on ? 'border-brand-600 text-fg' : 'border-transparent text-fg-muted hover:border-border-strong hover:text-fg', props.fitted ? 'flex-1' : ''];
};

const listClass = computed(() =>
    props.variant === 'pills'
        ? ['inline-flex gap-1 rounded-md bg-surface-2 p-1', props.fitted ? 'flex w-full' : '']
        : ['flex gap-6 border-b border-border', props.fitted ? 'gap-0' : ''],
);

const countClass = (tab) => [
    'rounded-full px-1.5 py-px text-[11px] font-semibold tabular-nums',
    tab.key === activeKey.value ? 'bg-brand-50 text-brand' : 'bg-surface-2 text-fg-subtle',
];
</script>

<template>
    <div>
        <div class="-mx-1 overflow-x-auto px-1 scrollbar-thin">
            <!-- Navigation tabs -->
            <nav v-if="isNav" :aria-label="label" :class="listClass">
                <Link v-for="tab in tabs" :key="tab.key" :href="tab.href" :class="tabClass(tab)"
                    :aria-current="tab.key === activeKey ? 'page' : undefined" preserve-scroll>
                    <component :is="tab.icon" v-if="tab.icon" class="h-[18px] w-[18px]" aria-hidden="true" />
                    {{ tab.label }}
                    <span v-if="tab.count !== undefined && tab.count !== null" :class="countClass(tab)">{{ tab.count }}</span>
                </Link>
            </nav>

            <!-- Panel tabs -->
            <div v-else role="tablist" :aria-label="label" :class="listClass">
                <button v-for="(tab, i) in tabs" :id="`tab-${uid}-${tab.key}`" :key="tab.key"
                    :ref="(el) => (btns[i] = el)" type="button" role="tab" :aria-selected="tab.key === activeKey"
                    :aria-controls="`panel-${uid}-${tab.key}`" :tabindex="tab.key === activeKey ? 0 : -1"
                    :disabled="tab.disabled" :class="tabClass(tab)" @click="select(tab)" @keydown="onKeydown($event, i)">
                    <component :is="tab.icon" v-if="tab.icon" class="h-[18px] w-[18px]" aria-hidden="true" />
                    {{ tab.label }}
                    <span v-if="tab.count !== undefined && tab.count !== null" :class="countClass(tab)">{{ tab.count }}</span>
                </button>
            </div>
        </div>

        <template v-if="!isNav">
            <template v-for="tab in tabs" :key="tab.key">
                <div v-if="$slots[tab.key] && tab.key === activeKey" :id="`panel-${uid}-${tab.key}`" role="tabpanel"
                    :aria-labelledby="`tab-${uid}-${tab.key}`" tabindex="0" class="pt-5 focus:outline-none">
                    <slot :name="tab.key" />
                </div>
            </template>
        </template>
        <slot :active="activeKey" />
    </div>
</template>
