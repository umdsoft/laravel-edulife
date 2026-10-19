<script setup>
/**
 * TopbarSearch — search field in the app top bar. Submits GET `action?q=…` via Inertia.
 * Press "/" anywhere to focus it.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    action: { type: String, required: true },
    placeholder: { type: String, default: 'Qidirish…' },
    param: { type: String, default: 'q' },
});

const page = usePage();
const input = ref(null);
const initial = () => {
    try {
        return new URL(page.url, window.location.origin).searchParams.get(props.param) || '';
    } catch {
        return '';
    }
};
const query = ref(initial());

const submit = () => {
    const q = query.value.trim();
    router.get(props.action, q ? { [props.param]: q } : {});
};

const onGlobalKey = (e) => {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    const t = e.target;
    if (t && (t.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName))) return;
    e.preventDefault();
    input.value?.focus();
};
onMounted(() => document.addEventListener('keydown', onGlobalKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onGlobalKey));
</script>

<template>
    <form role="search" class="relative w-full max-w-md" @submit.prevent="submit">
        <label for="topbar-search" class="sr-only">{{ placeholder }}</label>
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle"
            aria-hidden="true" />
        <input id="topbar-search" ref="input" v-model="query" type="search" :placeholder="placeholder" autocomplete="off"
            class="h-9 w-full rounded-md border border-transparent bg-surface-2 pl-9 pr-10 text-sm text-fg placeholder:text-fg-subtle transition-colors focus:border-brand-500 focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15" />
        <kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-border bg-surface px-1.5 text-[11px] font-medium text-fg-subtle lg:inline">/</kbd>
    </form>
</template>
